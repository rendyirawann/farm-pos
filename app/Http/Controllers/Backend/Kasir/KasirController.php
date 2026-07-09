<?php

namespace App\Http\Controllers\Backend\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Menu;
use App\Models\MenuAddon;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Promo;
use App\Models\Shift;
use App\Models\DailySalesTarget;
use App\Tenancy\TenantManager;
use App\Tenancy\DepositConfig;
use App\Services\DepositService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * POS Counter satu-halaman (tanpa meja).
 * Alur: input nama -> pilih menu (+ add-ons) -> keranjang -> bayar (depan) / kirim ke dapur (belakang).
 * Pembayaran: Tunai atau QRIS (tanpa payment gateway). Setiap order punya nomor antrian harian.
 */
class KasirController extends Controller
{
    /** Halaman utama kasir (single page). */
    public function index()
    {
        $activeShift = Shift::where('user_id', Auth::id())->where('status', 'open')->first();
        if (!$activeShift) {
            return redirect()->route('shifts.index')
                ->with('warning', '⚠️ Akses ditolak! Anda wajib membuka shift dan mengisi modal kasir terlebih dahulu.');
        }

        $categories = Category::orderBy('name', 'asc')->get();
        $menus = Menu::with('activeAddons')
            ->where('is_available', true)
            ->orderBy('name', 'asc')
            ->get();
        $promos = Promo::where('is_active', true)->get();
        $setting = Setting::first();

        return view('backend.kasir.index', compact('categories', 'menus', 'promos', 'setting', 'activeShift'));
    }

    /** JSON daftar order untuk panel kanan (tab Sedang Diproses / Selesai). */
    public function listOrders()
    {
        $processing = Order::withCount('details')
            ->whereIn('order_status', ['pending', 'cooking', 'served'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($o) => $this->orderSummary($o));

        $completed = Order::withCount('details')
            ->where('order_status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn($o) => $this->orderSummary($o));

        return response()->json([
            'processing' => $processing,
            'completed'  => $completed,
        ]);
    }

    private function orderSummary(Order $o): array
    {
        return [
            'id'             => $o->id,
            'invoice_no'     => $o->invoice_no,
            'queue_number'   => $o->queue_number,
            'customer_name'  => $o->customer_name,
            'grand_total'    => (float) $o->grand_total,
            'payment_status' => $o->payment_status,
            'order_status'   => $o->order_status,
            'items_count'    => $o->details_count,
            'created_at'     => optional($o->created_at)->format('H:i'),
        ];
    }

    /** JSON detail 1 order (untuk modal View). */
    public function showOrder($id)
    {
        $order = Order::with('details.menu')->findOrFail($id);
        return response()->json([
            'order' => [
                'id'             => $order->id,
                'invoice_no'     => $order->invoice_no,
                'queue_number'   => $order->queue_number,
                'customer_name'  => $order->customer_name,
                'subtotal'       => (float) $order->subtotal,
                'discount_amount' => (float) $order->discount_amount,
                'tax'            => (float) $order->tax,
                'grand_total'    => (float) $order->grand_total,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'cash_received'  => $order->cash_received !== null ? (float) $order->cash_received : null,
                'change_amount'  => $order->change_amount !== null ? (float) $order->change_amount : null,
                'order_status'   => $order->order_status,
                'created_at'     => optional($order->created_at)->format('d/m/Y H:i'),
            ],
            'items' => $order->details->map(fn($d) => [
                'name'     => $d->menu->name ?? 'Menu dihapus',
                'qty'      => $d->qty,
                'price'    => (float) $d->price,
                'subtotal' => (float) $d->subtotal,
                'addons'   => $d->addons ?? [],
                'notes'    => $d->notes,
                'status'   => $d->status,
            ]),
        ]);
    }

    /**
     * Buat order baru dari keranjang.
     * Body: customer_name, cart[{menu_id, qty, addon_ids[], note}], promo_id,
     *       payment_method (cash|qris|null), cash_received.
     * Jika payment_method diisi -> bayar di depan (LUNAS). Jika null -> kirim ke dapur (BELUM LUNAS).
     */
    public function storeOrder(Request $request)
    {
        $request->validate([
            'cart'           => 'required|array|min:1',
            'cart.*.menu_id' => 'required|integer',
            'cart.*.qty'     => 'required|integer|min:1',
            'payment_method' => 'nullable|in:cash,qris',
        ]);

        try {
            DB::beginTransaction();

            $built = $this->buildOrderFromCart($request->cart, $request->promo_id);

            $order = Order::create(array_merge(
                $this->newOrderBase($request->input('customer_name')),
                [
                    'promo_id'        => $request->promo_id,
                    'subtotal'        => $built['subtotal'],
                    'discount_amount' => $built['discount_amount'],
                    'tax'             => $built['tax'],
                    'grand_total'     => $built['grand_total'],
                    'payment_status'  => 'unpaid',
                    'order_status'    => 'pending',
                ]
            ));

            foreach ($built['items'] as $item) {
                $order->details()->create($item);
            }

            // Pembayaran di depan (opsional)
            if ($request->filled('payment_method')) {
                $this->applyPayment($order, $request->payment_method, $request->cash_received);
            }

            DB::commit();

            return response()->json([
                'success'   => true,
                'order_id'  => $order->id,
                'paid'      => $order->payment_status === 'paid',
                'print_url' => route('kasir.print', $order->id),
                'receipt'   => $this->receiptPayload($order->fresh('details.menu')),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** Bayar order yang masih BELUM LUNAS (dari panel kanan). */
    public function payOrder($id, Request $request)
    {
        $request->validate(['payment_method' => 'required|in:cash,qris']);

        try {
            DB::beginTransaction();
            $order = Order::findOrFail($id);

            if ($order->payment_status === 'paid') {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Pesanan ini sudah lunas.'], 422);
            }

            $this->applyPayment($order, $request->payment_method, $request->cash_received);
            DB::commit();

            return response()->json([
                'success'   => true,
                'order_id'  => $order->id,
                'print_url' => route('kasir.print', $order->id),
                'receipt'   => $this->receiptPayload($order->fresh('details.menu')),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Selesaikan order (hilang dari daftar "Sedang Diproses").
     * WAJIB sudah lunas. Boleh sekaligus membayar (kirim payment_method) saat menyelesaikan.
     */
    public function completeOrder($id, Request $request)
    {
        try {
            DB::beginTransaction();
            $order = Order::findOrFail($id);
            $wasCompleted = $order->order_status === 'completed';

            // Jika belum bayar: harus bayar dulu. Terima pembayaran inline bila dikirim.
            if ($order->payment_status !== 'paid') {
                if (!$request->filled('payment_method')) {
                    DB::rollBack();
                    return response()->json([
                        'success'       => false,
                        'need_payment'  => true,
                        'error'         => 'Pesanan belum dibayar. Harap selesaikan pembayaran terlebih dahulu.',
                    ], 422);
                }
                $request->validate(['payment_method' => 'in:cash,qris']);
                $this->applyPayment($order, $request->payment_method, $request->cash_received);
            }

            // Plan deposit: potong poin transaksi saat pesanan diselesaikan (hanya sekali).
            $tenant = app(TenantManager::class)->tenant();
            if (!$wasCompleted && $tenant && $tenant->isDepositMode()) {
                $fee = DepositConfig::feePerTransaction();
                if ((float) $tenant->deposit_points < $fee) {
                    DB::rollBack();
                    return response()->json([
                        'success'    => false,
                        'need_topup' => true,
                        'error'      => 'Saldo deposit tidak cukup (sisa Rp' . number_format($tenant->deposit_points, 0, ',', '.')
                            . '). Silakan top up untuk menyelesaikan transaksi.',
                    ], 422);
                }
                app(DepositService::class)->deduct(
                    $tenant,
                    $fee,
                    'usage',
                    $order->uuid,
                    Auth::id(),
                    'Biaya transaksi pesanan ' . ($order->invoice_no ?? ('#' . $order->id))
                );
            }

            $order->update(['order_status' => 'completed']);
            DB::commit();

            return response()->json([
                'success'   => true,
                'order_id'  => $order->id,
                'print_url' => route('kasir.print', $order->id),
                'receipt'   => $this->receiptPayload($order->fresh('details.menu')),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus 1 pesanan (khusus OWNER). Boleh untuk pesanan berjalan maupun selesai,
     * baik yang belum lunas maupun sudah lunas. order_details ikut terhapus (cascade FK).
     */
    public function destroyOrder($id)
    {
        abort_unless(auth()->user()->can('order.delete'), 403);

        try {
            DB::beginTransaction();
            $order = Order::findOrFail($id); // ter-scope per-tenant
            $order->delete();                // order_details terhapus otomatis via cascadeOnDelete
            DB::commit();

            return response()->json([
                'success' => true,
                'widget'  => $this->todaySalesWidget(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset penjualan hari ini (khusus OWNER) — untuk membersihkan data testing.
     * Menghapus SEMUA pesanan hari ini (per-tenant) + baris target penjualan hari ini.
     * "Penjualan Hari Ini" adalah query turunan dari orders, jadi otomatis kembali 0.
     */
    public function resetToday(Request $request)
    {
        abort_unless(auth()->user()->can('sales.clear'), 403);

        // Cegah wipe lintas-tenant (mis. akun tanpa tenant aktif).
        $tenantId = app(TenantManager::class)->id();
        abort_if($tenantId === null, 403, 'Tidak ada tenant aktif.');

        try {
            DB::beginTransaction();
            $today = Carbon::today()->toDateString();

            // Hapus semua pesanan hari ini (order_details ikut via cascade FK).
            // Query ter-scope otomatis ke tenant aktif oleh TenantScope.
            $deleted = Order::whereDate('created_at', $today)->delete();

            // Reset target penjualan hari ini (kembali diisi saat buka shift pertama berikutnya).
            DailySalesTarget::where('date', $today)->delete();

            DB::commit();

            // Catatan aktivitas (best-effort; tidak memblokir bila gagal).
            try {
                activity('orders')
                    ->causedBy(auth()->user())
                    ->withProperties(['deleted_orders' => $deleted, 'date' => $today])
                    ->log('Reset penjualan hari ini');
            } catch (\Throwable $e) {
            }

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'widget'  => $this->todaySalesWidget(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Set / ubah target penjualan hari ini (khusus OWNER).
     * Berguna setelah reset (baris target ikut terhapus) atau untuk mengubah target berjalan.
     */
    public function setTarget(Request $request)
    {
        abort_unless(auth()->user()->can('sales.target'), 403);

        $tenantId = app(TenantManager::class)->id();
        abort_if($tenantId === null, 403, 'Tidak ada tenant aktif.');

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            // Upsert baris target hari ini (unik per tenant+tanggal; tenant_id terisi otomatis).
            DailySalesTarget::updateOrCreate(
                ['date' => Carbon::today()->toDateString()],
                ['amount' => $data['amount']]
            );

            return response()->json([
                'success' => true,
                'widget'  => $this->todaySalesWidget(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Nilai widget "Penjualan Hari Ini" & "Target" (sama persis dgn komposer di AppServiceProvider),
     * dikembalikan agar sidebar bisa diperbarui via JS tanpa reload (keranjang tetap aman).
     */
    private function todaySalesWidget(): array
    {
        $today = Carbon::today()->toDateString();

        $income = (float) Order::whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        $targetObj = DailySalesTarget::where('date', $today)->first();
        $target = $targetObj ? (float) $targetObj->amount : 0.0;

        $percentage = 0;
        $barWidth = 0;
        $color = 'bg-warning';
        if ($target > 0) {
            $percentage = (int) round(($income / $target) * 100);
            $barWidth = $percentage > 100 ? 100 : $percentage;
            if ($percentage >= 100) {
                $color = 'bg-success';
            } elseif ($percentage >= 50) {
                $color = 'bg-primary';
            }
        }

        return [
            'income'     => $income,
            'target'     => $target,
            'percentage' => $percentage,
            'bar_width'  => $barWidth,
            'bar_color'  => $color,
        ];
    }

    /** Struk cetak. */
    public function printReceipt($id)
    {
        $order = Order::with('details.menu')->findOrFail($id);
        $setting = Setting::first();
        return view('backend.kasir.print', compact('order', 'setting'));
    }

    /** Sinkronisasi order yang dibuat saat offline (PWA/Dexie). */
    public function syncOfflineOrders(Request $request)
    {
        $orders = $request->orders;
        if (!$orders || !is_array($orders)) {
            return response()->json(['success' => false, 'error' => 'No orders provided'], 400);
        }

        try {
            DB::beginTransaction();

            foreach ($orders as $offline) {
                if (!empty($offline['invoice_no']) && Order::where('invoice_no', $offline['invoice_no'])->exists()) {
                    continue; // hindari duplikat
                }

                $built = $this->buildOrderFromCart($offline['cart'] ?? [], $offline['promo_id'] ?? null);

                $base = $this->newOrderBase($offline['customer_name'] ?? null);
                if (!empty($offline['invoice_no'])) {
                    $base['invoice_no'] = $offline['invoice_no'];
                }

                $order = Order::create(array_merge($base, [
                    'promo_id'        => $offline['promo_id'] ?? null,
                    'subtotal'        => $built['subtotal'],
                    'discount_amount' => $built['discount_amount'],
                    'tax'             => $built['tax'],
                    'grand_total'     => $built['grand_total'],
                    'payment_status'  => 'unpaid',
                    'order_status'    => 'pending',
                ]));

                foreach ($built['items'] as $item) {
                    $order->details()->create($item);
                }

                if (!empty($offline['payment_method']) && in_array($offline['payment_method'], ['cash', 'qris'], true)) {
                    $this->applyPayment($order, $offline['payment_method'], $offline['cash_received'] ?? null);
                }
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ============================================================
    // Helpers
    // ============================================================

    /** Nilai dasar order baru + generate invoice & nomor antrian harian (per-tenant via scope). */
    private function newOrderBase(?string $customerName): array
    {
        $nextQueue = (int) (Order::whereDate('created_at', Carbon::today())->max('queue_number')) + 1;

        return [
            'invoice_no'    => 'MDA-INV-' . date('YmdHis') . rand(10, 99),
            'queue_number'  => $nextQueue,
            'customer_name' => $customerName ?: 'Pelanggan',
        ];
    }

    /**
     * Hitung ulang keranjang di server (anti-hack): ambil harga menu & add-ons dari DB.
     * cart item: {menu_id, qty, addon_ids[], note}
     */
    private function buildOrderFromCart(array $cart, $promoId): array
    {
        $subtotal = 0;
        $items = [];

        foreach ($cart as $row) {
            $menu = Menu::find($row['menu_id']);
            if (!$menu) {
                continue;
            }
            $qty = max(1, (int) ($row['qty'] ?? 1));

            $addonIds = array_filter(array_map('intval', (array) ($row['addon_ids'] ?? [])));
            $addonSnapshot = [];
            $addonPrice = 0;
            if (!empty($addonIds)) {
                $addons = MenuAddon::where('menu_id', $menu->id)
                    ->whereIn('id', $addonIds)
                    ->where('is_active', true)
                    ->get();
                foreach ($addons as $a) {
                    $addonPrice += (float) $a->price;
                    $addonSnapshot[] = ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price];
                }
            }

            $unitPrice = (float) $menu->price + $addonPrice;
            $lineSubtotal = $unitPrice * $qty;
            $subtotal += $lineSubtotal;

            $items[] = [
                'menu_id'  => $menu->id,
                'qty'      => $qty,
                'price'    => $unitPrice,
                'subtotal' => $lineSubtotal,
                'addons'   => $addonSnapshot ?: null,
                'notes'    => $row['note'] ?? ($row['notes'] ?? null),
                'status'   => 'pending',
            ];
        }

        // Diskon promo
        $discount = 0;
        if ($promoId) {
            $promo = Promo::where('id', $promoId)->where('is_active', true)->first();
            if ($promo) {
                $discount = $promo->discount_type === 'percentage'
                    ? round($subtotal * ($promo->discount_value / 100))
                    : (float) $promo->discount_value;
            }
        }

        $net = max(0, $subtotal - $discount);
        $setting = Setting::first();
        $taxRate = $setting ? (float) $setting->tax_rate : 0;
        $tax = round($net * ($taxRate / 100));
        $grand = $net + $tax;

        return [
            'items'           => $items,
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'tax'             => $tax,
            'grand_total'     => $grand,
        ];
    }

    /** Terapkan pembayaran (tunai/QRIS) ke order. Validasi uang tunai. */
    private function applyPayment(Order $order, string $method, $cashReceived = null): void
    {
        $data = [
            'payment_method' => $method,
            'payment_status' => 'paid',
            'cash_received'  => null,
            'change_amount'  => null,
        ];

        if ($method === 'cash') {
            $received = (float) $cashReceived;
            if ($received < (float) $order->grand_total) {
                throw new \RuntimeException('Uang tunai kurang dari total yang harus dibayar.');
            }
            $data['cash_received'] = $received;
            $data['change_amount'] = $received - (float) $order->grand_total;
        }

        $order->update($data);
    }

    /** Payload struk untuk ditampilkan modal/JS setelah checkout. */
    private function receiptPayload(Order $order): array
    {
        $setting = Setting::first();
        return [
            'store_name'     => $setting->store_name ?? 'Mooda',
            'invoice_no'     => $order->invoice_no,
            'queue_number'   => $order->queue_number,
            'customer_name'  => $order->customer_name,
            'datetime'       => optional($order->created_at)->format('d/m/Y H:i'),
            'items'          => $order->details->map(fn($d) => [
                'name'     => $d->menu->name ?? 'Menu',
                'qty'      => $d->qty,
                'price'    => (float) $d->price,
                'subtotal' => (float) $d->subtotal,
                'addons'   => $d->addons ?? [],
                'notes'    => $d->notes,
            ]),
            'subtotal'        => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'tax'             => (float) $order->tax,
            'grand_total'     => (float) $order->grand_total,
            'payment_method'  => $order->payment_method,
            'payment_status'  => $order->payment_status,
            'cash_received'   => $order->cash_received !== null ? (float) $order->cash_received : null,
            'change_amount'   => $order->change_amount !== null ? (float) $order->change_amount : null,
        ];
    }
}

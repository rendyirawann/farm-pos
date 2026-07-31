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
        // MODE HYBRID:
        // - OPERATOR: punya 'shift.operate' (kasir & owner) DAN shift MILIKNYA sedang terbuka
        //   -> POS penuh (menu/keranjang/bayar).
        // - PENINJAU: punya 'shift.reopen' (owner/admin) atau Superadmin, TANPA shift sendiri
        //   -> masuk mode LIHAT memakai shift yang sedang berjalan di toko.
        // - Kasir murni tanpa shift sendiri: ditolak (wajib buka shift dulu).
        $user = Auth::user();
        $canOperate = ! $user->isSuperadmin() && $user->can('shift.operate');
        $canReview  = $user->isSuperadmin() || $user->can('shift.reopen');

        $ownShift = $canOperate
            ? Shift::where('user_id', $user->id)->where('status', 'open')->first()
            : null;
        $isOperator  = (bool) $ownShift;
        $activeShift = $ownShift
            ?: ($canReview ? Shift::where('status', 'open')->latest('start_time')->first() : null);

        if (! $activeShift) {
            return redirect()->route('shifts.index')->with('warning', $canOperate
                ? '⚠️ Akses ditolak! Anda wajib membuka shift dan mengisi modal kasir terlebih dahulu.'
                : 'ℹ️ Belum ada shift kasir yang sedang berjalan. Layar kasir bisa dibuka saat ada shift aktif.');
        }

        $categories = Category::orderBy('name', 'asc')->get();
        // Urutan dasar: dari menu yang PALING AWAL dibuat ke yang terbaru (bukan alfabet).
        $menus = Menu::with('activeAddons')
            ->where('is_available', true)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Total terjual per menu (kecualikan pesanan void).
        $sold = \App\Models\OrderDetail::query()
            ->whereHas('order', fn ($q) => $q->whereNull('voided_at'))
            ->groupBy('menu_id')
            ->selectRaw('menu_id, SUM(qty) as total')
            ->pluck('total', 'menu_id');
        // Hanya TOP-6 TERLARIS (global, terjual > 0) yang naik ke paling atas (urut penjualan);
        // SISANYA tetap urutan pembuatan (lama -> baru). Menu dgn sedikit penjualan TIDAK ikut naik.
        $bestsellerIds = $sold->filter(fn ($t) => (int) $t > 0)->sortDesc()->take(6)->keys()
            ->filter(fn ($id) => $menus->contains('id', $id))->values()->all();
        $best = collect($bestsellerIds)->map(fn ($id) => $menus->firstWhere('id', $id));
        $rest = $menus->reject(fn ($m) => in_array($m->id, $bestsellerIds, true));
        $menus = $best->concat($rest)->values();
        $promos = Promo::where('is_active', true)->get();
        $setting = Setting::first();

        // Meja: dinamis (dari Manajemen Meja) untuk paket Enterprise+, selain itu statis 1..25.
        $tenant = app(TenantManager::class)->tenant();
        $useDynamicTables = \App\Tenancy\Plan::tenantAllows($tenant, 'tables');
        $diningTables = $useDynamicTables
            ? \App\Models\DiningTable::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        // Preferensi per-user: tampilkan display pilihan meja (default tampil).
        $showTables = (bool) (Auth::user()->kasir_show_tables ?? true);

        return view('backend.kasir.index', compact('categories', 'menus', 'promos', 'setting', 'activeShift', 'useDynamicTables', 'diningTables', 'isOperator', 'showTables', 'bestsellerIds'));
    }

    /**
     * Toggle tampil/sembunyi display pilihan meja di layar Kasir (per-user, via AJAX).
     */
    public function toggleTables(Request $request)
    {
        $request->validate(['show' => ['required', 'boolean']]);
        $user = Auth::user();
        $user->kasir_show_tables = $request->boolean('show');
        $user->save();

        return response()->json(['status' => 'success', 'show' => (bool) $user->kasir_show_tables]);
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

        // "Selesai sesi sebelumnya": bila shift aktif dibuka SEBELUM hari ini (melewati tengah
        // malam / dibuka lagi), tampilkan pesanan selesai milik sesi itu yang tercatat pada hari
        // sebelumnya, supaya tidak hilang saat pergantian tanggal. Read-only (tak mengubah kas).
        $isOperator = ! Auth::user()->isSuperadmin() && Auth::user()->can('shift.operate');
        $activeShift = $isOperator
            ? Shift::where('user_id', Auth::id())->where('status', 'open')->first()
            : Shift::where('status', 'open')->latest('start_time')->first();

        $previousCompleted = collect();
        if ($activeShift && Carbon::parse($activeShift->start_time)->lt(Carbon::today())) {
            $previousCompleted = Order::withCount('details')
                ->where('order_status', 'completed')
                ->where('created_at', '>=', $activeShift->start_time)
                ->where('created_at', '<', Carbon::today())
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get()
                ->map(fn($o) => $this->orderSummary($o));
        }

        return response()->json([
            'processing'          => $processing,
            'completed'           => $completed,
            // Jumlah pesanan SALAH di antara yang selesai hari ini (untuk kartu penanda).
            'voided_count'        => $completed->where('voided', true)->count(),
            'previous_completed'  => $previousCompleted->values(),
        ]);
    }

    private function orderSummary(Order $o): array
    {
        return [
            'id'             => $o->id,
            'invoice_no'     => $o->invoice_no,
            'queue_number'   => $o->queue_number,
            'customer_name'  => $o->customer_name,
            'table_no'       => $o->table_no,
            'grand_total'    => (float) $o->grand_total,
            'payment_status' => $o->payment_status,
            'order_status'   => $o->order_status,
            'items_count'    => $o->details_count,
            'created_at'     => optional($o->created_at)->format('H:i'),
            'voided'         => $o->voided_at !== null, // ditandai SALAH (tak dihitung omzet)
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
                // detail_id dipakai fitur Split Bill (memilih baris item yang dipindah).
                'detail_id' => $d->id,
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
            'cart'                => 'required|array|min:1',
            'cart.*.menu_id'      => 'required|integer',
            'cart.*.qty'          => 'required|integer|min:1',
            'cart.*.addons'       => 'nullable|array',
            'cart.*.addons.*.id'  => 'required_with:cart.*.addons|integer',
            'cart.*.addons.*.qty' => 'required_with:cart.*.addons|integer|min:1',
            'cart.*.addon_ids'    => 'nullable|array',
            'payment_method'      => 'nullable|in:cash,qris',
            'client_txn_id'       => 'nullable|string|max:64',
        ]);

        $clientTxnId = $request->input('client_txn_id');

        // IDEMPOTENSI: bila percobaan sebelumnya sudah membuat pesanan dengan kunci transaksi
        // yang sama (mis. respons hilang karena jaringan lalu klien mencoba lagi / sinkron offline),
        // kembalikan pesanan yang SUDAH ada — jangan buat baru. Cegah dobel.
        if ($clientTxnId) {
            $existing = Order::where('client_txn_id', $clientTxnId)->first();
            if ($existing) {
                return response()->json($this->storeOrderSuccessPayload($existing, true));
            }
        }

        try {
            DB::beginTransaction();

            $built = $this->buildOrderFromCart($request->cart, $request->promo_id);

            $order = Order::create(array_merge(
                $this->newOrderBase($request->input('customer_name')),
                [
                    'client_txn_id'   => $clientTxnId,
                    'table_no'        => $request->input('table_no'),
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

            return response()->json($this->storeOrderSuccessPayload($order->fresh('details.menu'), false));
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            // Balapan dua request identik: unique (tenant_id, client_txn_id) dilanggar.
            // Ambil pesanan yang menang, kembalikan sebagai sukses -> tetap SATU pesanan.
            if ($clientTxnId && $e->getCode() === '23505') {
                $existing = Order::where('client_txn_id', $clientTxnId)->first();
                if ($existing) {
                    return response()->json($this->storeOrderSuccessPayload($existing, true));
                }
            }
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /** Payload sukses store order (dipakai jalur normal maupun idempoten). */
    private function storeOrderSuccessPayload(Order $order, bool $idempotent): array
    {
        return [
            'success'    => true,
            'order_id'   => $order->id,
            'paid'       => $order->payment_status === 'paid',
            'print_url'  => route('kasir.print', $order->id),
            'receipt'    => $this->receiptPayload($order->fresh('details.menu')),
            'idempotent' => $idempotent,
        ];
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
     * Tambah/gabung item ke pesanan yang MASIH BELUM LUNAS (view -> tambah menu -> merge).
     * GUARD server-side: hanya payment_status=unpaid, order masih diproses, & tidak void.
     * Total dihitung ULANG holistik (subtotal SEMUA item -> promo -> pajak) agar promo%/pajak benar.
     */
    public function addItems($id, Request $request)
    {
        $request->validate([
            'cart'                => 'required|array|min:1',
            'cart.*.menu_id'      => 'required|integer',
            'cart.*.qty'          => 'required|integer|min:1',
            'cart.*.addons'       => 'nullable|array',
            'cart.*.addons.*.id'  => 'required_with:cart.*.addons|integer',
            'cart.*.addons.*.qty' => 'required_with:cart.*.addons|integer|min:1',
            'cart.*.addon_ids'    => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();
            $order = Order::findOrFail($id); // ter-scope tenant otomatis

            // GUARD (jangan andalkan UI): pesanan lunas = grand_total sudah masuk kas/omzet,
            // tak boleh diubah. Hanya pesanan BELUM LUNAS, masih diproses, & tidak void.
            if ($order->payment_status === 'paid') {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Pesanan sudah lunas — tidak bisa menambah item.'], 422);
            }
            if (! in_array($order->order_status, ['pending', 'cooking', 'served'], true)) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Pesanan sudah selesai — tidak bisa menambah item.'], 422);
            }
            if ($order->voided_at !== null) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Pesanan ditandai salah — tidak bisa menambah item.'], 422);
            }

            // Item BARU dihargai dari DB (anti-hack) lalu ditempel ke order yang sama.
            $newItems = $this->buildCartItems($request->cart)['items'];
            if (empty($newItems)) {
                DB::rollBack();
                return response()->json(['success' => false, 'error' => 'Tidak ada item valid untuk ditambahkan.'], 422);
            }
            foreach ($newItems as $item) {
                $order->details()->create($item);
            }

            // RECOMPUTE HOLISTIK: subtotal = jumlah SEMUA item (lama + baru); promo & pajak
            // dihitung ulang atas subtotal baru (bukan tambah delta -> benar utk promo%/pajak).
            $order->load('details');
            $subtotal = (float) $order->details->sum('subtotal');
            $totals = $this->applyPromoAndTax($subtotal, $order->promo_id);

            // FIX dapur: item baru berstatus 'pending'. Hitung ulang order_status dari status
            // item (sama seperti KitchenController) agar pesanan yang tadinya 'served' kembali
            // ke antrian dapur ("Sedang Dibuat") untuk item tambahan — bukan tetap "Selesai".
            $statuses   = $order->details->pluck('status');
            $totalItems = $statuses->count();
            $doneItems  = $statuses->filter(fn ($s) => $s === 'done')->count();
            if ($totalItems > 0 && $doneItems === $totalItems) {
                $newOrderStatus = 'served';
            } elseif ($statuses->contains('cooking') || $doneItems > 0) {
                $newOrderStatus = 'cooking';
            } else {
                $newOrderStatus = 'pending';
            }

            $order->update([
                'subtotal'        => $subtotal,
                'discount_amount' => (int) $totals['discount_amount'],
                'tax'             => $totals['tax'],
                'grand_total'     => $totals['grand_total'],
                'order_status'    => $newOrderStatus,
            ]);
            // $order->update() -> OrderObserver::updated -> OrderChanged (layar Dapur & Kasir refetch).

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
     * Hapus 1 pesanan yang MASIH BERJALAN (khusus OWNER — hanya tab "Sedang Diproses").
     * Pesanan yang sudah SELESAI TIDAK boleh dihapus (harus tetap terekam & auditable) —
     * untuk pesanan selesai yang salah, gunakan "Tandai Salah" (void). order_details ikut
     * terhapus (cascade FK).
     */
    public function destroyOrder($id)
    {
        abort_unless(auth()->user()->can('order.delete'), 403);

        try {
            DB::beginTransaction();
            $order = Order::findOrFail($id); // ter-scope per-tenant

            // Server-side guard (jangan cuma andalkan UI): HANYA pesanan berjalan yang boleh dihapus.
            // Pesanan 'completed' harus dipertahankan demi audit -> pakai Tandai Salah (voidOrder).
            if (! in_array($order->order_status, ['pending', 'cooking', 'served'], true)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error'   => 'Pesanan yang sudah selesai tidak boleh dihapus. Gunakan "Tandai Salah".',
                ], 422);
            }

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
     * Tandai / batalkan tanda "SALAH" pada pesanan SELESAI (OWNER + KASIR).
     * Pesanan salah TIDAK dihitung ke omzet & kas laci (refund penuh), tetapi
     * TETAP tersimpan & tampil di laporan dengan penanda "SALAH". Bersifat toggle
     * agar salah-tandai bisa dibatalkan.
     */
    public function voidOrder($id)
    {
        abort_unless(auth()->user()->can('order.void'), 403);

        try {
            DB::beginTransaction();
            $order = Order::findOrFail($id); // ter-scope per-tenant

            // Hanya pesanan yang SUDAH SELESAI yang bisa ditandai salah (fitur tab "Selesai").
            if ($order->order_status !== 'completed') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error'   => 'Hanya pesanan yang sudah selesai yang bisa ditandai salah.',
                ], 422);
            }

            if ($order->voided_at === null) {
                $order->voided_at = now();
                $order->voided_by = auth()->id();
            } else {
                // Batalkan tanda salah.
                $order->voided_at = null;
                $order->voided_by = null;
            }
            $order->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'voided'  => $order->voided_at !== null,
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

        // Bersihkan pemisah ribuan (mis. "50.000" / "50,000" -> "50000") sebelum validasi,
        // agar tidak salah dibaca sebagai desimal (50.000 -> 50).
        $request->merge(['amount' => preg_replace('/\D/', '', (string) $request->input('amount'))]);

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
            ->whereNull('voided_at') // pesanan ditandai salah tidak dihitung ke omzet
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
                $clientTxnId = $offline['client_txn_id'] ?? null;

                // Dedupe: bila pesanan ini sudah masuk (via kunci transaksi klien ATAU nomor invoice
                // offline yang sama), lewati -> cegah dobel saat sinkron ulang / request sempat sampai.
                if ($clientTxnId && Order::where('client_txn_id', $clientTxnId)->exists()) {
                    continue;
                }
                if (!empty($offline['invoice_no']) && Order::where('invoice_no', $offline['invoice_no'])->exists()) {
                    continue;
                }

                $built = $this->buildOrderFromCart($offline['cart'] ?? [], $offline['promo_id'] ?? null);

                $base = $this->newOrderBase($offline['customer_name'] ?? null);
                if (!empty($offline['invoice_no'])) {
                    $base['invoice_no'] = $offline['invoice_no'];
                }

                $order = Order::create(array_merge($base, [
                    'client_txn_id'   => $clientTxnId,
                    'table_no'        => $offline['table_no'] ?? null,
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

                    // Plan deposit: POTONG fee transaksi (sama seperti completeOrder) — cegah
                    // transaksi offline jadi "gratis" tanpa potong poin. Saldo tak cukup -> batalkan
                    // seluruh sinkron (transaksi) supaya tidak ada layanan tak terbayar.
                    $tenant = app(TenantManager::class)->tenant();
                    if ($tenant && $tenant->isDepositMode()) {
                        $tenant->refresh(); // saldo terkini (order sebelumnya di batch mungkin sudah memotong)
                        $fee = DepositConfig::feePerTransaction();
                        if ((float) $tenant->deposit_points < $fee) {
                            throw new \RuntimeException('Saldo deposit tidak cukup untuk menyinkronkan transaksi offline (sisa Rp'
                                . number_format($tenant->deposit_points, 0, ',', '.') . '). Silakan top up dulu.');
                        }
                        app(DepositService::class)->deduct(
                            $tenant, $fee, 'usage', $order->uuid, Auth::id(),
                            'Biaya transaksi (offline) ' . ($order->invoice_no ?? ('#' . $order->id))
                        );
                    }

                    // Transaksi lunas & selesai -> masuk tab "Selesai".
                    $order->update(['order_status' => 'completed']);
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
        $tenantId = app(TenantManager::class)->id() ?? 0;

        // Advisory lock per tenant+tanggal (transaction-scoped, auto-release saat commit/rollback):
        // serialkan generate nomor antrian antar kasir yang buat pesanan bersamaan -> cegah
        // queue_number kembar. Dipanggil di dalam transaksi (storeOrder & syncOfflineOrders).
        $lockKey = crc32('order-queue-' . $tenantId . '-' . Carbon::today()->toDateString());
        DB::select('SELECT pg_advisory_xact_lock(?)', [$lockKey]);

        $nextQueue = (int) (Order::whereDate('created_at', Carbon::today())->max('queue_number')) + 1;

        return [
            'invoice_no'    => $this->generateInvoiceNo(),
            'queue_number'  => $nextQueue,
            'customer_name' => $customerName ?: 'Pelanggan',
        ];
    }

    /**
     * Invoice unik. Kolom invoice_no punya UNIQUE constraint GLOBAL, jadi pakai entropi tinggi
     * (Str::random) + cek keberadaan LINTAS-tenant -> praktis tak pernah tabrakan, sehingga
     * tidak lagi 500 saat 2 kasir membuat pesanan pada detik yang sama. Unique DB = jaring pengaman.
     */
    private function generateInvoiceNo(): string
    {
        do {
            $invoice = 'MDA-INV-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (Order::withoutGlobalScopes()->where('invoice_no', $invoice)->exists());

        return $invoice;
    }

    /**
     * Hitung ulang keranjang di server (anti-hack): ambil harga menu & add-ons dari DB.
     * cart item: {menu_id, qty, addon_ids[], note}
     */
    /** Bangun item pesanan dari keranjang (harga menu+addon dari DB, anti-hack). Kembalikan items + subtotal. */
    private function buildCartItems(array $cart): array
    {
        $subtotal = 0;
        $items = [];

        foreach ($cart as $row) {
            $menu = Menu::find($row['menu_id']);
            if (!$menu) {
                continue;
            }
            $qty = max(1, (int) ($row['qty'] ?? 1));

            // Add-on: format baru [{id,qty}] (qty per add-on, LEPAS dari qty menu).
            // Fallback ke format lama addon_ids[] (qty=1/add-on) untuk payload/offline lama.
            $addonQtyById = [];
            foreach ((array) ($row['addons'] ?? []) as $ad) {
                $aid = (int) ($ad['id'] ?? 0);
                if ($aid <= 0) {
                    continue;
                }
                $addonQtyById[$aid] = max(1, (int) ($ad['qty'] ?? 1));
            }
            if (empty($addonQtyById)) {
                foreach (array_filter(array_map('intval', (array) ($row['addon_ids'] ?? []))) as $aid) {
                    $addonQtyById[$aid] = 1;
                }
            }

            $addonSnapshot = [];
            $addonPrice = 0; // Σ(harga add-on × qty add-on)
            if (!empty($addonQtyById)) {
                $addons = MenuAddon::where('menu_id', $menu->id)
                    ->whereIn('id', array_keys($addonQtyById))
                    ->where('is_active', true)
                    ->get();
                foreach ($addons as $a) {
                    $aQty = $addonQtyById[$a->id] ?? 1;
                    $addonPrice += (float) $a->price * $aQty;
                    $addonSnapshot[] = ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price, 'qty' => $aQty];
                }
            }

            // MODEL HARGA: (menu × qtyMenu) + Σ(add-on × qtyAddon). Add-on TIDAK ikut qty menu.
            $menuPrice = (float) $menu->price;
            $lineSubtotal = $menuPrice * $qty + $addonPrice;
            $subtotal += $lineSubtotal;

            $items[] = [
                'menu_id'  => $menu->id,
                'qty'      => $qty,
                'price'    => $menuPrice, // harga menu saja; add-on ada di snapshot + subtotal
                'subtotal' => $lineSubtotal,
                'addons'   => $addonSnapshot ?: null,
                'notes'    => $row['note'] ?? ($row['notes'] ?? null),
                'status'   => 'pending',
            ];
        }

        return ['items' => $items, 'subtotal' => $subtotal];
    }

    /** Hitung diskon promo + pajak + grand_total dari subtotal. Dipakai store DAN add-items (konsisten). */
    private function applyPromoAndTax(float $subtotal, $promoId): array
    {
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

        return [
            'discount_amount' => $discount,
            'tax'             => $tax,
            'grand_total'     => $net + $tax,
        ];
    }

    /**
     * Hitung ulang keranjang di server (anti-hack): item + subtotal + diskon + pajak + grand.
     * cart item: {menu_id, qty, addon_ids[], note}
     */
    private function buildOrderFromCart(array $cart, $promoId): array
    {
        $built = $this->buildCartItems($cart);

        return array_merge($built, $this->applyPromoAndTax((float) $built['subtotal'], $promoId));
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
            'table_no'       => $order->table_no,
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

    /**
     * SPLIT BILL — pecah pesanan BELUM LUNAS menjadi 2 nota.
     * Item (atau sebagian qty-nya) yang dipilih DIPINDAH ke nota baru; sisanya tetap di nota asal.
     * Kedua nota dihitung ulang server-side (subtotal/diskon/pajak) supaya angka tak bisa dimanipulasi.
     *
     * Catatan: hanya untuk pesanan yang BELUM dibayar. Item yang stoknya sudah dipotong
     * (is_stock_deducted) tetap membawa HPP-nya secara proporsional agar laporan tetap akurat.
     */
    public function splitOrder($id, Request $request)
    {
        $data = $request->validate([
            'items'             => ['required', 'array', 'min:1'],
            'items.*.detail_id' => ['required', 'integer'],
            'items.*.qty'       => ['required', 'integer', 'min:1'],
            'customer_name'     => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $result = DB::transaction(function () use ($id, $data) {
                /** @var Order $order */
                $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($order->payment_status === 'paid') {
                    throw new \RuntimeException('Pesanan sudah lunas — tidak bisa dipecah.');
                }

                $details = $order->details()->get()->keyBy('id');
                $moved   = [];

                foreach ($data['items'] as $row) {
                    $d = $details->get((int) $row['detail_id']);
                    if (! $d) {
                        continue;
                    }
                    $takeQty = min((int) $row['qty'], (int) $d->qty);
                    if ($takeQty < 1) {
                        continue;
                    }
                    $moved[] = ['detail' => $d, 'qty' => $takeQty];
                }

                if (empty($moved)) {
                    throw new \RuntimeException('Tidak ada item yang dipilih.');
                }

                // Jangan biarkan SELURUH item pindah (nota asal tak boleh kosong).
                $totalQty = (int) $details->sum('qty');
                $movedQty = array_sum(array_column($moved, 'qty'));
                if ($movedQty >= $totalQty) {
                    throw new \RuntimeException('Tidak bisa memindahkan semua item. Sisakan minimal 1 item di nota asal.');
                }

                // Nota baru: meja & promo mengikuti nota asal.
                $newOrder = Order::create(array_merge($this->newOrderBase($data['customer_name'] ?? $order->customer_name), [
                    'table_no'       => $order->table_no,
                    'promo_id'       => $order->promo_id,
                    'order_status'   => $order->order_status,
                    'payment_status' => 'unpaid',
                    'subtotal'       => 0,
                    'discount_amount' => 0,
                    'tax'            => 0,
                    'grand_total'    => 0,
                    'shift_id'       => $order->shift_id,
                ]));

                foreach ($moved as $m) {
                    /** @var \App\Models\OrderDetail $d */
                    $d       = $m['detail'];
                    $takeQty = $m['qty'];
                    $unit    = (float) $d->price;
                    // HPP dipindah proporsional (bila stok sudah dipotong di dapur).
                    $hppUnit = (int) $d->qty > 0 ? (float) $d->hpp / (int) $d->qty : 0;

                    $newOrder->details()->create([
                        'menu_id'           => $d->menu_id,
                        'qty'               => $takeQty,
                        'price'             => $unit,
                        'subtotal'          => round($unit * $takeQty, 2),
                        'addons'            => $d->addons,
                        'notes'             => $d->notes,
                        'status'            => $d->status,
                        'hpp'               => round($hppUnit * $takeQty, 2),
                        'is_stock_deducted' => (bool) $d->is_stock_deducted,
                    ]);

                    $leftQty = (int) $d->qty - $takeQty;
                    if ($leftQty > 0) {
                        $d->update([
                            'qty'      => $leftQty,
                            'subtotal' => round($unit * $leftQty, 2),
                            'hpp'      => round($hppUnit * $leftQty, 2),
                        ]);
                    } else {
                        $d->delete();
                    }
                }

                $this->recalcOrderTotals($order->fresh());
                $this->recalcOrderTotals($newOrder->fresh());

                return ['from' => $order->fresh(), 'to' => $newOrder->fresh()];
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Nota berhasil dipecah.',
            'original' => $this->orderSummary($result['from']),
            'new'      => $this->orderSummary($result['to']),
        ]);
    }

    /**
     * MERGE TABLE — gabungkan beberapa pesanan BELUM LUNAS ke satu nota tujuan.
     * Item digabung (qty ditambahkan bila menu+addons+catatan sama), nota sumber dihapus.
     */
    public function mergeOrders(Request $request)
    {
        $data = $request->validate([
            'target_id'  => ['required', 'integer'],
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['integer'],
            'table_no'   => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $target = DB::transaction(function () use ($data) {
                /** @var Order $target */
                $target = Order::whereKey($data['target_id'])->lockForUpdate()->firstOrFail();
                if ($target->payment_status === 'paid') {
                    throw new \RuntimeException('Nota tujuan sudah lunas — tidak bisa digabung.');
                }

                $sources = Order::whereIn('id', $data['source_ids'])
                    ->where('id', '!=', $target->id)
                    ->lockForUpdate()->get();

                if ($sources->isEmpty()) {
                    throw new \RuntimeException('Tidak ada nota sumber yang valid.');
                }
                foreach ($sources as $s) {
                    if ($s->payment_status === 'paid') {
                        throw new \RuntimeException('Nota ' . $s->invoice_no . ' sudah lunas — tidak bisa digabung.');
                    }
                }

                foreach ($sources as $src) {
                    foreach ($src->details()->get() as $d) {
                        // Cari baris identik di nota tujuan -> gabungkan qty.
                        $same = $target->details()
                            ->where('menu_id', $d->menu_id)
                            ->where('price', $d->price)
                            ->where('status', $d->status)
                            ->get()
                            ->first(fn ($t) => json_encode($t->addons) === json_encode($d->addons)
                                && (string) $t->notes === (string) $d->notes
                                && (bool) $t->is_stock_deducted === (bool) $d->is_stock_deducted);

                        if ($same) {
                            $same->update([
                                'qty'      => (int) $same->qty + (int) $d->qty,
                                'subtotal' => (float) $same->subtotal + (float) $d->subtotal,
                                'hpp'      => (float) $same->hpp + (float) $d->hpp,
                            ]);
                            $d->delete();
                        } else {
                            $d->update(['order_id' => $target->id]);
                        }
                    }

                    // Nota sumber sudah kosong -> hapus (belum lunas, jadi tak mengubah omzet).
                    $src->delete();
                }

                if (! empty($data['table_no'])) {
                    $target->update(['table_no' => $data['table_no']]);
                }

                $this->recalcOrderTotals($target->fresh());

                return $target->fresh();
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nota berhasil digabung.',
            'order'   => $this->orderSummary($target),
        ]);
    }

    /** Hitung ulang subtotal/diskon/pajak/grand sebuah pesanan dari item-itemnya. */
    private function recalcOrderTotals(Order $order): void
    {
        $subtotal = (float) $order->details()->sum('subtotal');
        $totals   = $this->applyPromoAndTax($subtotal, $order->promo_id);

        $order->update(array_merge(['subtotal' => $subtotal], $totals));
    }
}

<?php

namespace App\Http\Controllers\Backend\Laundry;

use App\Http\Controllers\Controller;
use App\Models\Laundry\LaundryCustomer;
use App\Models\Laundry\LaundryOrder;
use App\Models\Laundry\LaundryService;
use App\Models\Laundry\LaundryStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kasir Laundry: board pesanan aktif + buat nota + pembayaran + serah-terima + struk.
 * Alur status: diterima -> dicuci -> ... -> selesai -> diambil (advance di Produksi).
 */
class LaundryKasirController extends Controller
{
    /** Board pesanan aktif (belum diambil). */
    public function index()
    {
        $orders = LaundryOrder::with('items')
            ->whereIn('order_status', array_merge(LaundryOrder::ACTIVE_STATUSES, ['selesai']))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $today = now()->startOfDay();

        return view('backend.laundry.kasir.index', [
            'orders'          => $orders,
            'stageLabels'     => LaundryOrder::STAGE_LABELS,
            'countActive'     => LaundryOrder::whereIn('order_status', LaundryOrder::ACTIVE_STATUSES)->count(),
            'countReady'      => LaundryOrder::where('order_status', 'selesai')->count(),
            'revenueToday'    => (float) LaundryOrder::where('payment_status', 'paid')->where('created_at', '>=', $today)->sum('grand_total'),
        ]);
    }

    /** Halaman POS buat nota. */
    public function create()
    {
        return view('backend.laundry.kasir.create', [
            'services'  => LaundryService::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'customers' => LaundryCustomer::orderBy('name')->get(['id', 'name', 'phone', 'email', 'address', 'member_status']),
            // Panel status di layar kasir (Sedang Diproses / Selesai).
            'activeOrders' => LaundryOrder::whereIn('order_status', LaundryOrder::ACTIVE_STATUSES)
                ->orderByDesc('created_at')->limit(50)->get(),
            'readyOrders'  => LaundryOrder::where('order_status', 'selesai')
                ->orderByDesc('created_at')->limit(50)->get(),
            'setting'      => \App\Models\Setting::query()->first(),
        ]);
    }

    /** Simpan nota baru (harga dihitung server-side, anti-manipulasi). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cart'                 => ['required', 'array', 'min:1'],
            'cart.*.service_id'    => ['required', 'integer'],
            'cart.*.qty'           => ['required', 'numeric', 'min:0.01'],
            'cart.*.notes'         => ['nullable', 'string', 'max:255'],
            'cart.*.item_condition' => ['nullable', 'string', 'max:500'],
            'customer_id'          => ['nullable', 'integer'],
            'customer_name'        => ['nullable', 'string', 'max:100'],
            'customer_phone'       => ['nullable', 'string', 'max:30'],
            'customer_email'       => ['nullable', 'email', 'max:120'],
            // Simpan pelanggan baru ke Data Master supaya bisa dipilih di nota berikutnya.
            'save_customer'        => ['nullable', 'boolean'],
            'order_type'           => ['required', 'in:self_pickup,delivery'],
            'delivery_fee'         => ['nullable', 'numeric', 'min:0'],
            'delivery_address'     => ['nullable', 'string', 'max:255'],
            'special_instructions' => ['nullable', 'string', 'max:500'],
            'payment_method'       => ['required', 'in:cash,nanti'],
            'cash_received'        => ['nullable', 'numeric', 'min:0'],
        ]);

        // Ambil ulang layanan (tenant-scoped) dalam 1 query -> hitung server-side.
        $ids      = collect($data['cart'])->pluck('service_id')->unique()->all();
        $services = LaundryService::whereIn('id', $ids)->get()->keyBy('id');

        $customer = ! empty($data['customer_id']) ? LaundryCustomer::find($data['customer_id']) : null;

        // Pelanggan BARU dari layar kasir: simpan ke Data Master bila diminta (registrasi cepat).
        if (! $customer && ! empty($data['save_customer']) && ! empty($data['customer_name'])) {
            $customer = LaundryCustomer::firstOrCreate(
                ['phone' => $data['customer_phone'] ?? null, 'name' => $data['customer_name']],
                [
                    'email'          => $data['customer_email'] ?? null,
                    'member_status'  => 'reguler',
                    'loyalty_points' => 0,
                ]
            );
        }

        try {
            $order = DB::transaction(function () use ($data, $services, $customer) {
                $subtotal = 0;
                $maxHours = 0;
                $lines    = [];

                foreach ($data['cart'] as $row) {
                    $svc = $services[$row['service_id']] ?? null;
                    if (! $svc) {
                        continue;
                    }
                    $qty  = round((float) $row['qty'], 2);
                    $line = round($qty * (float) $svc->price_per_unit, 2);
                    $subtotal += $line;
                    $maxHours = max($maxHours, (int) $svc->estimated_duration_hours);
                    $lines[] = [
                        'service_id'     => $svc->id,
                        'service_name'   => $svc->name,
                        'unit'           => $svc->unit,
                        'qty'            => $qty,
                        'price'          => (float) $svc->price_per_unit,
                        'subtotal'       => $line,
                        'notes'          => $row['notes'] ?? null,
                        'item_condition' => $row['item_condition'] ?? null,
                        'status'         => 'entry',
                    ];
                }

                if (empty($lines)) {
                    throw new \RuntimeException('Keranjang kosong / layanan tidak valid.');
                }

                // Diskon: VIP 10% otomatis. Pajak dari persen di Pengaturan, dihitung atas
                // nilai SETELAH diskon (sesuai spesifikasi modul laundry). Ongkir bila delivery.
                $discount = ($customer && $customer->isVip()) ? round($subtotal * 0.10, 2) : 0;
                $net      = max(0, $subtotal - $discount);
                $taxRate  = (float) (\App\Models\Setting::query()->value('tax_rate') ?? 0);
                $tax      = $taxRate > 0 ? round($net * $taxRate / 100, 2) : 0;
                $delivery = ($data['order_type'] === 'delivery') ? round((float) ($data['delivery_fee'] ?? 0), 2) : 0;
                $grand    = $net + $tax + $delivery;

                // Pembayaran
                $method  = $data['payment_method'];
                $paid    = $method === 'cash';
                $cashRcv = $paid ? (float) ($data['cash_received'] ?? $grand) : null;
                if ($paid && $cashRcv < $grand) {
                    throw new \RuntimeException('Uang tunai kurang dari total.');
                }

                $order = LaundryOrder::create([
                    'invoice_no'             => 'LDY-' . date('YmdHis') . mt_rand(10, 99),
                    'customer_id'            => $customer?->id,
                    'customer_name'          => $customer?->name ?: ($data['customer_name'] ?: 'Pelanggan'),
                    'customer_phone'         => $customer?->phone ?: ($data['customer_phone'] ?? null),
                    'customer_email'         => $customer?->email ?: ($data['customer_email'] ?? null),
                    'staff_id'               => Auth::id(),
                    'order_type'             => $data['order_type'],
                    'delivery_address'       => $data['order_type'] === 'delivery' ? ($data['delivery_address'] ?? $customer?->address) : null,
                    'delivery_fee'           => $delivery,
                    'subtotal'               => $subtotal,
                    'discount_amount'        => $discount,
                    'tax'                    => $tax,
                    'grand_total'            => $grand,
                    'payment_method'         => $paid ? 'cash' : null,
                    'payment_status'         => $paid ? 'paid' : 'unpaid',
                    'dp_amount'              => $paid ? $grand : 0,
                    'cash_received'          => $cashRcv,
                    'cash_change'            => $paid ? max(0, $cashRcv - $grand) : null,
                    'order_status'           => 'diterima',
                    'special_instructions'   => $data['special_instructions'] ?? null,
                    'estimated_completed_at' => now()->addHours($maxHours ?: 48),
                ]);

                foreach ($lines as $l) {
                    $order->items()->create($l);
                }

                LaundryStatusLog::create([
                    'order_id'   => $order->id,
                    'status'     => 'diterima',
                    'changed_by' => Auth::id(),
                    'notes'      => 'Cucian diterima di kasir.',
                ]);

                // Loyalty: 1 poin / Rp10.000 (khusus pelanggan terdaftar).
                if ($customer && $grand >= 10000) {
                    $customer->increment('loyalty_points', (int) floor($grand / 10000));
                }

                return $order;
            });
        } catch (\Throwable $e) {
            Log::error('Laundry store order gagal: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan nota: ' . $e->getMessage()], 422);
        }

        return response()->json([
            'status'     => 'success',
            'order_id'   => $order->id,
            'invoice_no' => $order->invoice_no,
            'print_url'  => route('laundry.kasir.print', $order->id),
            // Data struk untuk engine cetak terpusat (browser / QZ Tray / Web Bluetooth / RawBT).
            'receipt'    => $this->receiptPayload($order),
        ]);
    }

    /**
     * Bentuk data struk (format sama dgn engine MoodaPrint di F&B) supaya struk laundry
     * bisa dicetak lewat printer thermal Bluetooth/QZ/RawBT, bukan hanya dialog browser.
     */
    private function receiptPayload(LaundryOrder $order): array
    {
        $order->loadMissing('items');
        $setting = \App\Models\Setting::query()->first();
        $taxRate = (float) ($setting->tax_rate ?? 0);

        return [
            'store_name'     => $setting->store_name ?? (Auth::user()->tenant->name ?? 'Mooda'),
            'store_address'  => ($setting && $setting->receipt_show_address) ? ($setting->address ?? '') : '',
            'store_phone'    => ($setting && $setting->receipt_show_phone) ? ($setting->phone ?? '') : '',
            'receipt_header' => $setting->receipt_header ?? '',
            'receipt_footer' => $setting->receipt_footer ?? '',
            'invoice_no'     => $order->invoice_no,
            'customer_name'  => $order->customer_name,
            'datetime'       => optional($order->created_at)->format('d/m/Y H.i'),
            'items'          => $order->items->map(fn ($it) => [
                'name'     => $it->service_name . ' (' . rtrim(rtrim(number_format((float) $it->qty, 2, '.', ''), '0'), '.') . ' ' . $it->unit . ')',
                'qty'      => (float) $it->qty,
                'price'    => (float) $it->price,
                'subtotal' => (float) $it->subtotal,
                'notes'    => trim(($it->item_condition ? $it->item_condition : '') . ($it->notes ? ' | ' . $it->notes : '')) ?: null,
            ])->all(),
            'subtotal'        => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'tax'             => (float) $order->tax,
            'tax_rate'        => $taxRate,
            'grand_total'     => (float) $order->grand_total,
            'payment_method'  => $order->payment_method ?: 'nanti',
            'payment_status'  => $order->payment_status,
            'cash_received'   => (float) ($order->cash_received ?? 0),
            'change_amount'   => (float) ($order->cash_change ?? 0),
            // Khas laundry: estimasi selesai dicetak di struk.
            'note_line'       => 'Estimasi selesai: ' . optional($order->estimated_completed_at)->format('d/m/Y H.i'),
        ];
    }

    /** Lunasi sisa (dari status unpaid/DP). */
    public function pay(Request $request, LaundryOrder $order)
    {
        abort_if($order->payment_status === 'paid', 422, 'Pesanan sudah lunas.');
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'dp_amount'      => $order->grand_total,
        ]);
        return back()->with('success', 'Pembayaran ' . $order->invoice_no . ' dilunasi.');
    }

    /** Serahkan cucian ke pelanggan (status -> diambil). Wajib lunas. */
    public function handover(LaundryOrder $order)
    {
        abort_unless($order->order_status === 'selesai', 422, 'Cucian belum selesai.');
        abort_unless($order->isPaid(), 422, 'Pembayaran belum lunas.');

        DB::transaction(function () use ($order) {
            $order->update(['order_status' => 'diambil', 'picked_up_at' => now()]);
            LaundryStatusLog::create([
                'order_id'   => $order->id,
                'status'     => 'diambil',
                'changed_by' => Auth::id(),
                'notes'      => 'Cucian diserahkan ke pelanggan.',
            ]);
        });

        return back()->with('success', 'Cucian ' . $order->invoice_no . ' telah diserahkan.');
    }

    /** Struk thermal. */
    public function print(LaundryOrder $order)
    {
        $order->load('items');
        return view('backend.laundry.kasir.print', [
            'order'  => $order,
            'tenant' => Auth::user()->tenant,
        ]);
    }
}

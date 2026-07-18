<?php

namespace App\Http\Controllers\Backend\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Expense;
use App\Models\Setting;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class SalesReportController extends Controller
{
    public function index()
    {
        return view('backend.reports.sales.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = Order::with(['promo'])->where('payment_status', 'paid');

            // Filter Rentang Tanggal
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            // Filter Metode Pembayaran
            if ($request->payment_method && $request->payment_method != 'all') {
                $query->where('payment_method', $request->payment_method);
            }

            // Hitung ringkasan (Summary) dalam 1 query agregat.
            // Pesanan SALAH (voided) DIKECUALIKAN dari omzet, jumlah nota, & diskon —
            // tetapi dihitung terpisah untuk kartu "Pesanan Salah". FILTER = sintaks Postgres.
            $summary = (clone $query)->toBase()
                ->selectRaw("
                    COUNT(*) FILTER (WHERE voided_at IS NULL) as total_orders,
                    COALESCE(SUM(grand_total) FILTER (WHERE voided_at IS NULL), 0) as total_revenue,
                    COALESCE(SUM(discount_amount) FILTER (WHERE voided_at IS NULL), 0) as total_discount,
                    COUNT(*) FILTER (WHERE voided_at IS NOT NULL) as voided_count,
                    COALESCE(SUM(grand_total) FILTER (WHERE voided_at IS NOT NULL), 0) as voided_amount
                ")
                ->first();
            $totalRevenue  = $summary->total_revenue;   // omzet TANPA pesanan salah
            $totalDiscount = $summary->total_discount;
            $totalOrders   = $summary->total_orders;
            $voidedCount   = $summary->voided_count;     // jumlah pesanan salah
            $voidedAmount  = $summary->voided_amount;    // nominal pesanan salah (tak dihitung)

            // Pengeluaran diambil dari KAS/UANG TUNAI (laci) -> hanya relevan untuk tampilan
            // Tunai atau Semua Metode. Untuk filter QRIS: pengeluaran TIDAK ditampilkan &
            // TIDAK dikurangkan (Omzet Bersih QRIS = Pendapatan QRIS).
            $expenseApplies = ($request->payment_method !== 'qris');
            $totalExpense = 0.0;
            if ($expenseApplies) {
                $expenseQuery = Expense::query();
                if ($request->start_date && $request->end_date) {
                    // Basis kolom `date` (tanggal yang DIISI user), bukan created_at -> pengeluaran
                // dihitung pada tanggal yang dimaksud kasir walau dicatat lewat tengah malam.
                $expenseQuery->whereBetween('date', [$request->start_date, $request->end_date]);
                }
                $totalExpense = (float) $expenseQuery->sum('amount');
            }
            $netRevenue = $totalRevenue - $totalExpense;

            // Urutkan dari yang terbaru
            $query->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('date', function ($row) {
                    return Carbon::parse($row->created_at)->translatedFormat('d M Y H:i');
                })
                ->addColumn('invoice', function ($row) {
                    return '<span class="fw-bold text-primary">#' . $row->invoice_no . '</span>';
                })
                ->addColumn('customer', function ($row) {
                    $queue = $row->queue_number ? ' (No. Antrian ' . $row->queue_number . ')' : '';
                    return e($row->customer_name) . '<br><span class="text-muted fs-8">' . $queue . '</span>';
                })
                ->addColumn('payment_method', function ($row) {
                    $color = $row->payment_method == 'cash' ? 'success' : 'info';
                    return '<span class="badge badge-light-' . $color . ' text-uppercase">' . $row->payment_method . '</span>';
                })
                // 🔥 TAMBAHAN: Kolom Diskon
                ->addColumn('discount', function ($row) {
                    if ($row->discount_amount > 0) {
                        $promoName = $row->promo ? '<br><span class="badge badge-light-danger fs-9">' . $row->promo->name . '</span>' : '';
                        return '<span class="text-danger fw-bold">- Rp ' . number_format($row->discount_amount, 0, ',', '.') . '</span>' . $promoName;
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('grand_total', function ($row) {
                    // Pesanan salah: tampilkan dicoret & redup (tidak masuk omzet).
                    if ($row->voided_at) {
                        return '<span class="text-muted text-decoration-line-through fs-6">Rp ' . number_format($row->grand_total, 0, ',', '.') . '</span>';
                    }
                    return '<span class="fw-bold text-success fs-5">Rp ' . number_format($row->grand_total, 0, ',', '.') . '</span>';
                })
                ->addColumn('status', function ($row) {
                    return $row->voided_at
                        ? '<span class="badge badge-light-danger">SALAH</span>'
                        : '<span class="badge badge-light-success">Sah</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-sm btn-icon btn-light-primary btn-view-order" '
                        . 'data-id="' . $row->id . '" title="Lihat detail pesanan">'
                        . '<i class="ki-outline ki-eye fs-2"></i></button>';
                })
                // Kirim data tambahan ke frontend
                ->with('totalRevenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->with('totalDiscount', 'Rp ' . number_format($totalDiscount, 0, ',', '.'))
                ->with('totalOrders', number_format($totalOrders, 0, ',', '.'))
                ->with('totalExpense', 'Rp ' . number_format($totalExpense, 0, ',', '.'))
                ->with('showExpense', $expenseApplies)
                ->with('netRevenue', 'Rp ' . number_format($netRevenue, 0, ',', '.'))
                ->with('voidedCount', number_format($voidedCount, 0, ',', '.'))
                ->with('voidedAmount', 'Rp ' . number_format($voidedAmount, 0, ',', '.'))
                ->rawColumns(['invoice', 'customer', 'payment_method', 'discount', 'grand_total', 'status', 'action'])
                ->make(true);
        }
    }

    /** Detail 1 pesanan (item + harga) untuk modal "lihat" di laporan. Ter-scope per-tenant. */
    public function orderDetail($id)
    {
        $order = Order::with('details.menu')->findOrFail($id);

        return response()->json([
            'invoice_no'      => $order->invoice_no,
            'customer_name'   => $order->customer_name,
            'queue_number'    => $order->queue_number,
            'date'            => Carbon::parse($order->created_at)->translatedFormat('d M Y H:i'),
            'payment_method'  => strtoupper((string) $order->payment_method),
            'payment_status'  => $order->payment_status,
            'voided'          => $order->voided_at !== null,
            'subtotal'        => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'tax'             => (float) $order->tax,
            'grand_total'     => (float) $order->grand_total,
            'items'           => $order->details->map(fn ($d) => [
                'name'     => $d->menu->name ?? 'Menu dihapus',
                'qty'      => $d->qty,
                'price'    => (float) $d->price,
                'subtotal' => (float) $d->subtotal,
                'addons'   => $d->addons ?? [],
                'notes'    => $d->notes,
            ]),
        ]);
    }

    public function print(Request $request)
    {
        $query = Order::with(['promo', 'details.menu'])->where('payment_status', 'paid');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->payment_method && $request->payment_method != 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->orderBy('created_at', 'asc')->get();
        // Pesanan SALAH (voided) tetap tampil di daftar (dengan penanda) tapi
        // TIDAK dihitung ke omzet/nota/diskon; dihitung terpisah.
        $validOrders   = $orders->whereNull('voided_at');
        $voidedOrders  = $orders->whereNotNull('voided_at');
        $totalRevenue  = $validOrders->sum('grand_total');
        $totalDiscount = $validOrders->sum('discount_amount');
        $totalOrders   = $validOrders->count();
        $voidedCount   = $voidedOrders->count();
        $voidedAmount  = $voidedOrders->sum('grand_total');

        // Pengeluaran (kas tunai) hanya relevan utk Tunai/Semua Metode; QRIS: tak dikurangkan.
        $expenseApplies = ($request->payment_method !== 'qris');
        $totalExpense = 0.0;
        if ($expenseApplies) {
            $expenseQuery = Expense::query();
            if ($request->start_date && $request->end_date) {
                // Basis kolom `date` (tanggal yang DIISI user), bukan created_at -> pengeluaran
                // dihitung pada tanggal yang dimaksud kasir walau dicatat lewat tengah malam.
                $expenseQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            $totalExpense = (float) $expenseQuery->sum('amount');
        }
        $netRevenue = $totalRevenue - $totalExpense;

        $setting = Setting::first();

        $filterDate = Carbon::parse($request->start_date)->translatedFormat('d M Y') . ' - ' . Carbon::parse($request->end_date)->translatedFormat('d M Y');
        $filterPayment = $request->payment_method == 'all' ? 'Semua Metode' : strtoupper($request->payment_method);

        return view('backend.reports.sales.print', compact('orders', 'totalRevenue', 'totalDiscount', 'totalOrders', 'totalExpense', 'expenseApplies', 'netRevenue', 'setting', 'filterDate', 'filterPayment', 'voidedCount', 'voidedAmount'));
    }
}

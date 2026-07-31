<?php

namespace App\Http\Controllers\Backend\Kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    public function index()
    {
        // Tampilkan SEMUA pesanan yang belum selesai, tanpa filter tanggal.
        $activeOrders = Order::with(['details.menu'])
            ->whereIn('order_status', ['pending', 'cooking'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Pesanan Sudah Selesai: 3 hari terakhir saja untuk referensi
        $completedOrders = Order::with(['details.menu'])
            ->whereIn('order_status', ['served', 'completed'])
            ->where('updated_at', '>=', \Carbon\Carbon::now()->subDays(3))
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('backend.kitchen.index', compact('activeOrders', 'completedOrders'));
    }

    public function updateItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $detail = OrderDetail::findOrFail($request->detail_id);
            $detail->update(['status' => $request->status]);

            // MODUL HPP: potong stok bahan (FEFO) + tulis HPP saat item mulai dimasak/selesai.
            // Idempoten (is_stock_deducted) & hanya untuk tenant berpaket yang punya modul.
            $this->deductStockIfEligible($detail, $request->input('batch_selections', []));

            $order = Order::findOrFail($detail->order_id);

            // Ambil status semua detail dalam 1 query, hitung di PHP
            $statuses = $order->details()->pluck('status');
            $totalItems = $statuses->count();
            $doneItems = $statuses->filter(fn($s) => $s === 'done')->count();
            $cookingItems = $statuses->filter(fn($s) => $s === 'cooking')->count();

            $isFinished = false;

            if ($totalItems > 0 && $doneItems == $totalItems) {
                $order->update(['order_status' => 'served']);
                $isFinished = true;
            } elseif ($cookingItems > 0 || $doneItems > 0) {
                $order->update(['order_status' => 'cooking']);
            } else {
                $order->update(['order_status' => 'pending']);
            }

            DB::commit();
            return response()->json([
                'success'       => true,
                'is_finished'   => $isFinished,
                'customer_name' => $order->customer_name ?? 'Pelanggan',
                'queue_number'  => $order->queue_number,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Update semua item di dalam 1 Order sekaligus
    public function updateOrderStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($request->order_id);
            $status = $request->status; // 'cooking' atau 'done'
            $isFinished = false;

            if ($status == 'cooking') {
                $order->details()->where('status', 'pending')->update(['status' => 'cooking']);
                $order->update(['order_status' => 'cooking']);
            } elseif ($status == 'done') {
                $order->details()->whereIn('status', ['pending', 'cooking'])->update(['status' => 'done']);
                $order->update(['order_status' => 'served']);
                $isFinished = true;
            }

            // MODUL HPP: potong stok + tulis HPP untuk seluruh item pesanan (idempoten).
            foreach ($order->details()->get() as $d) {
                $this->deductStockIfEligible($d);
            }

            DB::commit();
            return response()->json([
                'success'       => true,
                'is_finished'   => $isFinished,
                'customer_name' => $order->customer_name ?? 'Pelanggan',
                'queue_number'  => $order->queue_number,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * MODUL HPP (F&B, paket Customize): potong stok bahan resep secara FEFO lalu simpan
     * HPP baris pesanan. Aman dipanggil berulang (dijaga is_stock_deducted di StockService).
     *
     * Non-fatal: kegagalan modul HPP TIDAK boleh menggagalkan alur dapur — hanya dicatat log.
     */
    private function deductStockIfEligible(OrderDetail $detail, array $batchSelections = []): void
    {
        try {
            $tenant = auth()->user()?->tenant;

            // Hanya tenant F&B yang paketnya memuat modul inventory_hpp.
            if (! $tenant || ! \App\Tenancy\Plan::tenantAllows($tenant, 'inventory_hpp')) {
                return;
            }
            if (! in_array($detail->status, ['cooking', 'done'], true) || $detail->is_stock_deducted) {
                return;
            }

            app(\App\Services\Fnb\StockService::class)->deductMenuStock($detail, $batchSelections);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('HPP: potong stok gagal (detail#' . $detail->id . '): ' . $e->getMessage());
        }
    }
}

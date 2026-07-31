<?php

namespace App\Http\Controllers\Backend\Laundry;

use App\Http\Controllers\Controller;
use App\Models\Laundry\LaundryOrder;
use App\Models\Laundry\LaundryStatusLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Papan Produksi Laundry (workshop): pantau & majukan status cucian satu tahap.
 */
class LaundryProduksiController extends Controller
{
    public function index()
    {
        return view('backend.laundry.produksi.index', [
            'active'      => LaundryOrder::with('items')
                ->whereIn('order_status', LaundryOrder::ACTIVE_STATUSES)
                ->orderBy('created_at')
                ->get(),
            'ready'       => LaundryOrder::where('order_status', 'selesai')
                ->where('created_at', '>=', now()->subDays(3))
                ->orderByDesc('actual_completed_at')
                ->get(),
            'pipeline'    => LaundryOrder::PIPELINE,
            'stageLabels' => LaundryOrder::STAGE_LABELS,
        ]);
    }

    /** Majukan satu tahap sesuai pipeline. */
    public function advance(LaundryOrder $order)
    {
        $next = $order->nextStatus();
        abort_if($next === null, 422, 'Pesanan sudah pada tahap akhir pipeline.');

        DB::transaction(function () use ($order, $next) {
            $order->order_status = $next;
            if ($next === 'selesai') {
                $order->actual_completed_at = now();
            }
            $order->save();

            $order->items()->update(['status' => $next === 'selesai' ? 'done' : 'process']);

            LaundryStatusLog::create([
                'order_id'   => $order->id,
                'status'     => $next,
                'changed_by' => Auth::id(),
                'notes'      => 'Maju ke tahap ' . ($order->statusLabel()),
            ]);
        });

        return back()->with('success', 'Status ' . $order->invoice_no . ' → ' . $order->statusLabel() . '.');
    }
}

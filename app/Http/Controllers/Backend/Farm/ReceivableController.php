<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Agent;
use App\Models\Farm\AgentPayment;
use App\Models\Farm\StockOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** PIUTANG AGEN — daftar nota belum lunas + pencatatan pembayaran (boleh dicicil). */
class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $q = StockOut::with(['agent', 'payments'])->where('payment_status', 'unpaid');

        if ($request->filled('agent_id')) {
            $q->where('agent_id', $request->agent_id);
        }
        if ($request->input('filter') === 'overdue') {
            $q->whereNotNull('due_date')->whereDate('due_date', '<', now());
        }

        $rows = $q->orderBy('due_date')->orderByDesc('date')->paginate(30)->withQueryString();

        $total = (float) StockOut::where('payment_status', 'unpaid')
            ->selectRaw('COALESCE(SUM(total_sale - paid_amount),0) as sisa')->value('sisa');
        $jatuhTempo = (float) StockOut::where('payment_status', 'unpaid')
            ->whereNotNull('due_date')->whereDate('due_date', '<', now())
            ->selectRaw('COALESCE(SUM(total_sale - paid_amount),0) as sisa')->value('sisa');

        return view('backend.farm.receivables.index', [
            'rows'       => $rows,
            'agents'     => Agent::orderBy('name')->get(),
            'total'      => $total,
            'jatuhTempo' => $jatuhTempo,
            'filter'     => $request->input('filter'),
            'agentId'    => $request->input('agent_id'),
        ]);
    }

    /** Kartu piutang satu agen: seluruh nota + pembayaran. */
    public function card(Agent $agent)
    {
        return view('backend.farm.receivables.card', [
            'agent' => $agent,
            'rows'  => StockOut::with('payments')->where('agent_id', $agent->id)
                ->orderByDesc('date')->orderByDesc('id')->get(),
            'sisa'  => $agent->outstanding(),
        ]);
    }

    public function pay(Request $request, StockOut $stockOut)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'date'   => ['required', 'date'],
            'method' => ['required', 'in:cash,transfer'],
            'notes'  => ['nullable', 'string', 'max:255'],
        ]);

        if ($stockOut->isPaid()) {
            return back()->with('error', 'Nota ini sudah lunas.');
        }

        $sisa = $stockOut->remaining();
        if ($data['amount'] > $sisa + 0.01) {
            return back()->with('error', 'Jumlah melebihi sisa tagihan (Rp ' . number_format($sisa, 0, ',', '.') . ').');
        }

        DB::transaction(function () use ($data, $stockOut) {
            AgentPayment::create([
                'agent_id'     => $stockOut->agent_id,
                'stock_out_id' => $stockOut->id,
                'date'         => $data['date'],
                'amount'       => $data['amount'],
                'method'       => $data['method'],
                'user_id'      => Auth::id(),
                'notes'        => $data['notes'] ?? null,
            ]);

            $dibayar = round((float) $stockOut->paid_amount + (float) $data['amount'], 2);
            $lunas   = $dibayar >= (float) $stockOut->total_sale - 0.01;

            $stockOut->update([
                'paid_amount'    => $dibayar,
                'payment_status' => $lunas ? 'paid' : 'unpaid',
                'paid_at'        => $lunas ? $data['date'] : null,
            ]);
        });

        return back()->with('success', 'Pembayaran dicatat.');
    }
}

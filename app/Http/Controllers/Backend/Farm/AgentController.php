<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Agent;
use Illuminate\Http\Request;

/** Master agen pembeli, termasuk tempo baku & batas piutang. */
class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::orderBy('name')->get();

        return view('backend.farm.agents.index', [
            'agents' => $agents->map(function (Agent $a) {
                $a->sisa_piutang = $a->outstanding();

                return $a;
            }),
        ]);
    }

    public function store(Request $request)
    {
        Agent::create($this->validated($request));

        return back()->with('success', 'Agen ditambahkan.');
    }

    public function update(Request $request, Agent $agent)
    {
        $agent->update($this->validated($request));

        return back()->with('success', 'Agen diperbarui.');
    }

    public function toggle(Agent $agent)
    {
        $agent->update(['is_active' => ! $agent->is_active]);

        return back()->with('success', 'Status agen diubah.');
    }

    public function destroy(Agent $agent)
    {
        if ($agent->stockOuts()->exists()) {
            return back()->with('error', 'Agen sudah punya riwayat penjualan — nonaktifkan saja, jangan dihapus.');
        }
        $agent->delete();

        return back()->with('success', 'Agen dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'address'      => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'term_days'    => ['nullable', 'integer', 'min:0', 'max:180'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use App\Models\Farm\WarehouseSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BUKA / TUTUP GUDANG — pengganti "shift" kasir.
 * Tidak ada modal & kembalian: yang dipertanggungjawabkan adalah STOK FISIK.
 */
class WarehouseController extends Controller
{
    public function index()
    {
        return view('backend.farm.warehouse.index', [
            'active'  => WarehouseSession::where('status', 'open')->latest('opened_at')->first(),
            'history' => WarehouseSession::with(['opener', 'closer'])->orderByDesc('opened_at')->limit(30)->get(),
            'items'   => Item::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function open(Request $request)
    {
        if (WarehouseSession::where('status', 'open')->exists()) {
            return back()->with('error', 'Masih ada sesi gudang yang terbuka. Tutup dulu sebelum membuka yang baru.');
        }

        WarehouseSession::create([
            'opened_by'     => Auth::id(),
            'opened_at'     => now(),
            'opening_stock' => $this->snapshot(),
            'status'        => 'open',
            'notes'         => $request->input('notes'),
        ]);

        return back()->with('success', 'Gudang dibuka. Stok awal tercatat.');
    }

    public function close(Request $request, WarehouseSession $session)
    {
        if (! $session->isOpen()) {
            return back()->with('error', 'Sesi ini sudah ditutup.');
        }

        $sistem = $this->snapshot();
        $fisik  = [];
        foreach ((array) $request->input('physical', []) as $itemId => $nilai) {
            $fisik[(int) $itemId] = [
                'ekor' => (int) ($nilai['ekor'] ?? 0),
                'kg'   => round((float) ($nilai['kg'] ?? 0), 2),
            ];
        }

        // Selisih = fisik - sistem. Dicatat apa adanya, tidak menimpa stok:
        // koreksi stok dilakukan sadar lewat Penyesuaian Stok.
        $selisih = [];
        foreach ($sistem as $itemId => $s) {
            $f = $fisik[$itemId] ?? ['ekor' => 0, 'kg' => 0];
            $selisih[$itemId] = [
                'nama' => $s['nama'],
                'ekor' => $f['ekor'] - $s['ekor'],
                'kg'   => round($f['kg'] - $s['kg'], 2),
            ];
        }

        $session->update([
            'closed_by'      => Auth::id(),
            'closed_at'      => now(),
            'closing_stock'  => $sistem,
            'physical_stock' => $fisik,
            'difference'     => $selisih,
            'status'         => 'closed',
            'notes'          => trim(($session->notes ? $session->notes . ' | ' : '') . (string) $request->input('notes')),
        ]);

        return back()->with('success', 'Gudang ditutup. Selisih tercatat — luruskan lewat Penyesuaian Stok bila perlu.');
    }

    /** Potret stok sistem saat ini per item. */
    private function snapshot(): array
    {
        $out = [];
        foreach (Item::where('is_active', true)->get() as $item) {
            $s = $item->stock();
            $out[$item->id] = ['nama' => $item->name, 'ekor' => $s['ekor'], 'kg' => $s['kg']];
        }

        return $out;
    }
}

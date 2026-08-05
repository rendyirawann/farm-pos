<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use App\Models\Farm\StockAdjustment;
use App\Models\Farm\StockLot;
use App\Services\Farm\FarmStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * PENYESUAIAN STOK — ayam mati, susut bobot, rusak, atau koreksi hasil opname.
 * Tanpa jalur ini stok sistem tidak akan pernah cocok dengan fisik.
 */
class AdjustmentController extends Controller
{
    public function __construct(private FarmStockService $stock) {}

    public function index()
    {
        return view('backend.farm.adjustments.index', [
            'rows'    => StockAdjustment::with(['item', 'lot', 'user'])->orderByDesc('date')->orderByDesc('id')->paginate(30),
            'items'   => Item::where('is_active', true)->orderBy('name')->get(),
            'reasons'   => StockAdjustment::REASONS,
            'tanpaFoto' => StockAdjustment::TANPA_FOTO,
        ]);
    }

    public function store(Request $request)
    {
        // Foto WAJIB kecuali alasannya "hilang" — penyesuaian tidak punya dokumen
        // dari pihak luar, jadi fotonya satu-satunya bukti bahwa barangnya memang
        // begitu. Aturan ini ditegakkan di server, bukan hanya di layar.
        $wajibFoto = StockAdjustment::butuhFoto($request->input('reason'));

        $data = $request->validate([
            'date'      => ['required', 'date'],
            'item_id'   => ['required', 'integer'],
            'lot_id'    => ['nullable', 'integer'],
            'reason'    => ['required', 'in:' . implode(',', array_keys(StockAdjustment::REASONS))],
            'qty_ekor'  => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'notes'     => ['nullable', 'string', 'max:255'],
            'photo'     => [$wajibFoto ? 'required' : 'nullable', 'file',
                'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'photo.required' => 'Foto bukti wajib dilampirkan untuk alasan ini. '
                . 'Pilih alasan "Hilang" bila memang tidak ada barang yang bisa difoto.',
        ], ['photo' => 'foto bukti']);

        if ((int) ($data['qty_ekor'] ?? 0) <= 0 && (float) ($data['weight_kg'] ?? 0) <= 0) {
            return back()->withInput()->with('error', 'Isi jumlah ekor atau berat yang disesuaikan.');
        }

        $foto = null;
        if ($request->hasFile('photo')) {
            $berkas = $request->file('photo');
            $ext = strtolower($berkas->getClientOriginalExtension() ?: 'jpg');
            $foto = $berkas->storeAs('farm/penyesuaian',
                'adj-' . now()->format('Ymd') . '-' . Str::random(8) . '.' . $ext, 'public');
        }

        $adj = StockAdjustment::create(collect($data)->except('photo')->all() + [
            'ref_no'     => 'ADJ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'user_id'    => Auth::id(),
            'photo_path' => $foto,
        ]);

        try {
            $dampak = $this->stock->applyAdjustment($adj);
        } catch (\Throwable $e) {
            $adj->delete();
            if ($foto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($foto);
            }

            return back()->with('error', 'Gagal menyesuaikan: ' . $e->getMessage());
        }

        return back()->with('success', 'Penyesuaian tercatat. Dampak nilai: Rp ' . number_format($dampak, 0, ',', '.'));
    }

    /**
     * HAPUS PENYESUAIAN — stok dikembalikan sebanyak yang benar-benar terpotong.
     *
     * Penyesuaian yang dicatat saat stoknya sudah habis tidak memotong apa pun,
     * tetapi tetap menghitung diri sebagai susut dan membuat laporan tidak bisa
     * ditutup. Baris seperti itu harus bisa dibuang tanpa mengubah stok.
     */
    public function destroy(StockAdjustment $adjustment)
    {
        // Penyesuaian yang sudah disetujui adalah keputusan resmi; membatalkannya
        // harus lewat persetujuan baru, bukan dihapus sepihak.
        if ($adjustment->isApproved()) {
            return back()->with('error',
                'Penyesuaian ini sudah disetujui, jadi tidak bisa dihapus. '
                . 'Buat penyesuaian baru sebagai koreksi bila angkanya salah.');
        }

        $kembali = [];

        try {
            DB::transaction(function () use ($adjustment, &$kembali) {
                if ($adjustment->isAddition()) {
                    // Koreksi TAMBAH membuat lot baru. Lot itu hanya boleh dibuang
                    // bila belum terpakai — kalau sudah terjual, harga pokok nota
                    // lama mengambil dari lot ini.
                    $lot = StockLot::whereNull('stock_in_line_id')
                        ->where('item_id', $adjustment->item_id)
                        ->where('date', $adjustment->date)
                        ->where('qty_ekor_initial', abs((int) $adjustment->qty_ekor))
                        ->orderByDesc('id')->first();

                    if ($lot) {
                        $terpakai = (int) $lot->qty_ekor_initial - (int) $lot->qty_ekor_left;
                        if ($terpakai > 0) {
                            throw new \RuntimeException(
                                'Stok dari koreksi tambah ini sudah terpakai penjualan, jadi tidak bisa dihapus.');
                        }
                        $lot->delete();
                        $kembali[] = 'lot koreksi tambah dibuang';
                    }
                } else {
                    // Pengurangan: kembalikan tepat sebanyak yang tercatat terpotong.
                    $pemakaian = DB::table('farm_adjustment_lot_usages')
                        ->where('adjustment_id', $adjustment->id)->get();

                    foreach ($pemakaian as $u) {
                        $lot = StockLot::find($u->lot_id);
                        if ($lot) {
                            $lot->update([
                                'weight_kg_left' => round((float) $lot->weight_kg_left + (float) $u->weight_kg, 2),
                                'qty_ekor_left'  => (int) $lot->qty_ekor_left + (int) $u->qty_ekor,
                            ]);
                            $kembali[] = number_format((float) $u->weight_kg, 2, ',', '.') . ' kg';
                        }
                    }

                    DB::table('farm_adjustment_lot_usages')->where('adjustment_id', $adjustment->id)->delete();
                }

                if ($adjustment->hasPhoto()) {
                    Storage::disk('public')->delete($adjustment->photo_path);
                }

                $adjustment->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Penyesuaian dihapus.'
            . ($kembali ? ' Stok dikembalikan: ' . implode(', ', $kembali) . '.'
                        : ' Penyesuaian ini dulu tidak memotong stok apa pun, jadi stok tidak berubah.'));
    }

    /** Persetujuan supervisor/admin. */
    public function approve(StockAdjustment $adjustment)
    {
        $adjustment->update(['approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Penyesuaian disetujui.');
    }

    /** Daftar lot suatu item (ajax) — untuk memilih lot tertentu saat menyesuaikan. */
    public function lots(Item $item)
    {
        return response()->json(
            StockLot::where('item_id', $item->id)->available()->fifo()->with('supplier')->get()
                ->map(fn (StockLot $l) => [
                    'id'    => $l->id,
                    // Nama supplier ikut ditampilkan: beberapa lot sering bertanggal sama,
                    // sehingga tanpa nama supplier petugas tidak bisa membedakannya.
                    'label' => sprintf('%s · %s — sisa %d ekor / %s kg @ Rp%s/kg',
                        $l->date->format('d/m/Y'),
                        $l->supplier?->name ?: 'tanpa supplier',
                        $l->qty_ekor_left,
                        number_format((float) $l->weight_kg_left, 2, ',', '.'),
                        number_format((float) $l->cost_per_kg, 0, ',', '.')),
                ])
        );
    }
}

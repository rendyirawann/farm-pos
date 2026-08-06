<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Agent;
use App\Models\Farm\Item;
use App\Models\Farm\StockOut;
use App\Models\Farm\StockOutLine;
use App\Services\Farm\EggCostService;
use App\Services\Farm\FarmStockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * STOCK OUT — penjualan ke agen. Harga pokok diambil FIFO dari lot pembelian,
 * sehingga laba per nota memakai harga beli yang benar-benar terpakai.
 */
class StockOutController extends Controller
{
    public function __construct(
        private FarmStockService $stock,
        private EggCostService $eggCost,
    ) {}

    public function index(Request $request)
    {
        // Sama seperti barang masuk: bawaan TAMPIL SEMUA, tanggal hanya bila diisi.
        $from = $request->filled('from') ? Carbon::parse($request->from) : null;
        $to   = $request->filled('to') ? Carbon::parse($request->to) : null;

        // Dua jenis penjualan dipisah jadi tab: penjualan ke AGEN dibaca bersama
        // tempo & piutangnya, sedangkan ECER selalu tunai di tempat. Menyatukannya
        // dalam satu daftar membuat keduanya harus disaring dengan mata.
        $jenis = in_array($request->input('jenis'), ['agen', 'ecer'], true)
            ? $request->input('jenis')
            : 'agen';

        // Filter tanggal & status berlaku sama untuk kedua tab; jenisnya dipisah
        // supaya jumlah pada tiap tab tetap sebanding.
        $filterUmum = function ($q) use ($from, $to, $request) {
            if ($from) {
                $q->whereDate('date', '>=', $from->toDateString());
            }
            if ($to) {
                $q->whereDate('date', '<=', $to->toDateString());
            }
            if (in_array($request->input('status'), ['paid', 'unpaid'], true)) {
                $q->where('payment_status', $request->input('status'));
            }

            return $q;
        };

        $filterJenis = fn ($q, string $j) => $j === 'ecer'
            ? $q->whereNull('agent_id')
            : $q->whereNotNull('agent_id');

        $rows = $filterJenis($filterUmum(StockOut::with(['agent', 'lines.item'])), $jenis)
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(10)->withQueryString();

        $rekap = $filterJenis($filterUmum(StockOut::query()), $jenis)
            ->selectRaw('COALESCE(SUM(total_sale),0) jual, COALESCE(SUM(total_cost),0) modal, COALESCE(SUM(gross_profit),0) laba')
            ->first();

        return view('backend.farm.stock_out.index', [
            'rows'  => $rows,
            'from'  => $from?->format('Y-m-d'),
            'to'    => $to?->format('Y-m-d'),
            'rekap' => $rekap,
            'status' => $request->input('status'),
            'jenis'  => $jenis,
            // Jumlah pada kedua tab ikut disaring, supaya angkanya sesuai dengan
            // yang benar-benar akan terlihat ketika tabnya dibuka.
            'jumlahAgen' => $filterJenis($filterUmum(StockOut::query()), 'agen')->count(),
            'jumlahEcer' => $filterJenis($filterUmum(StockOut::query()), 'ecer')->count(),
            'jumlah'   => $rows->total(),
            'disaring' => (bool) ($from || $to || $request->filled('status')),
        ]);
    }

    public function create()
    {
        $items = Item::where('is_active', true)->orderBy('name')->get()
            ->map(function (Item $i) {
                $s = $i->stock();
                $i->stok_ekor = $s['ekor'];
                $i->stok_kg   = $s['kg'];

                // Kapan terakhir barang ini masuk — supaya daftar "item habis"
                // memberi petunjuk, bukan cuma memberitahu bahwa stoknya nol.
                $i->terakhir_masuk = \Illuminate\Support\Facades\DB::table('farm_stock_lots')
                    ->where('item_id', $i->id)->max('date');

                return $i;
            });

        // Barang berstok nol TIDAK ikut ke dropdown: memilihnya hanya menghasilkan
        // nota berharga pokok 0 dan stok minus. Daftarnya tetap ditampilkan lewat
        // tombol tersendiri sebagai informasi.
        [$tersedia, $habis] = $items->partition(
            fn (Item $i) => (int) $i->stok_ekor > 0 || (float) $i->stok_kg > 0.001
        );

        return view('backend.farm.stock_out.create', [
            'agents' => Agent::where('is_active', true)->orderBy('name')->get(),
            'items'  => $tersedia->values(),
            'habis'  => $habis->values(),
            'hppTelur' => $this->eggCost->costPerButir(),
        ]);
    }

    /**
     * Pratinjau harga pokok (ajax) — dipanggil saat kasir mengetik jumlah,
     * supaya margin terlihat SEBELUM nota disimpan.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'item_id'   => ['required', 'integer'],
            'qty_ekor'  => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $item = Item::findOrFail($data['item_id']);

        $hasil = $this->stock->previewCost(
            $item->id, (float) ($data['weight_kg'] ?? 0), (int) ($data['qty_ekor'] ?? 0)
        );

        // Telur diambil dari lot produksi seperti barang lain. Hanya bila belum ada
        // lot sama sekali, harga pokoknya jatuh ke hitungan biaya operasional —
        // dan itu dikatakan terang-terangan supaya tidak disangka harga beli.
        if ($item->is_produced && empty($hasil['lots'])) {
            $butir = (int) ($data['qty_ekor'] ?? 0);
            $hasil['cost'] = $this->eggCost->costFor($butir);
            $hasil['hpp_per_ekor'] = $butir > 0 ? round($hasil['cost'] / $butir, 2) : null;
            $hasil['catatan'] = 'Belum ada produksi telur yang tercatat — harga pokok memakai '
                . 'hitungan biaya operasional bulan ini.';
        }

        return response()->json($hasil);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                => ['required', 'date'],
            'agent_id'            => ['nullable', 'integer'],
            'payment_status'      => ['required', 'in:paid,unpaid'],
            'due_date'            => ['nullable', 'date'],
            'notes'               => ['nullable', 'string', 'max:255'],
            'lines'               => ['required', 'array', 'min:1'],
            'lines.*.item_id'     => ['required', 'integer'],
            'lines.*.qty_ekor'    => ['nullable', 'integer', 'min:0'],
            'lines.*.weight_kg'   => ['nullable', 'numeric', 'min:0'],
            'lines.*.price_basis' => ['required', 'in:kg,ekor,butir'],
            'lines.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $out = DB::transaction(function () use ($data) {
                $out = StockOut::create([
                    'invoice_no'     => $this->generateInvoiceNo(),
                    'date'           => $data['date'],
                    'agent_id'       => $data['agent_id'] ?: null,
                    'user_id'        => Auth::id(),
                    'payment_status' => $data['payment_status'],
                    'due_date'       => $data['payment_status'] === 'unpaid' ? ($data['due_date'] ?? null) : null,
                    'paid_amount'    => 0,
                    'notes'          => $data['notes'] ?? null,
                ]);

                $totalJual = $totalModal = 0.0;

                foreach ($data['lines'] as $row) {
                    $ekor = (int) ($row['qty_ekor'] ?? 0);
                    $kg   = round((float) ($row['weight_kg'] ?? 0), 2);
                    if ($ekor <= 0 && $kg <= 0) {
                        continue;
                    }

                    $item  = Item::findOrFail((int) $row['item_id']);
                    $harga = (float) $row['unit_price'];
                    $basis = $row['price_basis'];

                    $subtotal = match ($basis) {
                        'ekor', 'butir' => $harga * $ekor,
                        default         => $harga * $kg,
                    };

                    $line = StockOutLine::create([
                        'stock_out_id' => $out->id,
                        'item_id'      => $item->id,
                        'qty_ekor'     => $ekor,
                        'weight_kg'    => $kg,
                        'price_basis'  => $basis,
                        'unit_price'   => $harga,
                        'subtotal'     => round($subtotal, 2),
                    ]);

                    // Barang produksi sendiri (telur) IKUT mengurangi stok. Sebelumnya
                    // hanya harga pokoknya yang dihitung sementara lot produksinya tidak
                    // pernah berkurang — telur terjual berkali-kali dan stoknya tetap.
                    $hasil = $this->stock->consumeFifo($item->id, $kg, $ekor);
                    $cost  = $hasil['cost'];
                    $this->stock->recordUsages($line, $hasil['usages']);

                    // Bila produksinya belum pernah dicatat, FIFO tidak menemukan lot dan
                    // biayanya 0. Untuk telur, jatuhkan ke harga pokok otomatis dari biaya
                    // operasional supaya labanya tidak terlihat 100%.
                    if ($item->is_produced && $cost <= 0 && $ekor > 0) {
                        $cost = $this->eggCost->costFor($ekor, Carbon::parse($data['date']));
                    }

                    $line->update(['cost' => $cost, 'profit' => round($subtotal - $cost, 2)]);

                    $totalJual  += $subtotal;
                    $totalModal += $cost;
                }

                if ($out->lines()->count() === 0) {
                    throw new \RuntimeException('Tidak ada baris barang yang diisi.');
                }

                $out->update([
                    'total_sale'   => round($totalJual, 2),
                    'total_cost'   => round($totalModal, 2),
                    'gross_profit' => round($totalJual - $totalModal, 2),
                    'paid_amount'  => $data['payment_status'] === 'paid' ? round($totalJual, 2) : 0,
                    'paid_at'      => $data['payment_status'] === 'paid' ? $data['date'] : null,
                ]);

                return $out->fresh(['lines.item', 'agent']);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }

        $pesan = 'Penjualan tersimpan. Nota siap dicetak.';
        if ($this->stock->warnings) {
            $pesan .= ' ⚠ ' . implode(' ', $this->stock->warnings);
        }

        return redirect()->route('farm.stock-out.show', $out->id)
            ->with('success', $pesan)
            // Cetak otomatis hanya untuk nota lunas; nota berhutang dicetak nanti
            // setelah pembayarannya masuk.
            ->with('autoprint', $out->isPaid());
    }

    public function show(StockOut $stockOut)
    {
        $stockOut->load(['lines.item', 'lines.lotUsages.lot.supplier', 'agent', 'user', 'payments']);

        return view('backend.farm.stock_out.show', ['row' => $stockOut]);
    }

    public function receipt(StockOut $stockOut)
    {
        // Nota hanya boleh dicetak setelah LUNAS. Struk yang tercetak dipegang agen
        // sebagai bukti transaksi selesai; mencetaknya saat masih berutang membuat
        // bukti itu menyesatkan. Aturan ini ditegakkan di sini, bukan hanya dengan
        // menyembunyikan tombol — supaya tidak bisa dilewati dari alamat langsung.
        if (! $stockOut->isPaid()) {
            return response()->json([
                'message' => 'Nota ' . $stockOut->invoice_no . ' belum lunas, jadi belum bisa dicetak. '
                    . 'Catat pembayarannya dulu (sisa ' . number_format($stockOut->remaining(), 0, ',', '.') . ').',
            ], 422);
        }

        $stockOut->load(['lines.item', 'agent']);

        return response()->json([
            'title'       => 'NOTA PENJUALAN',
            'invoice_no'  => $stockOut->invoice_no,
            'datetime'    => $stockOut->date->format('d/m/Y'),
            'party'       => $stockOut->agent?->name ?? 'Umum',
            'party_label' => 'Agen',
            'items'       => $stockOut->lines->map(fn ($l) => [
                'name'      => $l->item?->name ?? '-',
                'qty_ekor'  => (int) $l->qty_ekor,
                'weight_kg' => (float) $l->weight_kg,
                'basis'     => $l->price_basis,
                'price'     => (float) $l->unit_price,
                'subtotal'  => (float) $l->subtotal,
            ]),
            'total'          => (float) $stockOut->total_sale,
            'payment_status' => $stockOut->payment_status,
            'due_date'       => $stockOut->due_date?->format('d/m/Y'),
            'notes'          => $stockOut->notes,
        ]);
    }

    /**
     * BATALKAN NOTA BARANG KELUAR — stok dikembalikan ke lot asalnya.
     *
     * Diperlukan untuk memperbaiki salah input yang tidak bisa dibetulkan cara lain:
     * nota yang jumlahnya melebihi stok tersimpan berharga pokok 0 pada bagian yang
     * tidak menemukan lot, sehingga labanya terlihat lebih besar dari kenyataan.
     * Satu-satunya cara meluruskannya adalah membatalkan nota itu, mencatat
     * pembelian yang tertinggal, lalu memasukkan notanya kembali.
     *
     * Yang dikembalikan hanya yang BENAR-BENAR terpotong dari lot (baris pemakaian),
     * jadi stok tidak pernah bertambah lebih dari yang pernah keluar.
     */
    public function destroy(StockOut $stockOut)
    {
        // Nota yang sudah ada pembayarannya jangan dibatalkan diam-diam: uangnya
        // sudah tercatat masuk dan kartu piutang agen akan ikut melenceng.
        if ($stockOut->payments()->exists()) {
            return back()->with('error',
                'Nota ini sudah punya catatan pembayaran agen. Hapus dulu pembayarannya, '
                . 'baru notanya bisa dibatalkan.');
        }

        $no = $stockOut->invoice_no;

        try {
            $ringkas = DB::transaction(function () use ($stockOut) {
                $kembali = [];

                foreach ($stockOut->lines()->with('item')->get() as $line) {
                    $balik = (float) $line->lotUsages()->sum('weight_kg');
                    $balikEkor = (int) $line->lotUsages()->sum('qty_ekor');

                    $this->stock->restoreFromStockOut($line);

                    if ($balik > 0.001 || $balikEkor > 0) {
                        $kembali[] = sprintf('%s %s kg / %d ekor',
                            $line->item?->name ?? 'barang',
                            number_format($balik, 2, ',', '.'), $balikEkor);
                    }
                    $line->delete();
                }

                $stockOut->delete();

                return $kembali;
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membatalkan nota: ' . $e->getMessage());
        }

        $pesan = 'Nota ' . $no . ' dibatalkan.';
        $pesan .= $ringkas
            ? ' Stok dikembalikan: ' . implode(', ', $ringkas) . '.'
            : ' Tidak ada stok yang perlu dikembalikan (nota ini dulu tidak menemukan stok sama sekali).';

        return redirect()->route('farm.stock-out.index')->with('success', $pesan);
    }

    /** Nomor nota jual: JUAL-YYYYMMDD-XXXXXX */
    private function generateInvoiceNo(): string
    {
        do {
            $no = 'JUAL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (StockOut::withoutGlobalScopes()->where('invoice_no', $no)->exists());

        return $no;
    }
}

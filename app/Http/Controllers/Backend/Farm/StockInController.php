<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use App\Models\Farm\StockIn;
use App\Models\Farm\StockInLine;
use App\Models\Farm\StockLot;
use App\Models\Farm\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Farm\DepositService;
use App\Services\Farm\RealizationService;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * STOCK IN — pembelian ayam dari supplier.
 * Setiap baris pembelian menjadi SATU LOT; lot inilah yang nanti diambil FIFO saat dijual.
 */
class StockInController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : Carbon::now()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->to) : Carbon::now();

        $q = StockIn::with(['supplier', 'lines.item', 'realization'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        // Filter status pembayaran KITA ke supplier.
        if (in_array($request->input('status'), ['paid', 'unpaid'], true)) {
            $q->where('payment_status', $request->input('status'));
        }

        $rows = $q->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        // Ringkasan seluruh nota BELUM LUNAS — tidak dibatasi rentang tanggal,
        // karena nota lama yang menggantung justru yang paling perlu terlihat.
        $belum = StockIn::with('realization')->where('payment_status', 'unpaid')->get();
        $sisaBelum = $belum->sum(fn (StockIn $r) => $r->remainingToPay());

        return view('backend.farm.stock_in.index', [
            'rows'   => $rows,
            'from'   => $from->format('Y-m-d'),
            'to'     => $to->format('Y-m-d'),
            'status' => $request->input('status'),
            'total'  => (float) StockIn::whereBetween('date', [$from->toDateString(), $to->toDateString()])->sum('total'),
            'jumlahBelum' => $belum->count(),
            'sisaBelum'   => round((float) $sisaBelum, 2),
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get()
            ->map(function (Supplier $s) {
                // Saldo deposit tiap supplier ikut dikirim supaya form bisa memberi tahu
                // SEBELUM nota disimpan bahwa saldonya kurang, bukan setelahnya.
                $s->saldo = $s->depositBalance();

                return $s;
            });

        return view('backend.farm.stock_in.create', [
            'suppliers' => $suppliers,
            // Telur tidak dibeli dari supplier -> tidak muncul di form pembelian.
            'items'     => Item::where('is_active', true)->where('is_produced', false)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                  => ['required', 'date'],
            'supplier_id'           => ['nullable', 'integer'],
            'notes'                 => ['nullable', 'string', 'max:255'],
            'lines'                 => ['required', 'array', 'min:1'],
            'lines.*.item_id'       => ['required', 'integer'],
            'lines.*.qty_ekor'      => ['nullable', 'integer', 'min:0'],
            'lines.*.weight_kg'     => ['nullable', 'numeric', 'min:0'],
            'lines.*.price_basis'   => ['required', 'in:kg,ekor'],
            'lines.*.unit_price'    => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $stockIn = DB::transaction(function () use ($data) {
                $in = StockIn::create([
                    'invoice_no'  => $this->generateInvoiceNo(),
                    'date'        => $data['date'],
                    'supplier_id' => $data['supplier_id'] ?: null,
                    'user_id'     => Auth::id(),
                    'notes'       => $data['notes'] ?? null,
                    'total'       => 0,
                ]);

                $total = 0.0;

                foreach ($data['lines'] as $row) {
                    $ekor = (int) ($row['qty_ekor'] ?? 0);
                    $kg   = round((float) ($row['weight_kg'] ?? 0), 2);
                    $harga = (float) $row['unit_price'];

                    if ($ekor <= 0 && $kg <= 0) {
                        continue;   // baris kosong dilewati
                    }

                    // Subtotal mengikuti dasar harga yang dipilih kasir.
                    $subtotal = $row['price_basis'] === 'ekor' ? $harga * $ekor : $harga * $kg;

                    $line = StockInLine::create([
                        'stock_in_id' => $in->id,
                        'item_id'     => (int) $row['item_id'],
                        'qty_ekor'    => $ekor,
                        'weight_kg'   => $kg,
                        'price_basis' => $row['price_basis'],
                        'unit_price'  => $harga,
                        'subtotal'    => round($subtotal, 2),
                    ]);

                    // Harga pokok disimpan dalam DUA besaran supaya penjualan bisa
                    // dihitung baik per kg maupun per ekor tanpa menebak-nebak.
                    StockLot::create([
                        'item_id'           => $line->item_id,
                        'stock_in_line_id'  => $line->id,
                        'supplier_id'       => $in->supplier_id,
                        'date'              => $in->date,
                        'qty_ekor_initial'  => $ekor,
                        'weight_kg_initial' => $kg,
                        'qty_ekor_left'     => $ekor,
                        'weight_kg_left'    => $kg,
                        'cost_per_kg'       => $kg > 0 ? round($subtotal / $kg, 2) : 0,
                        'cost_per_ekor'     => $ekor > 0 ? round($subtotal / $ekor, 2) : 0,
                    ]);

                    $total += $subtotal;
                }

                if ($in->lines()->count() === 0) {
                    throw new \RuntimeException('Tidak ada baris barang yang diisi.');
                }

                $in->update(['total' => round($total, 2)]);

                // Nota memotong SALDO DEPOSIT supplier. Dilakukan di dalam transaksi
                // yang sama supaya stok dan saldo tidak pernah terpisah separuh jalan.
                app(DepositService::class)->chargePurchase($in->fresh());

                return $in->fresh(['lines.item', 'supplier']);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }

        $pesan = 'Pembelian tersimpan.';

        if ($stockIn->supplier_id) {
            $saldo = $stockIn->supplier->depositBalance();
            $pesan .= ' Saldo deposit ' . $stockIn->supplier->name . ' terpotong Rp '
                . number_format((float) $stockIn->total, 0, ',', '.') . '.';

            if ($saldo < -0.01) {
                // Saldo minus artinya uang yang disetor belum menutup nota ini —
                // disebut apa adanya supaya tidak dibaca sebagai supplier berutang.
                $pesan .= ' Perhatian: saldo minus, KITA BELUM BAYAR Rp '
                    . number_format(abs($saldo), 0, ',', '.') . ' ke supplier ini.';
            } else {
                $pesan .= ' Sisa saldo Rp ' . number_format($saldo, 0, ',', '.') . '.';
            }
        }

        return redirect()->route('farm.stock-in.show', $stockIn->id)->with('success', $pesan);
    }


    /**
     * Unggah foto bon dari supplier. Boleh beberapa lembar.
     * Berkas dikompres di sisi peramban sebelum dikirim (lihat view) supaya
     * foto kamera 3-5 MB tidak membebani kuota petugas gudang.
     */
    public function uploadPhoto(Request $request, StockIn $stockIn)
    {
        // Di HP bon difoto; di laptop bon sering sudah berupa PDF/scan dari supplier,
        // jadi keduanya diterima. PDF diberi batas lebih besar karena hasil scan
        // multi-halaman wajar lebih berat daripada foto yang sudah dikompres.
        $request->validate([
            'photos'   => ['required', 'array', 'max:5'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ], [], ['photos.*' => 'berkas bon']);

        $daftar = $stockIn->photoList();

        foreach ($request->file('photos', []) as $file) {
            $ext  = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
            $nama = 'bon-' . $stockIn->id . '-' . Str::random(8) . '.' . $ext;
            $path = $file->storeAs('farm/bon', $nama, 'public');
            $daftar[] = $path;
        }

        // Batasi 10 lembar per nota agar tidak menumpuk tanpa kendali.
        $stockIn->update(['photos' => array_slice($daftar, 0, 10)]);

        return back()->with('success', 'Foto bon tersimpan.');
    }

    /** Hapus satu lembar foto bon (berkasnya ikut dihapus dari disk). */
    public function deletePhoto(Request $request, StockIn $stockIn)
    {
        $path = (string) $request->input('path');
        $daftar = $stockIn->photoList();

        if (! in_array($path, $daftar, true)) {
            return back()->with('error', 'Foto tidak ditemukan pada nota ini.');
        }

        Storage::disk('public')->delete($path);
        $stockIn->update(['photos' => array_values(array_diff($daftar, [$path]))]);

        return back()->with('success', 'Foto bon dihapus.');
    }

    /**
     * Nota pembelian sebagai PDF (A5) — untuk diarsipkan atau dikirim ke supplier.
     * Berbeda dari struk thermal: ini dokumen yang rapi dibaca & dicetak di kertas biasa.
     */
    public function pdf(StockIn $stockIn)
    {
        $stockIn->load(['lines.item', 'supplier', 'user']);

        $pdf = Pdf::loadView('backend.farm.stock_in.pdf', [
            'row'    => $stockIn,
            'tenant' => app(\App\Tenancy\TenantManager::class)->tenant(),
        ])->setPaper('a5');

        return $pdf->download('Nota-Pembelian-' . $stockIn->invoice_no . '.pdf');
    }


    /* ==================== REALISASI (timbang ulang barang diterima) ==================== */

    /**
     * Catat realisasi satu nota — SATU KALI saja per nota.
     * Petugas mengisi angka NYATA yang diterima; arah uang dihitung sistem.
     */
    public function storeRealization(Request $request, StockIn $stockIn, RealizationService $svc)
    {
        $data = $request->validate([
            'date'                  => ['required', 'date'],
            'reason'                => ['required', 'in:' . implode(',', array_keys(\App\Models\Farm\StockInRealization::REASONS))],
            'lines'                 => ['required', 'array', 'min:1'],
            'lines.*.qty_ekor'      => ['nullable', 'integer', 'min:0'],
            'lines.*.weight_kg'     => ['nullable', 'numeric', 'min:0'],
            'notes'                 => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $r = $svc->record($stockIn, $data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Realisasi tercatat. ' . $r->effectLabel() . '.');
    }

    /** Batalkan realisasi nota ini (lot dikembalikan ke angka nota, saldo dibalik). */
    public function destroyRealization(StockIn $stockIn, RealizationService $svc)
    {
        $r = $stockIn->realization;
        if (! $r) {
            return back()->with('error', 'Nota ini belum punya realisasi.');
        }

        try {
            $svc->revert($r);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Realisasi dibatalkan — stok dan saldo supplier dikembalikan.');
    }

    /** Ubah status bayar KITA ke supplier (lunas / belum lunas). */
    public function setPayment(Request $request, StockIn $stockIn)
    {
        $data = $request->validate([
            'payment_status' => ['required', 'in:paid,unpaid'],
            'paid_at'        => ['nullable', 'date'],
        ]);

        if ($data['payment_status'] === 'paid') {
            $stockIn->update([
                'paid_amount'    => $stockIn->netTotal(),
                'payment_status' => 'paid',
                'paid_at'        => $data['paid_at'] ?? now()->toDateString(),
            ]);
        } else {
            $stockIn->update(['paid_amount' => 0, 'payment_status' => 'unpaid', 'paid_at' => null]);
        }

        return back()->with('success', 'Status pembayaran diperbarui.');
    }

    public function show(StockIn $stockIn)
    {
        $stockIn->load(['lines.item', 'supplier', 'user', 'realization.lines.line.item']);

        return view('backend.farm.stock_in.show', [
            'row'     => $stockIn,
            'saldo'   => $stockIn->supplier?->depositBalance() ?? 0,
            'alasan'  => \App\Models\Farm\StockInRealization::REASONS,
        ]);
    }

    /** Payload nota untuk mesin cetak (MoodaPrint). */
    public function receipt(StockIn $stockIn)
    {
        $stockIn->load(['lines.item', 'supplier']);

        return response()->json([
            'title'    => 'NOTA PEMBELIAN',
            'invoice_no' => $stockIn->invoice_no,
            'datetime' => $stockIn->date->format('d/m/Y'),
            'party'    => $stockIn->supplier?->name ?? 'Tanpa Supplier',
            'party_label' => 'Supplier',
            'items'    => $stockIn->lines->map(fn ($l) => [
                'name'      => $l->item?->name ?? '-',
                'qty_ekor'  => (int) $l->qty_ekor,
                'weight_kg' => (float) $l->weight_kg,
                'basis'     => $l->price_basis,
                'price'     => (float) $l->unit_price,
                'subtotal'  => (float) $l->subtotal,
            ]),
            'total'    => (float) $stockIn->total,
            'notes'    => $stockIn->notes,
        ]);
    }

    /** Nomor nota beli: BELI-YYYYMMDD-XXXXXX */
    private function generateInvoiceNo(): string
    {
        do {
            $no = 'BELI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (StockIn::withoutGlobalScopes()->where('invoice_no', $no)->exists());

        return $no;
    }
}

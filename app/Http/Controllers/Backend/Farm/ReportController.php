<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Agent;
use App\Models\Farm\Item;
use App\Models\Farm\Supplier;
use App\Services\Farm\ReportService;
use App\Tenancy\TenantManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * LAPORAN — satu pintu untuk seluruh laporan peternakan.
 *
 * Layar dan berkas PDF memakai DATA YANG SAMA dari ReportService, hanya beda
 * pembungkus. Dengan begitu angka yang dilihat di layar dan yang tercetak
 * mustahil berbeda — kesalahan yang paling sulit dipercaya klien.
 */
class ReportController extends Controller
{
    public function __construct(private ReportService $laporan) {}

    public function index(Request $request)
    {
        $jenis = $request->input('jenis', 'ringkasan');
        if (! isset(ReportService::JENIS[$jenis])) {
            $jenis = 'ringkasan';
        }

        $konf = ReportService::JENIS[$jenis];
        $periode = $request->input('periode', 'bulan-ini');
        if (! isset(ReportService::PERIODE[$periode])) {
            $periode = 'bulan-ini';
        }

        [$a, $b, $labelPeriode] = $this->laporan->rentang(
            $periode, $request->input('dari'), $request->input('sampai')
        );

        $data = $this->susun($jenis, $a, $b, $request);

        return view('backend.farm.reports.index', [
            'jenis'        => $jenis,
            'konf'         => $konf,
            'daftarJenis'  => ReportService::JENIS,
            'daftarPeriode' => ReportService::PERIODE,
            'periode'      => $periode,
            'dari'         => $a->toDateString(),
            'sampai'       => $b->toDateString(),
            'labelPeriode' => $labelPeriode,
            'pakaiPeriode' => in_array('periode', $konf['filter'], true),
            'suppliers'    => in_array('supplier', $konf['filter'], true)
                ? Supplier::orderBy('name')->get() : collect(),
            'agents'       => in_array('agen', $konf['filter'], true)
                ? Agent::orderBy('name')->get() : collect(),
            'items'        => in_array('item', $konf['filter'], true)
                ? Item::where('is_active', true)->orderBy('name')->get() : collect(),
            'supplierId'   => $request->input('supplier_id'),
            'agenId'       => $request->input('agen_id'),
            'itemId'       => $request->input('item_id'),
            'data'         => $data,
        ]);
    }

    /** Unduh laporan sebagai PDF dengan filter yang sedang dipakai di layar. */
    public function pdf(Request $request)
    {
        $jenis = $request->input('jenis', 'ringkasan');
        if (! isset(ReportService::JENIS[$jenis])) {
            abort(404);
        }

        $konf = ReportService::JENIS[$jenis];
        $periode = $request->input('periode', 'bulan-ini');
        [$a, $b, $labelPeriode] = $this->laporan->rentang(
            $periode, $request->input('dari'), $request->input('sampai')
        );

        $data = $this->susun($jenis, $a, $b, $request);

        // Laporan dengan banyak kolom dicetak mendatar supaya angkanya tidak
        // berdesakan; sisanya tegak karena lebih enak dibaca & diarsipkan.
        $mendatar = in_array($jenis, ['pembelian', 'penjualan', 'laba', 'piutang', 'susut'], true);

        $pdf = Pdf::loadView('backend.farm.reports.pdf', [
            'data'         => $data,
            'labelPeriode' => in_array('periode', $konf['filter'], true) ? $labelPeriode : null,
            'tenant'       => app(TenantManager::class)->tenant(),
            'dicetakOleh'  => auth()->user()?->name ?? '-',
        ])->setPaper('a4', $mendatar ? 'landscape' : 'portrait');

        $this->nomorHalaman($pdf);

        $nama = 'Laporan-' . Str::slug($data['judul']) . '-'
            . ($a->isSameDay($b) ? $a->format('Ymd') : $a->format('Ymd') . '-' . $b->format('Ymd')) . '.pdf';

        return $pdf->download($nama);
    }

    /**
     * Tulis "Halaman X dari Y" di kaki setiap halaman.
     *
     * counter(pages) pada CSS tidak dikenali DomPDF — hasilnya selalu 0. Satu-satunya
     * cara yang benar-benar tahu jumlah halaman adalah menuliskannya SETELAH dokumen
     * selesai dirender, lewat kanvas, memakai penanda {PAGE_NUM}/{PAGE_COUNT}.
     */
    private function nomorHalaman($pdf): void
    {
        $pdf->render();

        $dom    = $pdf->getDomPDF();
        $kanvas = $dom->getCanvas();
        $font   = $dom->getFontMetrics()->getFont('DejaVu Sans', 'normal');

        $kanvas->page_text(
            $kanvas->get_width() - 132,
            $kanvas->get_height() - 30,
            'Halaman {PAGE_NUM} dari {PAGE_COUNT}',
            $font, 7.5, [0.61, 0.64, 0.69]
        );
    }

    /** Panggil penyusun laporan yang sesuai beserta filter yang berlaku untuknya. */
    private function susun(string $jenis, $a, $b, Request $request): array
    {
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;
        $agenId     = $request->filled('agen_id') ? (int) $request->input('agen_id') : null;
        $itemId     = $request->filled('item_id') ? (int) $request->input('item_id') : null;

        return match ($jenis) {
            'pembelian'     => $this->laporan->pembelian($a, $b, $supplierId),
            'penjualan'     => $this->laporan->penjualan($a, $b, $agenId),
            'laba'          => $this->laporan->labaHarian($a, $b),
            'kartu-stok'    => $this->laporan->kartuStok($a, $b, $itemId),
            'stok-supplier' => $this->laporan->stokSupplier($supplierId),
            'deposit'       => $this->laporan->deposit($a, $b, $supplierId),
            'piutang'       => $this->laporan->piutang($agenId),
            'susut'         => $this->laporan->susut($a, $b, $itemId),
            default         => $this->laporan->ringkasan($a, $b),
        };
    }
}

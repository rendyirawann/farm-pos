<?php

namespace App\Console\Commands;

use App\Models\Farm\Item;
use App\Models\Farm\StockOut;
use App\Models\Farm\StockOutLine;
use App\Models\Tenant;
use App\Services\Farm\FarmStockService;
use App\Tenancy\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * PERBAIKI STOK BARANG PRODUKSI SENDIRI (telur) PADA NOTA LAMA.
 *
 * Sebelum 5 Agustus 2026 21:21, penjualan barang produksi sendiri hanya menghitung
 * harga pokoknya dan TIDAK mengurangi lot produksinya. Akibatnya telur yang sudah
 * terjual masih tercatat sebagai stok, dan bisa dipilih lagi di Barang Keluar.
 *
 * Perintah ini menutup selisih itu: setiap baris nota keluar milik barang produksi
 * yang belum punya rincian pemakaian lot diproses ulang lewat FIFO, urut tanggal
 * nota — supaya lot tertua yang terpakai lebih dulu, sama seperti bila notanya
 * disimpan hari itu.
 *
 * Nota TIDAK dihapus dan tidak dibuat ulang, jadi nomor nota & riwayatnya utuh.
 */
class FarmRepairProducedStock extends Command
{
    protected $signature = 'farm:repair-produced-stock
        {--tenant= : ID tenant (bawaan: semua tenant peternakan)}
        {--apply : Terapkan perubahan. Tanpa ini hanya menampilkan rencana}';

    protected $description = 'Kurangi stok barang produksi sendiri (telur) untuk nota keluar lama yang belum memotong lot';

    public function handle(TenantManager $tenancy, FarmStockService $stok): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::whereKey((int) $this->option('tenant'))->get()
            : Tenant::where('vertical', 'farm')->get();

        $terapkan = (bool) $this->option('apply');
        if (! $terapkan) {
            $this->warn('MODE PERIKSA — tidak ada yang diubah. Tambahkan --apply untuk menerapkan.');
        }

        foreach ($tenants as $tenant) {
            $tenancy->setTenant($tenant);
            $this->line('');
            $this->info("Tenant: {$tenant->name} (#{$tenant->id})");

            $produksi = Item::where('is_produced', true)->pluck('id');
            if ($produksi->isEmpty()) {
                $this->line('    tidak ada barang produksi sendiri.');
                continue;
            }

            // Urut tanggal nota supaya FIFO memakai lot yang benar, bukan urutan input.
            $baris = StockOutLine::whereIn('item_id', $produksi)
                ->whereDoesntHave('lotUsages')
                ->with(['item', 'stockOut'])
                ->get()
                ->filter(fn (StockOutLine $l) => (int) $l->qty_ekor > 0 || (float) $l->weight_kg > 0)
                ->sortBy(fn (StockOutLine $l) => [$l->stockOut?->date?->toDateString() ?? '', $l->stock_out_id]);

            if ($baris->isEmpty()) {
                $this->line('    tidak ada nota yang perlu diperbaiki.');
                continue;
            }

            $tabel = [];
            foreach ($baris as $l) {
                $tabel[] = [
                    $l->stockOut?->invoice_no ?? '-',
                    $l->stockOut?->date?->format('d/m/Y') ?? '-',
                    $l->item?->name ?? '-',
                    (int) $l->qty_ekor,
                    'Rp ' . number_format((float) $l->cost, 0, ',', '.'),
                ];
            }
            $this->table(['No. Nota', 'Tanggal', 'Barang', 'Butir', 'HPP Lama'], $tabel);

            if (! $terapkan) {
                continue;
            }

            $stok->warnings = [];
            $diperbaiki = 0;

            DB::transaction(function () use ($baris, $stok, &$diperbaiki) {
                foreach ($baris as $l) {
                    $hasil = $stok->consumeFifo(
                        $l->item_id, (float) $l->weight_kg, (int) $l->qty_ekor
                    );

                    if (empty($hasil['usages'])) {
                        continue;   // tidak ada lot yang bisa dipotong
                    }

                    $stok->recordUsages($l, $hasil['usages']);

                    // Harga pokok baris ikut diperbarui HANYA bila FIFO memberi angka:
                    // bila lotnya berharga pokok Rp 0 (biaya operasional belum dicatat),
                    // biarkan nilai lama supaya laba lama tidak berubah tanpa alasan.
                    if ($hasil['cost'] > 0) {
                        $l->update([
                            'cost'   => $hasil['cost'],
                            'profit' => round((float) $l->subtotal - $hasil['cost'], 2),
                        ]);

                        $out = $l->stockOut;
                        if ($out) {
                            $out->update([
                                'total_cost'   => round((float) $out->lines()->sum('cost'), 2),
                                'gross_profit' => round((float) $out->lines()->sum('profit'), 2),
                            ]);
                        }
                    }

                    $diperbaiki++;
                }
            });

            $this->info("    {$diperbaiki} baris nota diperbaiki — stoknya sudah terpotong.");
            foreach ($stok->warnings as $w) {
                $this->warn('    ' . $w);
            }

            foreach (Item::where('is_produced', true)->get() as $item) {
                $s = $item->stock();
                $this->line(sprintf('    sisa %s: %s butir', $item->name, number_format($s['ekor'], 0, ',', '.')));
            }
        }

        return self::SUCCESS;
    }
}

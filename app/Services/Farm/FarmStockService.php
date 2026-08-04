<?php

namespace App\Services\Farm;

use App\Models\Farm\Item;
use App\Models\Farm\StockAdjustment;
use App\Models\Farm\StockLot;
use App\Models\Farm\StockOutLine;
use App\Models\Farm\StockOutLotUsage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mesin stok Mooda Stok (modul peternakan).
 *
 * Dua besaran dipakai berdampingan: EKOR dan KG. Pengambilan FIFO memakai KG
 * sebagai patokan utama (karena harga umumnya per kg), dan jumlah ekor ikut
 * berkurang secara proporsional terhadap lot yang terpakai. Menyimpan keduanya
 * membuat susut bobot terlihat: 100 ekor/200kg masuk, 100 ekor/195kg keluar.
 */
class FarmStockService
{
    /** Peringatan non-fatal dari proses terakhir (mis. stok kurang). */
    public array $warnings = [];

    /**
     * Ambil stok item secara FIFO.
     *
     * @return array{cost: float, usages: array<int, array{lot_id:int, qty_ekor:int, weight_kg:float, cost:float}>}
     */
    public function consumeFifo(int $itemId, float $weightKg, int $qtyEkor): array
    {
        $sisaKg   = round($weightKg, 2);
        $sisaEkor = $qtyEkor;
        $totalCost = 0.0;
        $usages = [];

        $lots = StockLot::where('item_id', $itemId)->available()->fifo()->lockForUpdate()->get();

        foreach ($lots as $lot) {
            if ($sisaKg <= 0 && $sisaEkor <= 0) {
                break;
            }

            [$ambilKg, $ambilEkor, $cost, $lewati] = $this->alokasiLot($lot, $sisaKg, $sisaEkor);

            if ($lewati) {
                continue;
            }

            $sisaKgLot   = round((float) $lot->weight_kg_left - $ambilKg, 2);
            $sisaEkorLot = (int) $lot->qty_ekor_left - $ambilEkor;

            // Bobot lot habis tapi ekor masih tersisa: sisa ekor itu tidak punya
            // nilai lagi (seluruh nilai lot dinilai per kg dan sudah terpakai).
            // Dibiarkan menggantung, ia akan ikut antrean FIFO dengan harga pokok 0
            // dan menciptakan laba palsu — jadi ditutup di sini.
            $ekorHantu = 0;
            if ($this->dinilaiPerKg($lot) && $sisaKgLot <= 0.001 && $sisaEkorLot > 0) {
                $ekorHantu   = $sisaEkorLot;
                $sisaEkorLot = 0;
            }

            $lot->update([
                'weight_kg_left' => max(0, $sisaKgLot),
                'qty_ekor_left'  => max(0, $sisaEkorLot),
            ]);

            if ($ekorHantu > 0) {
                $this->warnings[] = sprintf(
                    'Lot #%d: bobot habis tetapi %d ekor masih tercatat — selisih ekor ini ditutup '
                    . '(nilainya sudah terhitung pada kilogram yang terjual).',
                    $lot->id, $ekorHantu
                );
            }

            $usages[] = [
                'lot_id'    => $lot->id,
                'qty_ekor'  => $ambilEkor,
                'weight_kg' => round($ambilKg, 2),
                'cost'      => round($cost, 2),
            ];

            $totalCost += $cost;
            $sisaKg    = round($sisaKg - $ambilKg, 2);
            $sisaEkor -= $ambilEkor;
        }

        // Stok kurang: transaksi TIDAK digagalkan (barang fisik memang sudah keluar),
        // tapi kekurangan dicatat agar terlihat dan bisa diluruskan lewat penyesuaian.
        if ($sisaKg > 0.001 || $sisaEkor > 0) {
            $item = Item::find($itemId);
            $this->warnings[] = sprintf(
                'Stok %s kurang %s kg / %d ekor — bagian ini tercatat berharga pokok 0. Luruskan lewat Penyesuaian Stok.',
                $item->name ?? "#{$itemId}",
                number_format(max(0, $sisaKg), 2),
                max(0, $sisaEkor)
            );
        }

        return ['cost' => round($totalCost, 2), 'usages' => $usages];
    }

    /**
     * Berapa yang diambil dari satu lot, dan berapa nilainya.
     *
     * KILOGRAM adalah satuan penilai; jumlah ekor hanyalah keterangan. Sebabnya:
     * kalau nilai boleh dihitung dari salah satu besaran mana pun yang tersedia,
     * satu lot bisa "mengeluarkan" nilai lewat ekor sementara kilogramnya masih
     * utuh dan tetap dihitung sebagai persediaan — nilai keluar jadi lebih besar
     * daripada nilai masuk, dan laporan HPP tidak akan pernah bisa ditutup.
     *
     * Hanya lot yang benar-benar tanpa bobot (mis. telur yang dihitung per butir)
     * yang dinilai per ekor/butir.
     *
     * @return array{0: float, 1: int, 2: float, 3: bool}  [ambilKg, ambilEkor, biaya, lewati]
     */
    private function alokasiLot(StockLot $lot, float $sisaKg, int $sisaEkor): array
    {
        if (! $this->dinilaiPerKg($lot)) {
            $ambilEkor = min($sisaEkor, (int) $lot->qty_ekor_left);

            return [0.0, $ambilEkor, $ambilEkor * (float) $lot->cost_per_ekor, $ambilEkor <= 0];
        }

        $ambilKg = min($sisaKg, (float) $lot->weight_kg_left);

        // Kebutuhan kilogram sudah terpenuhi sementara lot ini masih berbobot:
        // jangan mengambil ekornya. Mengambil ekor tanpa kilogram akan memisahkan
        // jumlah fisik dari nilainya.
        if ($ambilKg <= 0) {
            return [0.0, 0, 0.0, true];
        }

        // Ekor mengikuti kilogram yang diambil, sebatas isi lot dan sebatas yang
        // memang dijual. Sisa ekor yang menggantung setelah bobot lot habis
        // ditutup oleh pemanggil (bukan dijual), supaya angka "terjual" pada
        // laporan tetap sama dengan yang benar-benar keluar.
        $ambilEkor = max(0, min($sisaEkor, (int) $lot->qty_ekor_left));

        return [$ambilKg, $ambilEkor, $ambilKg * (float) $lot->cost_per_kg, false];
    }

    /** Lot berbobot dinilai per kilogram; yang tanpa bobot dinilai per ekor/butir. */
    private function dinilaiPerKg(StockLot $lot): bool
    {
        return (float) $lot->weight_kg_initial > 0;
    }

    /** Catat pemakaian lot untuk satu baris penjualan. */
    public function recordUsages(StockOutLine $line, array $usages): void
    {
        foreach ($usages as $u) {
            StockOutLotUsage::create([
                'stock_out_line_id' => $line->id,
                'lot_id'    => $u['lot_id'],
                'qty_ekor'  => $u['qty_ekor'],
                'weight_kg' => $u['weight_kg'],
                'cost'      => $u['cost'],
            ]);
        }
    }

    /**
     * Kembalikan stok ke lot asalnya (dipakai saat nota penjualan dibatalkan/dihapus).
     * Mengembalikan ke lot yang sama, bukan membuat lot baru, supaya urutan FIFO utuh.
     */
    public function restoreFromStockOut(StockOutLine $line): void
    {
        foreach ($line->lotUsages()->get() as $u) {
            $lot = StockLot::find($u->lot_id);
            if ($lot) {
                $lot->update([
                    'weight_kg_left' => round((float) $lot->weight_kg_left + (float) $u->weight_kg, 2),
                    'qty_ekor_left'  => (int) $lot->qty_ekor_left + (int) $u->qty_ekor,
                ]);
            }
            $u->delete();
        }
    }

    /**
     * Terapkan penyesuaian stok (mati / susut / rusak / koreksi).
     * Pengurangan mengikuti FIFO bila lot tidak ditentukan.
     */
    public function applyAdjustment(StockAdjustment $adj): float
    {
        return DB::transaction(function () use ($adj) {
            $kg   = abs((float) $adj->weight_kg);
            $ekor = abs((int) $adj->qty_ekor);

            // Koreksi TAMBAH: buat lot baru berharga pokok rata-rata item saat ini,
            // supaya barang temuan tidak masuk dengan nilai 0.
            if ($adj->isAddition()) {
                $avg = $this->averageCostPerKg($adj->item_id);
                StockLot::create([
                    'item_id'           => $adj->item_id,
                    'stock_in_line_id'  => null,
                    'supplier_id'       => null,
                    'date'              => $adj->date,
                    'qty_ekor_initial'  => $ekor,
                    'weight_kg_initial' => $kg,
                    'qty_ekor_left'     => $ekor,
                    'weight_kg_left'    => $kg,
                    'cost_per_kg'       => $avg,
                    'cost_per_ekor'     => $ekor > 0 ? round($kg * $avg / $ekor, 2) : 0,
                ]);

                $dampak = round($kg * $avg, 2);
                $adj->update(['cost_impact' => $dampak]);

                return $dampak;
            }

            // Pengurangan: dari lot tertentu bila dipilih, kalau tidak ikuti FIFO.
            if ($adj->lot_id) {
                $lot = StockLot::whereKey($adj->lot_id)->lockForUpdate()->first();
                if (! $lot) {
                    throw new RuntimeException('Lot tidak ditemukan.');
                }
                // Aturan penilaian sama dengan FIFO: kilogram yang menentukan nilai.
                [$ambilKg, $ambilEkor, $biaya] = $this->alokasiLot($lot, $kg, $ekor);
                $lot->update([
                    'weight_kg_left' => max(0, round((float) $lot->weight_kg_left - $ambilKg, 2)),
                    'qty_ekor_left'  => max(0, (int) $lot->qty_ekor_left - $ambilEkor),
                ]);
                $dampak = round($biaya, 2);

                $pemakaian = [[
                    'lot_id' => $lot->id, 'qty_ekor' => $ambilEkor,
                    'weight_kg' => round($ambilKg, 2), 'cost' => $dampak,
                ]];
            } else {
                $hasil  = $this->consumeFifo($adj->item_id, $kg, $ekor);
                $dampak = $hasil['cost'];
                $pemakaian = $hasil['usages'];
            }

            // Jejak lot yang susut. Tanpa ini, kerugian penyesuaian tidak bisa
            // dibebankan ke supplier mana pun sehingga HPP per supplier di menu
            // Gudang jadi angka gantung.
            $this->recordAdjustmentUsages($adj, $pemakaian);

            $adj->update(['cost_impact' => $dampak]);

            return $dampak;
        });
    }

    /** Simpan rincian lot mana saja yang berkurang karena satu penyesuaian. */
    private function recordAdjustmentUsages(StockAdjustment $adj, array $usages): void
    {
        DB::table('farm_adjustment_lot_usages')->where('adjustment_id', $adj->id)->delete();

        foreach ($usages as $u) {
            if ($u['weight_kg'] <= 0 && $u['qty_ekor'] <= 0) {
                continue;
            }
            DB::table('farm_adjustment_lot_usages')->insert([
                'tenant_id'     => $adj->tenant_id,
                'adjustment_id' => $adj->id,
                'lot_id'        => $u['lot_id'],
                'qty_ekor'      => $u['qty_ekor'],
                'weight_kg'     => $u['weight_kg'],
                'cost'          => $u['cost'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    /** Harga pokok rata-rata per kg dari lot yang masih tersisa. */
    public function averageCostPerKg(int $itemId): float
    {
        $row = StockLot::where('item_id', $itemId)
            ->selectRaw('COALESCE(SUM(weight_kg_left * cost_per_kg),0) as nilai, COALESCE(SUM(weight_kg_left),0) as kg')
            ->first();

        $kg = (float) ($row->kg ?? 0);

        return $kg > 0 ? round((float) $row->nilai / $kg, 2) : 0.0;
    }

    /**
     * Pratinjau harga pokok TANPA mengubah stok — dipakai layar Stock Out agar
     * margin terlihat saat kasir masih mengetik harga jual.
     */
    public function previewCost(int $itemId, float $weightKg, int $qtyEkor): array
    {
        $sisaKg = round($weightKg, 2);
        $sisaEkor = $qtyEkor;
        $total = 0.0;
        $lots = [];

        // Aturan alokasi HARUS sama dengan consumeFifo. Kalau pratinjau memakai
        // rumus lain, angka margin yang dilihat penjual bukan angka yang tersimpan.
        foreach (StockLot::where('item_id', $itemId)->available()->fifo()->get() as $lot) {
            if ($sisaKg <= 0 && $sisaEkor <= 0) {
                break;
            }

            [$ambilKg, $ambilEkor, $cost, $lewati] = $this->alokasiLot($lot, $sisaKg, $sisaEkor);
            if ($lewati) {
                continue;
            }

            $total += $cost;
            $lots[] = [
                'lot_id'      => $lot->id,
                'tanggal'     => optional($lot->date)->format('d/m/Y'),
                'supplier_id' => $lot->supplier_id,
                'supplier'    => $lot->supplier?->name,
                'weight_kg'   => round($ambilKg, 2),
                'qty_ekor'    => $ambilEkor,
                'cost_per_kg' => (float) $lot->cost_per_kg,
            ];
            $sisaKg = round($sisaKg - $ambilKg, 2);
            $sisaEkor -= $ambilEkor;
        }

        // Harga pokok TERTIMBANG atas seluruh lot yang akan terpakai. Kalau hanya lot
        // pertama yang ditampilkan, penjual bisa yakin marginnya Rp2.000 padahal
        // rata-rata sebenarnya jauh lebih tinggi.
        $terpakaiKg   = round(array_sum(array_column($lots, 'weight_kg')), 2);
        $terpakaiEkor = (int) array_sum(array_column($lots, 'qty_ekor'));

        return [
            'cost'        => round($total, 2),
            'lots'        => $lots,
            'kurang_kg'   => max(0, $sisaKg),
            'kurang_ekor' => max(0, $sisaEkor),
            'hpp_per_kg'  => $terpakaiKg > 0.001 ? round($total / $terpakaiKg, 2) : null,
            'hpp_per_ekor' => $terpakaiEkor > 0 ? round($total / $terpakaiEkor, 2) : null,
            'terpakai_kg'   => $terpakaiKg,
            'terpakai_ekor' => $terpakaiEkor,
            // Acuan harga beli terakhir diambil dari supplier lot yang akan dipakai
            // FIFO — bukan lintas supplier, karena harga tiap supplier bisa jauh beda
            // (ayam kecil vs besar) dan salah acuan justru menekan harga jual.
            'acuan' => $this->hargaBeliTerakhir($itemId, $lots[0]['supplier_id'] ?? null),
        ];
    }

    /**
     * Harga beli terakhir untuk (item, supplier) — sudah termasuk koreksi realisasi,
     * karena angkanya diambil dari lot, bukan dari baris nota mentah.
     *
     * @return array{state:string, harga_kg:?float, harga_ekor:?float, tanggal:?string, supplier:?string, umur_hari:?int}
     */
    public function hargaBeliTerakhir(int $itemId, ?int $supplierId): array
    {
        $kosong = ['state' => 'none', 'harga_kg' => null, 'harga_ekor' => null,
            'tanggal' => null, 'supplier' => null, 'umur_hari' => null];

        if (! $supplierId) {
            // Telur / barang produksi sendiri tidak punya harga beli — acuan pembelian
            // tidak boleh dikarang 0, itu membuat peringatan jadi sampah.
            return $kosong;
        }

        $lot = StockLot::with('supplier')
            ->where('item_id', $itemId)
            ->where('supplier_id', $supplierId)
            ->where('source', 'purchase')
            ->where(fn ($q) => $q->where('weight_kg_initial', '>', 0)->orWhere('qty_ekor_initial', '>', 0))
            ->orderByDesc('date')->orderByDesc('id')
            ->first();

        if (! $lot) {
            return $kosong;
        }

        $umur = (int) $lot->date->diffInDays(now());

        return [
            // Lebih dari 14 hari: harga ayam hidup berubah cepat, acuan sudah basi
            // dan tidak layak dipakai menyarankan harga.
            'state'      => $umur > 14 ? 'stale' : 'ok',
            'harga_kg'   => (float) $lot->cost_per_kg > 0 ? (float) $lot->cost_per_kg : null,
            'harga_ekor' => (float) $lot->cost_per_ekor > 0 ? (float) $lot->cost_per_ekor : null,
            'tanggal'    => $lot->date->format('d/m/Y'),
            'supplier'   => $lot->supplier?->name,
            'umur_hari'  => $umur,
        ];
    }
}

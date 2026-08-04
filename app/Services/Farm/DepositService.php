<?php

namespace App\Services\Farm;

use App\Models\Farm\StockIn;
use App\Models\Farm\Supplier;
use App\Models\Farm\SupplierDeposit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * DEPOSIT SUPPLIER — buku besar APPEND-ONLY.
 *
 * Saldo = SUM(amount) atas seluruh baris. Tidak ada baris yang diubah atau
 * dihapus: pembatalan dibukukan sebagai BARIS BALIK yang menunjuk baris asalnya
 * (kolom reverses_id). Sebabnya sederhana — kalau baris lama boleh disunting,
 * saldo bisa berubah tanpa jejak dan selisihnya tidak akan pernah bisa dilacak.
 */
class DepositService
{
    /** Setoran uang dari owner ke supplier. Bukti transfer disimpan bila ada. */
    public function topup(Supplier $supplier, array $data, ?string $proofPath = null): SupplierDeposit
    {
        $jumlah = round((float) $data['amount'], 2);
        if ($jumlah <= 0) {
            throw new RuntimeException('Jumlah deposit harus lebih dari 0.');
        }

        return SupplierDeposit::create([
            'supplier_id' => $supplier->id,
            'date'        => $data['date'] ?? now()->toDateString(),
            'type'        => 'topup',
            'amount'      => $jumlah,          // positif: menambah saldo
            'proof_path'  => $proofPath,
            'user_id'     => Auth::id(),
            'notes'       => $data['notes'] ?? null,
        ]);
    }

    /**
     * Potong saldo karena nota barang masuk.
     * Idempoten: bila nota ini sudah pernah dipotong, tidak dipotong dua kali.
     */
    public function chargePurchase(StockIn $stockIn): ?SupplierDeposit
    {
        if (! $stockIn->supplier_id) {
            return null;    // pembelian tanpa supplier tidak menyentuh deposit
        }

        return DB::transaction(function () use ($stockIn) {
            $sudah = SupplierDeposit::where('reference_type', 'stock_in')
                ->where('reference_id', $stockIn->id)
                ->where('type', 'purchase')
                ->whereNull('reverses_id')
                ->lockForUpdate()
                ->first();

            if ($sudah) {
                return $sudah;      // sudah pernah dipotong -> jangan dobel
            }

            return SupplierDeposit::create([
                'supplier_id'    => $stockIn->supplier_id,
                'date'           => $stockIn->date,
                'type'           => 'purchase',
                'amount'         => -1 * round((float) $stockIn->total, 2),  // negatif: mengurangi saldo
                'reference_type' => 'stock_in',
                'reference_id'   => $stockIn->id,
                'user_id'        => Auth::id(),
                'notes'          => 'Nota pembelian ' . $stockIn->invoice_no,
            ]);
        });
    }

    /**
     * Koreksi saldo karena realisasi. Nilai BERTANDA:
     * barang kurang -> positif (saldo naik, kita kelebihan potong);
     * barang lebih   -> negatif (saldo turun, kita kurang potong).
     *
     * Idempoten per realisasi: satu realisasi menghasilkan paling banyak satu
     * baris aktif. Bila nilainya berubah, baris lama dibalik lalu ditulis baru.
     */
    public function adjustForRealization(int $realizationId, int $supplierId, $tanggal, float $nilai, string $keterangan): ?SupplierDeposit
    {
        if (abs($nilai) < 0.01) {
            return null;
        }

        return DB::transaction(function () use ($realizationId, $supplierId, $tanggal, $nilai, $keterangan) {
            $this->reverseByReference('realization', $realizationId, 'Koreksi ulang realisasi');

            return SupplierDeposit::create([
                'supplier_id'    => $supplierId,
                'date'           => $tanggal,
                'type'           => 'realization',
                'amount'         => round($nilai, 2),
                'reference_type' => 'realization',
                'reference_id'   => $realizationId,
                'user_id'        => Auth::id(),
                'notes'          => $keterangan,
            ]);
        });
    }

    /**
     * Batalkan pengaruh sebuah dokumen pada saldo dengan BARIS BALIK.
     * Baris asli tidak disentuh — itulah inti buku besar append-only.
     */
    public function reverseByReference(string $type, int $id, string $alasan): int
    {
        return DB::transaction(function () use ($type, $id, $alasan) {
            $baris = SupplierDeposit::where('reference_type', $type)
                ->where('reference_id', $id)
                ->whereNull('reverses_id')
                ->lockForUpdate()
                ->get();

            $n = 0;
            foreach ($baris as $b) {
                // Sudah pernah dibalik? jangan dibalik lagi.
                $adaBalik = SupplierDeposit::where('reverses_id', $b->id)->exists();
                if ($adaBalik) {
                    continue;
                }

                SupplierDeposit::create([
                    'supplier_id'    => $b->supplier_id,
                    'date'           => now()->toDateString(),
                    'type'           => $b->type,
                    'amount'         => -1 * (float) $b->amount,
                    'reference_type' => $b->reference_type,
                    'reference_id'   => $b->reference_id,
                    'reverses_id'    => $b->id,
                    'user_id'        => Auth::id(),
                    'notes'          => $alasan . ' (pembalikan baris #' . $b->id . ')',
                ]);
                $n++;
            }

            return $n;
        });
    }

    /** Koreksi manual dengan alasan tertulis — wajib ada catatan. */
    public function manualAdjust(Supplier $supplier, float $nilai, string $tanggal, string $catatan): SupplierDeposit
    {
        if (trim($catatan) === '') {
            throw new RuntimeException('Koreksi manual wajib disertai alasan.');
        }
        if (abs($nilai) < 0.01) {
            throw new RuntimeException('Nilai koreksi tidak boleh 0.');
        }

        return SupplierDeposit::create([
            'supplier_id' => $supplier->id,
            'date'        => $tanggal,
            'type'        => 'manual',
            'amount'      => round($nilai, 2),
            'user_id'     => Auth::id(),
            'notes'       => $catatan,
        ]);
    }

    public function balance(int $supplierId): float
    {
        return round((float) SupplierDeposit::where('supplier_id', $supplierId)->sum('amount'), 2);
    }

    /** Ringkasan per jenis untuk halaman detail supplier. */
    public function summary(int $supplierId): array
    {
        $rows = SupplierDeposit::where('supplier_id', $supplierId)
            ->selectRaw('type, COALESCE(SUM(amount),0) as total')
            ->groupBy('type')->pluck('total', 'type');

        return [
            'topup'       => round((float) ($rows['topup'] ?? 0), 2),
            'purchase'    => round((float) ($rows['purchase'] ?? 0), 2),
            'realization' => round((float) ($rows['realization'] ?? 0), 2),
            'manual'      => round((float) ($rows['manual'] ?? 0), 2),
            'saldo'       => round((float) $rows->sum(), 2),
        ];
    }
}

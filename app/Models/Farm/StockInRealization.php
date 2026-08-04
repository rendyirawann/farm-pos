<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * REALISASI — hasil timbang ulang barang yang benar-benar diterima dari supplier.
 *
 * SATU NOTA HANYA PUNYA SATU REALISASI. Isinya angka NYATA per barang, bukan
 * selisih, sehingga menyimpan dua kali tidak pernah menggandakan koreksi.
 *
 * Tidak ada lagi istilah "piutang supplier": selisihnya langsung menyesuaikan
 * SALDO DEPOSIT supplier —
 *   barang kurang -> saldo NAIK  (kita kelebihan potong saat nota dicatat)
 *   barang lebih  -> saldo TURUN (potongan tadi kurang)
 *
 * Berbeda dari StockAdjustment: penyesuaian terjadi setelah barang ada di gudang
 * (ayam mati, susut kandang) — itu kerugian kita sendiri dan TIDAK pernah
 * menyentuh saldo supplier.
 */
class StockInRealization extends Model
{
    use BelongsToTenant;

    public const REASONS = [
        'kurang_timbang' => 'Selisih Timbangan',
        'mati'           => 'Mati saat Diterima',
        'susut'          => 'Susut Perjalanan',
        'lebih'          => 'Barang Lebih dari Nota',
        'lainnya'        => 'Lainnya',
    ];

    protected $table = 'farm_stock_in_realizations';
    protected $fillable = ['tenant_id', 'stock_in_id', 'supplier_id', 'date', 'reason',
        'delta_qty_ekor', 'delta_weight_kg', 'value', 'user_id', 'notes'];
    protected $casts = ['date' => 'date', 'delta_weight_kg' => 'decimal:2', 'value' => 'decimal:2'];

    public function stockIn()  { return $this->belongsTo(StockIn::class, 'stock_in_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function user()     { return $this->belongsTo(User::class, 'user_id'); }
    public function lines()    { return $this->hasMany(StockInRealizationLine::class, 'realization_id'); }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /** Barang kurang dari nota — nilai koreksi menambah saldo supplier. */
    public function isShort(): bool
    {
        return (float) $this->value > 0.01;
    }

    /** Barang lebih dari nota — saldo supplier ikut terpotong lagi. */
    public function isOver(): bool
    {
        return (float) $this->value < -0.01;
    }

    /** Kalimat awam untuk layar & nota, supaya arah uangnya tidak perlu ditafsirkan. */
    public function effectLabel(): string
    {
        $rp = 'Rp ' . number_format(abs((float) $this->value), 0, ',', '.');

        if ($this->isShort()) {
            return 'Barang kurang — saldo supplier NAIK ' . $rp;
        }
        if ($this->isOver()) {
            return 'Barang lebih — saldo supplier TURUN ' . $rp;
        }

        return 'Sesuai nota — saldo supplier tidak berubah';
    }
}

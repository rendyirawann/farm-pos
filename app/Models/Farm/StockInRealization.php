<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * REALISASI — barang dari supplier ternyata kurang saat ditimbang ulang.
 * Nilai kekurangannya menjadi PIUTANG SUPPLIER (supplier berutang ke kita)
 * dan bisa ditutup oleh pembelian berikutnya dari supplier yang sama.
 *
 * Berbeda dari StockAdjustment yang mencatat kerugian kita sendiri di gudang.
 */
class StockInRealization extends Model
{
    use BelongsToTenant;

    public const REASONS = [
        'kurang_timbang' => 'Kurang Timbang',
        'mati'           => 'Mati saat Diterima',
        'susut'          => 'Susut Perjalanan',
        'lainnya'        => 'Lainnya',
    ];

    protected $table = 'farm_stock_in_realizations';
    protected $fillable = ['tenant_id', 'stock_in_id', 'stock_in_line_id', 'supplier_id', 'date',
        'reason', 'qty_ekor_short', 'weight_kg_short', 'value', 'settled_amount', 'status',
        'user_id', 'notes'];
    protected $casts = ['date' => 'date', 'weight_kg_short' => 'decimal:2',
        'value' => 'decimal:2', 'settled_amount' => 'decimal:2'];

    public function stockIn()    { return $this->belongsTo(StockIn::class, 'stock_in_id'); }
    public function line()       { return $this->belongsTo(StockInLine::class, 'stock_in_line_id'); }
    public function supplier()   { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function user()       { return $this->belongsTo(User::class, 'user_id'); }
    public function settlements(){ return $this->hasMany(SupplierSettlement::class, 'realization_id'); }

    public function reasonLabel(): string { return self::REASONS[$this->reason] ?? $this->reason; }

    /** Sisa piutang yang belum ditutup pembelian berikutnya. */
    public function remaining(): float
    {
        return max(0, round((float) $this->value - (float) $this->settled_amount, 2));
    }

    public function isSettled(): bool
    {
        return $this->remaining() <= 0.01;
    }
}

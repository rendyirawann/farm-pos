<?php

namespace App\Models\Fnb;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Kartu stok (ledger). cost_total = COGS gerakan = qty × harga lot. */
class StockMovement extends Model
{
    use BelongsToTenant;

    public const REASONS = [
        'purchase'                      => 'Pembelian',
        'sales_deduction'               => 'Terpakai penjualan',
        'sales_deduction_out_of_stock'   => 'Terpakai (stok kurang)',
        'stock_opname'                  => 'Stok opname',
        'adjustment'                    => 'Koreksi',
        'waste'                         => 'Rusak / terbuang',
    ];

    protected $fillable = [
        'tenant_id', 'ingredient_id', 'ingredient_batch_id', 'order_detail_id',
        'type', 'quantity', 'cost_total', 'reason', 'reference',
    ];

    protected $casts = ['quantity' => 'decimal:2', 'cost_total' => 'decimal:2'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function batch()
    {
        return $this->belongsTo(IngredientBatch::class, 'ingredient_batch_id');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ($this->reason ?: '-');
    }
}

<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu lot = satu baris pembelian. Stok berkurang dari lot TERTUA lebih dulu (FIFO),
 * sehingga harga pokok penjualan mengikuti harga beli nyata, bukan rata-rata.
 */
class StockLot extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_stock_lots';
    protected $fillable = ['tenant_id', 'item_id', 'stock_in_line_id', 'supplier_id', 'date',
        'qty_ekor_initial', 'weight_kg_initial', 'qty_ekor_left', 'weight_kg_left',
        'cost_per_kg', 'cost_per_ekor'];
    protected $casts = [
        'date' => 'date',
        'weight_kg_initial' => 'decimal:2', 'weight_kg_left' => 'decimal:2',
        'cost_per_kg' => 'decimal:2', 'cost_per_ekor' => 'decimal:2',
    ];

    public function item()    { return $this->belongsTo(Item::class, 'item_id'); }
    public function supplier(){ return $this->belongsTo(Supplier::class, 'supplier_id'); }

    /** Urutan FIFO: yang paling dulu masuk, paling dulu keluar. */
    public function scopeFifo(Builder $q): Builder
    {
        return $q->orderBy('date')->orderBy('id');
    }

    public function scopeAvailable(Builder $q): Builder
    {
        return $q->where(fn ($x) => $x->where('weight_kg_left', '>', 0)->orWhere('qty_ekor_left', '>', 0));
    }

    public function isEmpty(): bool
    {
        return (float) $this->weight_kg_left <= 0 && (int) $this->qty_ekor_left <= 0;
    }
}

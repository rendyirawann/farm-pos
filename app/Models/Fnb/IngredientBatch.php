<?php

namespace App\Models\Fnb;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Lot/batch stok — jantung FIFO/FEFO. Tiap pembelian = 1 lot dengan harga belinya sendiri,
 * sehingga HPP memakai harga lot yang benar-benar dikuras (bukan harga rata-rata).
 */
class IngredientBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'ingredient_id', 'supplier_id', 'initial_quantity',
        'remaining_quantity', 'buy_price', 'buy_price_total', 'entry_date', 'expiry_date',
    ];

    protected $casts = [
        'initial_quantity'   => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'buy_price'          => 'decimal:2',
        'buy_price_total'    => 'decimal:2',
        'entry_date'         => 'date',
        'expiry_date'        => 'date',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Urutan pengurasan FEFO: paling dekat kadaluarsa dulu (NULL paling akhir),
     * tie-break FIFO berdasarkan tanggal masuk lalu id.
     */
    public function scopeFefo($q)
    {
        return $q->where('remaining_quantity', '>', 0)
            ->orderByRaw('expiry_date ASC NULLS LAST')
            ->orderBy('entry_date')
            ->orderBy('id');
    }
}

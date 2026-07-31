<?php

namespace App\Models\Fnb;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Bahan baku (master). Tidak ada kolom "stok sekarang" — stok dihitung dari
 * SUM(remaining_quantity) seluruh batch, sesuai desain FIFO/FEFO.
 */
class Ingredient extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'unit', 'minimum_stock'];

    protected $casts = ['minimum_stock' => 'decimal:2'];

    public function batches()
    {
        return $this->hasMany(IngredientBatch::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Stok saat ini = jumlah sisa semua lot. */
    public function currentStock(): float
    {
        return (float) $this->batches()->sum('remaining_quantity');
    }

    /** Nilai persediaan = Σ(sisa lot × harga beli lot). */
    public function stockValue(): float
    {
        return (float) $this->batches()->selectRaw('COALESCE(SUM(remaining_quantity * buy_price),0) v')->value('v');
    }

    public function isLow(): bool
    {
        return (float) $this->minimum_stock > 0 && $this->currentStock() <= (float) $this->minimum_stock;
    }
}

<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Pemasok ayam. */
class Supplier extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_suppliers';
    protected $fillable = ['tenant_id', 'name', 'phone', 'address', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function stockIns()
    {
        return $this->hasMany(StockIn::class, 'supplier_id');
    }

    public function realizations()
    {
        return $this->hasMany(StockInRealization::class, 'supplier_id');
    }

    /**
     * PIUTANG SUPPLIER — nilai barang yang ternyata kurang dan belum ditutup
     * pembelian berikutnya. Positif berarti supplier masih berutang kepada kita.
     */
    public function creditOutstanding(): float
    {
        return (float) $this->realizations()
            ->selectRaw('COALESCE(SUM(value - settled_amount), 0) as sisa')
            ->value('sisa');
    }

    public function hasCredit(): bool
    {
        return $this->creditOutstanding() > 0.01;
    }
}

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
}

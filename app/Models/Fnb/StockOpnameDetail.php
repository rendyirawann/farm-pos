<?php

namespace App\Models\Fnb;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'stock_opname_id', 'ingredient_id', 'system_qty', 'physical_qty', 'difference',
    ];

    protected $casts = ['system_qty' => 'decimal:2', 'physical_qty' => 'decimal:2', 'difference' => 'decimal:2'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}

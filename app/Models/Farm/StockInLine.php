<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StockInLine extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_stock_in_lines';
    protected $fillable = ['tenant_id', 'stock_in_id', 'item_id', 'qty_ekor', 'weight_kg',
        'price_basis', 'unit_price', 'subtotal'];
    protected $casts = ['weight_kg' => 'decimal:2', 'unit_price' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function stockIn(){ return $this->belongsTo(StockIn::class, 'stock_in_id'); }
    public function item()   { return $this->belongsTo(Item::class, 'item_id'); }
    public function lot()    { return $this->hasOne(StockLot::class, 'stock_in_line_id'); }
}

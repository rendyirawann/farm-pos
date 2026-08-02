<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StockOutLine extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_stock_out_lines';
    protected $fillable = ['tenant_id', 'stock_out_id', 'item_id', 'qty_ekor', 'weight_kg',
        'price_basis', 'unit_price', 'subtotal', 'cost', 'profit'];
    protected $casts = ['weight_kg' => 'decimal:2', 'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2', 'cost' => 'decimal:2', 'profit' => 'decimal:2'];

    public function stockOut(){ return $this->belongsTo(StockOut::class, 'stock_out_id'); }
    public function item()    { return $this->belongsTo(Item::class, 'item_id'); }
    public function lotUsages(){ return $this->hasMany(StockOutLotUsage::class, 'stock_out_line_id'); }
}

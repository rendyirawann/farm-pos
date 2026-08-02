<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Jejak lot mana yang terpakai pada satu baris penjualan (audit FIFO). */
class StockOutLotUsage extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_stock_out_lot_usages';
    protected $fillable = ['tenant_id', 'stock_out_line_id', 'lot_id', 'qty_ekor', 'weight_kg', 'cost'];
    protected $casts = ['weight_kg' => 'decimal:2', 'cost' => 'decimal:2'];

    public function lot() { return $this->belongsTo(StockLot::class, 'lot_id'); }
    public function line(){ return $this->belongsTo(StockOutLine::class, 'stock_out_line_id'); }
}

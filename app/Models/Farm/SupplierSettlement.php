<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Alokasi: nota pembelian baru menutup piutang supplier dari realisasi terdahulu. */
class SupplierSettlement extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_supplier_settlements';
    protected $fillable = ['tenant_id', 'supplier_id', 'realization_id', 'stock_in_id',
        'date', 'amount', 'user_id', 'notes'];
    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function realization(){ return $this->belongsTo(StockInRealization::class, 'realization_id'); }
    public function stockIn()    { return $this->belongsTo(StockIn::class, 'stock_in_id'); }
    public function supplier()   { return $this->belongsTo(Supplier::class, 'supplier_id'); }
}

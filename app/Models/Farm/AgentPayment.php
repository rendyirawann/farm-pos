<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Pembayaran piutang agen (bisa dicicil). */
class AgentPayment extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_agent_payments';
    protected $fillable = ['tenant_id', 'agent_id', 'stock_out_id', 'date', 'amount', 'method', 'user_id', 'notes'];
    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function agent()   { return $this->belongsTo(Agent::class, 'agent_id'); }
    public function stockOut(){ return $this->belongsTo(StockOut::class, 'stock_out_id'); }
}

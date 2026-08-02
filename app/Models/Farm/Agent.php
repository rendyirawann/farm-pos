<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Agen pembeli. Menyimpan tempo baku & batas piutang. */
class Agent extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_agents';
    protected $fillable = ['tenant_id', 'name', 'phone', 'address', 'credit_limit', 'term_days', 'is_active'];
    protected $casts = ['credit_limit' => 'decimal:2', 'is_active' => 'boolean'];

    public function stockOuts()
    {
        return $this->hasMany(StockOut::class, 'agent_id');
    }

    /** Sisa piutang = total nota belum lunas - yang sudah dibayar. */
    public function outstanding(): float
    {
        return (float) $this->stockOuts()
            ->where('payment_status', 'unpaid')
            ->selectRaw('COALESCE(SUM(total_sale - paid_amount), 0) as sisa')
            ->value('sisa');
    }

    /** Harga jual terakhir untuk item tertentu -> mengisi form otomatis. */
    public function lastPrice(int $itemId): ?array
    {
        $line = StockOutLine::whereHas('stockOut', fn ($q) => $q->where('agent_id', $this->id))
            ->where('item_id', $itemId)
            ->latest('id')
            ->first();

        return $line ? ['unit_price' => (float) $line->unit_price, 'price_basis' => $line->price_basis] : null;
    }
}

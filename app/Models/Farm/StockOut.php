<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Nota penjualan ke agen. Menyimpan HPP FIFO agar laba per nota terlihat. */
class StockOut extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_stock_outs';
    protected $fillable = ['tenant_id', 'invoice_no', 'date', 'agent_id', 'customer_name', 'user_id',
        'total_sale', 'total_cost', 'gross_profit', 'payment_status', 'due_date',
        'paid_amount', 'paid_at', 'notes'];
    protected $casts = [
        'date' => 'date', 'due_date' => 'date', 'paid_at' => 'date',
        'total_sale' => 'decimal:2', 'total_cost' => 'decimal:2',
        'gross_profit' => 'decimal:2', 'paid_amount' => 'decimal:2',
    ];

    public function lines()   { return $this->hasMany(StockOutLine::class, 'stock_out_id'); }
    public function agent()   { return $this->belongsTo(Agent::class, 'agent_id'); }
    public function user()    { return $this->belongsTo(User::class, 'user_id'); }
    public function payments(){ return $this->hasMany(AgentPayment::class, 'stock_out_id'); }

    /**
     * Nama pihak yang membeli — dipakai di daftar piutang, nota, dan laporan.
     * Nota agen memakai nama agen; nota ecer memakai nama pembeli yang diketik
     * saat mencatat (wajib bila belum lunas, supaya piutangnya bisa ditagih).
     */
    public function pembeli(): string
    {
        return $this->agent?->name ?: (trim((string) $this->customer_name) ?: 'Umum');
    }

    public function isPaid(): bool  { return $this->payment_status === 'paid'; }
    public function remaining(): float { return max(0, (float) $this->total_sale - (float) $this->paid_amount); }

    /** Lewat jatuh tempo dan belum lunas. */
    public function isOverdue(): bool
    {
        return ! $this->isPaid() && $this->due_date && $this->due_date->isPast();
    }

    public function marginPercent(): float
    {
        $s = (float) $this->total_sale;
        return $s > 0 ? round((float) $this->gross_profit / $s * 100, 1) : 0;
    }
}

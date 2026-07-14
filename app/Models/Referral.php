<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modul AFFILIATE — pemakaian kode referral oleh tenant (global, tanpa TenantScope).
 */
class Referral extends Model
{
    protected $fillable = [
        'affiliate_id', 'tenant_id', 'tenant_name', 'status',
        'commission_amount', 'commission_status', 'subscribed_at', 'paid_at',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'subscribed_at'     => 'datetime',
        'paid_at'           => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

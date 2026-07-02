<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsAllActivity;

class Subscription extends Model
{
    use HasUuids, LogsAllActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'plan',
        'amount',
        'billing_period',
        'status',
        'midtrans_order_id',
        'snap_token',
        'payment_type',
        'starts_at',
        'ends_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'paid_at'   => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

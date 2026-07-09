<?php

namespace App\Models;

use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pembelian top-up deposit (alur Midtrans), mirip Subscription. Tidak memakai
 * BelongsToTenant agar webhook (tanpa tenant aktif) bisa mencari via midtrans_order_id.
 */
class DepositTopup extends Model
{
    use HasUuids, LogsAllActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'amount',
        'points',
        'status',
        'midtrans_order_id',
        'snap_token',
        'payment_type',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'points'  => 'integer',
        'paid_at' => 'datetime',
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

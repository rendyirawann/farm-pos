<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Buku besar mutasi poin deposit (immutable). Tidak memakai BelongsToTenant
 * agar bisa dibuat dari konteks tanpa-tenant (webhook) — tenant_id diisi eksplisit.
 */
class DepositTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'type',
        'points',
        'balance_after',
        'cash_amount',
        'description',
        'reference',
    ];

    protected $casts = [
        'points'        => 'decimal:2',
        'balance_after' => 'decimal:2',
        'cash_amount'   => 'decimal:2',
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

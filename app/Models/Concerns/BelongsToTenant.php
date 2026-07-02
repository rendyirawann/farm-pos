<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait untuk model yang datanya dimiliki per-tenant.
 *
 * - Menerapkan TenantScope (filter otomatis saat membaca data).
 * - Mengisi tenant_id otomatis saat membuat data baru (jika ada tenant aktif).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantId = app(TenantManager::class)->id();
                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

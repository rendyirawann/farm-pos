<?php

namespace App\Models\Scopes;

use App\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope anti-bocor: setiap query model ber-tenant otomatis difilter
 * WHERE <table>.tenant_id = <tenant aktif>.
 *
 * Jika tidak ada tenant aktif (Superadmin / proses sistem), scope tidak diterapkan.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantManager::class)->id();

        if ($tenantId !== null) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}

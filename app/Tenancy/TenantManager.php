<?php

namespace App\Tenancy;

use App\Models\Tenant;

/**
 * Penyimpan konteks tenant aktif untuk request berjalan.
 * Di-bind sebagai singleton di AppServiceProvider.
 *
 * - Untuk user login non-Superadmin: di-set oleh middleware IdentifyTenant dari user->tenant_id.
 * - Untuk Superadmin: TIDAK di-set (null) -> global scope tidak aktif -> bisa lihat semua data.
 * - Untuk halaman publik (scan menu, kiosk): di-set manual oleh controller dari resource yang discan.
 */
class TenantManager
{
    protected ?int $tenantId = null;
    protected ?Tenant $tenant = null;

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
        // reset cache model jika id berganti
        if ($this->tenant && $this->tenant->id !== $tenantId) {
            $this->tenant = null;
        }
    }

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->tenantId = $tenant?->id;
    }

    public function tenant(): ?Tenant
    {
        if (!$this->tenant && $this->tenantId) {
            $this->tenant = Tenant::find($this->tenantId);
        }
        return $this->tenant;
    }

    public function has(): bool
    {
        return $this->tenantId !== null;
    }

    public function forget(): void
    {
        $this->tenantId = null;
        $this->tenant = null;
    }
}

<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pencatatan otomatis ke activity_log untuk setiap create/update/delete model.
 * - log_name  = nama tabel (mis. 'orders', 'menus') untuk memudahkan filter.
 * - hanya mencatat kolom yang berubah (logOnlyDirty) & skip jika tidak ada perubahan.
 * - causer otomatis = user yang sedang login (di-set oleh spatie/activitylog).
 */
trait LogsAllActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->getTable())
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $event) {
                $map = ['created' => 'ditambahkan', 'updated' => 'diperbarui', 'deleted' => 'dihapus'];
                return class_basename($this) . ' ' . ($map[$event] ?? $event);
            });
    }
}

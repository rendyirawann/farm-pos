<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pencatatan otomatis ke activity_log untuk create/update/delete model.
 *
 * PENTING (robust): penulisan log dilakukan SETELAH transaksi commit dan
 * dibungkus try/catch. Jadi kalau penulisan log gagal (mis. masalah skema
 * activity_log), proses bisnis — terutama PENYELESAIAN ORDER — TETAP sukses;
 * log hanya di-skip & dicatat ke laravel.log. Tidak pernah membatalkan transaksi.
 */
trait LogsAllActivity
{
    public static function bootLogsAllActivity(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                // Ambil snapshot perubahan saat event terjadi.
                $changes = $event === 'updated' ? $model->getChanges() : $model->attributesToArray();
                unset($changes['updated_at'], $changes['created_at']);
                if ($event === 'updated' && empty($changes)) {
                    return; // tidak ada yang berubah -> tidak usah dicatat
                }

                $label = ['created' => 'ditambahkan', 'updated' => 'diperbarui', 'deleted' => 'dihapus'][$event] ?? $event;
                $desc = class_basename($model) . ' ' . $label;
                $logName = $model->getTable();
                $causer = auth()->user();

                $write = function () use ($model, $event, $changes, $desc, $logName, $causer) {
                    try {
                        activity($logName)
                            ->performedOn($model)
                            ->causedBy($causer)
                            ->event($event)
                            ->withProperties(['attributes' => $changes])
                            ->log($desc);
                    } catch (\Throwable $e) {
                        try { Log::warning('Activity log gagal dicatat: ' . $e->getMessage()); } catch (\Throwable $x) {}
                    }
                };

                // Jalankan setelah commit bila sedang dalam transaksi (mis. proses kasir),
                // agar kegagalan log tak pernah me-rollback order. Di luar transaksi -> langsung.
                if (DB::transactionLevel() > 0) {
                    DB::afterCommit($write);
                } else {
                    $write();
                }
            });
        }
    }
}

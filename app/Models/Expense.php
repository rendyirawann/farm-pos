<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Expense extends Model
{
    use BelongsToTenant, LogsAllActivity;

    protected $fillable = ['uuid', 'tenant_id', 'date', 'category', 'notes', 'amount', 'user_id'];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shift yang SEDANG TERBUKA saat pengeluaran ini dicatat (berdasarkan created_at) —
     * menunjukkan "laci/kas shift mana" uang ini keluar. Ter-scope per-tenant otomatis
     * (Shift memakai BelongsToTenant). Null bila dicatat di luar jam shift manapun.
     */
    public function resolveShift(): ?Shift
    {
        if (! $this->created_at) {
            return null;
        }

        return Shift::with('user')
            ->where('start_time', '<=', $this->created_at)
            ->where(function ($q) {
                $q->whereNull('end_time')->orWhere('end_time', '>=', $this->created_at);
            })
            ->orderByDesc('start_time')
            ->first();
    }
}

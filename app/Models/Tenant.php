<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'business_type',
        'owner_id',
        'phone',
        'email',
        'address',
        'logo',
        'plan',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'is_active',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_active'            => 'boolean',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Apakah tenant masih boleh memakai fitur sistem?
     * (trial belum habis, atau langganan aktif belum kedaluwarsa, dan tidak di-suspend)
     */
    public function hasActiveAccess(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->subscription_status === 'trial') {
            return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
        }

        if ($this->subscription_status === 'active') {
            return $this->subscription_ends_at !== null && $this->subscription_ends_at->isFuture();
        }

        return false;
    }

    /**
     * Sisa hari akses (trial/langganan). Untuk ditampilkan di banner billing.
     */
    public function accessDaysLeft(): int
    {
        $until = $this->subscription_status === 'trial'
            ? $this->trial_ends_at
            : $this->subscription_ends_at;

        if (!$until) {
            return 0;
        }

        return max(0, (int) ceil(now()->floatDiffInDays($until, false)));
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial';
    }
}

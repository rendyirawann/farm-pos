<?php

namespace App\Models;

use App\Tenancy\DepositConfig;
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
        'category',
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
        // Plan deposit / poin
        'billing_mode',
        'deposit_points',
        'deposit_expires_at',
        'deposit_last_used_at',
    ];

    protected $casts = [
        'trial_ends_at'        => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_active'            => 'boolean',
        'deposit_points'       => 'decimal:2',
        'deposit_expires_at'   => 'datetime',
        'deposit_last_used_at' => 'datetime',
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

    public function depositTransactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class);
    }

    public function depositTopups(): HasMany
    {
        return $this->hasMany(DepositTopup::class);
    }

    /**
     * Apakah tenant masih boleh memakai fitur sistem?
     * - Mode deposit: selalu boleh (akses aplikasi); tiap transaksi digating poin terpisah.
     * - Mode bulanan: trial belum habis / langganan aktif belum kedaluwarsa.
     * Selalu tunduk pada flag suspend (is_active) Superadmin.
     */
    public function hasActiveAccess(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isDepositMode()) {
            return true;
        }

        return $this->monthlyActive();
    }

    /** Langganan BULANAN sedang aktif (trial/active belum kedaluwarsa). */
    public function monthlyActive(): bool
    {
        if ($this->subscription_status === 'trial') {
            return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
        }

        if ($this->subscription_status === 'active') {
            return $this->subscription_ends_at !== null && $this->subscription_ends_at->isFuture();
        }

        return false;
    }

    /* ===================== Plan deposit / poin ===================== */

    public function isDepositMode(): bool
    {
        return $this->billing_mode === 'deposit';
    }

    public function isMonthlyMode(): bool
    {
        return $this->billing_mode !== 'deposit';
    }

    /** Saldo poin (nilai Rupiah). */
    public function depositBalance(): float
    {
        return (float) $this->deposit_points;
    }

    /** Poin sudah lewat masa aktif (menunggu di-sweep jadi 0). */
    public function depositExpired(): bool
    {
        return $this->deposit_expires_at !== null && $this->deposit_expires_at->isPast();
    }

    /** Poin masih bisa dipakai untuk minimal 1 transaksi. */
    public function hasUsableDepositPoints(): bool
    {
        return ! $this->depositExpired()
            && (float) $this->deposit_points >= DepositConfig::feePerTransaction();
    }

    /** Cukup poin untuk menyelesaikan 1 transaksi? (mode bulanan selalu true) */
    public function canCompleteTransaction(): bool
    {
        if (! $this->isDepositMode()) {
            return true;
        }

        return (float) $this->deposit_points >= DepositConfig::feePerTransaction();
    }

    /** Poin akan hangus dalam <= $days hari (dan masih ada saldo). */
    public function depositExpiringSoon(int $days = 7): bool
    {
        if ($this->deposit_expires_at === null || (float) $this->deposit_points <= 0) {
            return false;
        }

        return ! $this->depositExpired()
            && $this->deposit_expires_at->isBefore(now()->addDays($days));
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

    /** UMKM: pakai "Kas Harian" (1x/hari) alih-alih shift per-sesi. */
    public function isUmkm(): bool
    {
        return $this->category === 'umkm';
    }
}

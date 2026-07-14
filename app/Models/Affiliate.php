<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modul AFFILIATE — afiliator (global, bukan per-tenant → tanpa TenantScope).
 */
class Affiliate extends Model
{
    protected $fillable = [
        'code', 'name', 'email', 'phone', 'type',
        'tenant_id', 'user_id', 'status', 'payout_info', 'notes',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** URL referral yang dibagikan afiliator. */
    public function referralUrl(): string
    {
        return 'https://affiliate.mooda.id/r/' . $this->code;
    }

    /** Buat kode referral unik (dipakai saat membuat afiliator). */
    public static function generateCode(?string $seed = null): string
    {
        $base = Str::upper(Str::slug($seed ?: 'MOODA'));
        $base = preg_replace('/[^A-Z0-9]/', '', $base);
        $base = substr($base ?: 'MOODA', 0, 6);
        do {
            $code = $base . Str::upper(Str::random(4));
        } while (self::where('code', $code)->exists());
        return $code;
    }
}

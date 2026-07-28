<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Pengajuan pencairan (withdraw) komisi oleh affiliate.
 * status: pending (menunggu diproses Superadmin) | done (dicairkan) | rejected (ditolak).
 */
class Withdrawal extends Model
{
    protected $fillable = [
        'code', 'affiliate_id', 'amount', 'status', 'note', 'requested_at', 'done_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'requested_at' => 'datetime',
        'done_at'      => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    /** Komisi (referral) yang tercakup dalam pencairan ini. */
    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }

    /** Kode unik pencairan, mis. WD-3F9K2A. */
    public static function generateCode(): string
    {
        do {
            $code = 'WD-' . strtoupper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}

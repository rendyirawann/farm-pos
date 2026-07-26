<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Cog\Contracts\Ban\Bannable as BannableContract;
use Cog\Laravel\Ban\Traits\Bannable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 1. Ini penawar errornya
use App\Models\Concerns\BelongsToTenant;
use App\Notifications\VerifyAccountNotification;

class User extends Authenticatable implements BannableContract, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // 2. Masukkan HasUuids ke dalam use
    use HasFactory, Notifiable, HasRoles, Bannable, HasUuids, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'username',
        'email',
        'no_wa',
        'avatar',
        'last_ip',
        'last_login',
        'banned_at',
        'nik',
        'phone',
        'is_active',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Superadmin = pemilik platform (lintas tenant). Tidak terikat pada tenant manapun.
     */
    public function isSuperadmin(): bool
    {
        return $this->hasRole('Superadmin');
    }

    /** Kirim email verifikasi (link aktivasi) versi branded Mooda. */
    public function sendEmailVerificationNotification(): void
    {
        // Catat waktu kirim -> dipakai cooldown "kirim ulang" (2 menit) & countdown UI.
        \Illuminate\Support\Facades\Cache::put('verify_sent_' . $this->getKey(), now()->timestamp, now()->addMinutes(30));
        $this->notify(new VerifyAccountNotification());
    }

    /** Sisa detik cooldown kirim-ulang link aktivasi (0 bila boleh kirim). */
    public function verificationResendCooldown(int $seconds = 120): int
    {
        $last = \Illuminate\Support\Facades\Cache::get('verify_sent_' . $this->getKey());

        return $last ? max(0, $seconds - (now()->timestamp - (int) $last)) : 0;
    }
}

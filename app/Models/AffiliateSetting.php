<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Setelan program affiliate tingkat-platform (satu baris / singleton).
 * Diatur oleh Superadmin. Baca via AffiliateSetting::current().
 */
class AffiliateSetting extends Model
{
    protected $fillable = [
        'commission_type',   // 'flat' (Rp) | 'percent'
        'commission_value',  // rupiah bila flat; persen bila percent
        'cashback_percent',  // persen cashback utk user yg daftar via referral (0 = nonaktif)
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'cashback_percent' => 'decimal:2',
    ];

    /** Ambil (atau buat) baris setelan tunggal. */
    public static function current(): self
    {
        return static::query()->first() ?? tap(static::create([]))->refresh();
    }
}

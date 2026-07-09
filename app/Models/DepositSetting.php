<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Setelan plan deposit tingkat-platform (satu baris). Baca via App\Tenancy\DepositConfig.
 */
class DepositSetting extends Model
{
    protected $fillable = [
        'max_points',
        'fee_per_transaction',
        'expiry_days',
        'min_deposit',
    ];

    protected $casts = [
        'max_points'          => 'integer',
        'fee_per_transaction' => 'integer',
        'expiry_days'         => 'integer',
        'min_deposit'         => 'integer',
    ];
}

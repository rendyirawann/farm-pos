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
        'initial_topup',
        'manual_wa',
        'manual_bank',
    ];

    protected $casts = [
        'max_points'          => 'integer', // null diperbolehkan (unlimited); cast tidak mengubah null
        'fee_per_transaction' => 'integer',
        'expiry_days'         => 'integer',
        'min_deposit'         => 'integer',
        'initial_topup'       => 'integer',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pilihan nominal top-up deposit (amount Rupiah => points diterima). Diedit Superadmin.
 */
class DepositTier extends Model
{
    protected $fillable = [
        'amount',
        'points',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'amount'     => 'integer',
        'points'     => 'integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];
}

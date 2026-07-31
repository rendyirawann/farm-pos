<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Promo/diskon per (paket, durasi bulan). Diatur Superadmin. */
class PlanPromo extends Model
{
    protected $fillable = [
        'plan_key', 'vertical', 'months', 'discount_percent', 'promo_label', 'is_active', 'price_per_month',
    ];

    protected $casts = [
        'months'           => 'integer',
        'discount_percent' => 'decimal:2',
        'is_active'        => 'boolean',
        'price_per_month'  => 'integer',
    ];
}

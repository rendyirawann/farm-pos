<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Setelan payment gateway tingkat-platform (satu baris). Baca via App\Support\Billing.
 */
class PaymentSetting extends Model
{
    protected $fillable = [
        'active_driver',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Harga dasar per paket (harga 1 bulan). Diatur Superadmin. */
class PlanSetting extends Model
{
    protected $fillable = ['plan_key', 'base_price'];

    protected $casts = ['base_price' => 'integer'];
}

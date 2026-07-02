<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Promo extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'discount_type', 'discount_value', 'is_active'];
}

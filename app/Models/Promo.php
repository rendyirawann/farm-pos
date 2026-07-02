<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;

class Promo extends Model
{
    use BelongsToTenant, LogsAllActivity;

    protected $fillable = ['tenant_id', 'name', 'discount_type', 'discount_value', 'is_active'];
}

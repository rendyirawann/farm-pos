<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Setting extends Model
{
    use BelongsToTenant;

    protected $table = 'settings';
    protected $fillable = ['tenant_id', 'store_name', 'address', 'phone', 'tax_rate'];
}

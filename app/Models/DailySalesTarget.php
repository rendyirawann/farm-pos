<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class DailySalesTarget extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'date', 'amount'];
}

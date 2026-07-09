<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DailyBudget extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'date', 'amount'];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];
}

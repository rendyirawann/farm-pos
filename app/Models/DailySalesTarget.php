<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;

class DailySalesTarget extends Model
{
    use BelongsToTenant, LogsAllActivity;

    protected $fillable = ['tenant_id', 'date', 'amount'];
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;
use Illuminate\Database\Eloquent\Model;

class DiningTable extends Model
{
    use BelongsToTenant, LogsAllActivity;

    protected $table = 'dining_tables';

    protected $fillable = ['tenant_id', 'name', 'area', 'capacity', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'capacity'   => 'integer',
        'sort_order' => 'integer',
    ];
}

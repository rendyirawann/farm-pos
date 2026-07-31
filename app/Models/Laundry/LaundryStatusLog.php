<?php

namespace App\Models\Laundry;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaundryStatusLog extends Model
{
    protected $table = 'laundry_status_logs';

    protected $fillable = [
        'order_id', 'status', 'changed_by', 'notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaundryOrder::class, 'order_id');
    }
}

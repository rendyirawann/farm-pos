<?php

namespace App\Models\Laundry;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaundryOrderItem extends Model
{
    protected $table = 'laundry_order_items';

    protected $fillable = [
        'order_id', 'service_id', 'service_name', 'unit', 'qty', 'price', 'subtotal',
        'notes', 'item_condition', 'status',
    ];

    protected $casts = [
        'qty'      => 'decimal:2',
        'price'    => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaundryOrder::class, 'order_id');
    }
}

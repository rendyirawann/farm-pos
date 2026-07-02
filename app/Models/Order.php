<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;

class Order extends Model
{
    use HasUuids, BelongsToTenant, LogsAllActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'invoice_no',
        'queue_number',
        'customer_name',
        'subtotal',
        'tax',
        'grand_total',
        'payment_method',
        'payment_status',
        'cash_received',
        'change_amount',
        'order_status',
        'promo_id',
        'discount_amount',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }
}

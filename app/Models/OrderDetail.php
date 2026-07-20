<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class OrderDetail extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'menu_id',
        'qty',
        'price',
        'subtotal',
        'addons',
        'notes',
        'status',
    ];

    protected $casts = [
        'addons' => 'array', // [{"id":1,"name":"Extra Keju","price":5000,"qty":2}, ...]
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}

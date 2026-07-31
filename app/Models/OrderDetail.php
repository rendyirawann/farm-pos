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
        // Modul HPP: snapshot biaya bahan nyata + penjaga idempoten potong stok.
        'hpp',
        'is_stock_deducted',
    ];

    protected $casts = [
        'addons' => 'array', // [{"id":1,"name":"Extra Keju","price":5000,"qty":2}, ...]
        'hpp'               => 'decimal:2',
        'is_stock_deducted' => 'boolean',
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

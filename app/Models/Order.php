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
        'client_txn_id',
        'tenant_id',
        'invoice_no',
        'queue_number',
        'customer_name',
        'table_no',
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
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'voided_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** Pesanan ditandai salah (tidak dihitung ke omzet/kas). */
    public function isVoided(): bool
    {
        return $this->voided_at !== null;
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

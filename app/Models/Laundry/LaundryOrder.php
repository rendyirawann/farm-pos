<?php

namespace App\Models\Laundry;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaundryOrder extends Model
{
    use BelongsToTenant, HasUuids;

    protected $table = 'laundry_orders';

    /** Isi kolom `uuid` otomatis; PK tetap `id` (bigint auto-increment). */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected $fillable = [
        'tenant_id', 'uuid', 'invoice_no', 'customer_id', 'customer_name', 'customer_phone',
        'customer_email', 'staff_id', 'order_type', 'delivery_address', 'delivery_fee',
        'subtotal', 'discount_amount', 'tax', 'grand_total', 'payment_method', 'payment_status',
        'dp_amount', 'cash_received', 'cash_change', 'order_status', 'special_instructions',
        'estimated_completed_at', 'actual_completed_at', 'picked_up_at',
    ];

    protected $casts = [
        'delivery_fee'           => 'decimal:2',
        'subtotal'               => 'decimal:2',
        'discount_amount'        => 'decimal:2',
        'tax'                    => 'decimal:2',
        'grand_total'            => 'decimal:2',
        'dp_amount'              => 'decimal:2',
        'cash_received'          => 'decimal:2',
        'cash_change'            => 'decimal:2',
        'estimated_completed_at' => 'datetime',
        'actual_completed_at'    => 'datetime',
        'picked_up_at'           => 'datetime',
    ];

    /** Alur produksi cucian (satu langkah maju). 'diambil' terminal, di luar pipeline. */
    public const PIPELINE = ['diterima', 'dicuci', 'dikeringkan', 'disetrika', 'packing', 'selesai'];

    public const STAGE_LABELS = [
        'diterima'    => 'Diterima',
        'dicuci'      => 'Dicuci',
        'dikeringkan' => 'Dikeringkan',
        'disetrika'   => 'Disetrika',
        'packing'     => 'Packing',
        'selesai'     => 'Selesai',
        'diambil'     => 'Diambil',
    ];

    /** Status "masih di workshop". */
    public const ACTIVE_STATUSES = ['diterima', 'dicuci', 'dikeringkan', 'disetrika', 'packing'];

    public function items(): HasMany
    {
        return $this->hasMany(LaundryOrderItem::class, 'order_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(LaundryStatusLog::class, 'order_id')->orderBy('created_at');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(LaundryCustomer::class, 'customer_id');
    }

    /** Status berikutnya di pipeline (null bila sudah 'selesai'/terminal). */
    public function nextStatus(): ?string
    {
        $i = array_search($this->order_status, self::PIPELINE, true);
        if ($i === false) {
            return null;
        }
        return self::PIPELINE[$i + 1] ?? null;
    }

    public function statusLabel(): string
    {
        return self::STAGE_LABELS[$this->order_status] ?? ucfirst((string) $this->order_status);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}

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
        'shift_id',
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

    /** Shift kasir yang membuat order ini (laporan penjualan per-kasir + rekonsiliasi laci). */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Saat order dibuat, tautkan otomatis ke shift kasir yang sedang TERBUKA (per-tenant).
     * Menggantikan atribusi via created_at yang dobel-hitung antar-shift tumpang-tindih.
     */
    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (empty($order->shift_id)) {
                // Utamakan shift milik user sendiri. Bila tidak punya (mis. owner yang ikut
                // mengoperasikan kasir saat shift kasir berjalan), ikut shift toko yang terbuka —
                // lebih baik daripada shift_id NULL yang membuat pesanan tak teratribusi ke kas.
                $order->shift_id = Shift::where('user_id', \Illuminate\Support\Facades\Auth::id())
                        ->where('status', 'open')
                        ->value('id')
                    ?: Shift::where('status', 'open')->latest('start_time')->value('id');
            }
        });
    }
}

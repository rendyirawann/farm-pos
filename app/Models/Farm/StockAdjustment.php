<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Penyesuaian stok. Tanpa jalur ini stok sistem tidak akan pernah cocok dengan
 * fisik (ayam mati/susut), dan begitu tidak cocok, FIFO ikut melenceng.
 */
class StockAdjustment extends Model
{
    use BelongsToTenant;

    public const REASONS = [
        'mati'            => 'Mati',
        'susut'           => 'Susut Bobot',
        'rusak'           => 'Rusak / Afkir',
        'hilang'          => 'Hilang',
        'koreksi_opname'  => 'Koreksi Opname (kurang)',
        'koreksi_tambah'  => 'Koreksi Opname (lebih)',
    ];

    protected $table = 'farm_stock_adjustments';
    protected $fillable = ['tenant_id', 'ref_no', 'date', 'item_id', 'lot_id', 'reason',
        'qty_ekor', 'weight_kg', 'cost_impact', 'user_id', 'approved_by', 'approved_at', 'notes',
        'photo_path'];
    protected $casts = ['date' => 'date', 'approved_at' => 'datetime',
        'weight_kg' => 'decimal:2', 'cost_impact' => 'decimal:2'];

    public function item()      { return $this->belongsTo(Item::class, 'item_id'); }
    public function lot()       { return $this->belongsTo(StockLot::class, 'lot_id'); }
    public function user()      { return $this->belongsTo(User::class, 'user_id'); }
    public function approver()  { return $this->belongsTo(User::class, 'approved_by'); }

    public function reasonLabel(): string { return self::REASONS[$this->reason] ?? $this->reason; }
    public function isApproved(): bool    { return $this->approved_at !== null; }
    public function isAddition(): bool    { return $this->reason === 'koreksi_tambah'; }

    /**
     * Alasan yang WAJIB berfoto. Barang hilang dikecualikan — tidak ada wujud
     * yang bisa difoto, dan memaksa foto hanya akan membuat petugas mengarang
     * gambar apa saja supaya formulirnya lolos.
     */
    public const TANPA_FOTO = ['hilang'];

    public static function butuhFoto(?string $reason): bool
    {
        return ! in_array((string) $reason, self::TANPA_FOTO, true);
    }

    public function hasPhoto(): bool
    {
        return ! empty($this->photo_path);
    }

    public function isImagePhoto(): bool
    {
        return in_array(strtolower(pathinfo((string) $this->photo_path, PATHINFO_EXTENSION)),
            ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}

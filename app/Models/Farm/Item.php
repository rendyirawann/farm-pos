<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Objek yang diperdagangkan: ayam potong, ayam petelur, telur.
 * Tidak ada kolom "stok sekarang" — stok dihitung dari sisa lot (FIFO).
 */
class Item extends Model
{
    use BelongsToTenant;

    public const CATEGORIES = [
        'ayam_potong'  => 'Ayam Potong',
        'ayam_petelur' => 'Ayam Petelur',
        'telur'        => 'Telur',
    ];

    protected $table = 'farm_items';
    protected $fillable = ['tenant_id', 'category', 'name', 'primary_unit', 'is_produced', 'min_stock_kg', 'is_active'];
    protected $casts = ['is_produced' => 'boolean', 'is_active' => 'boolean', 'min_stock_kg' => 'decimal:2'];

    public function lots()
    {
        return $this->hasMany(StockLot::class, 'item_id');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** Stok tersedia: ekor & kg sekaligus — dua-duanya penting agar susut terlihat. */
    public function stock(): array
    {
        $row = $this->lots()
            ->selectRaw('COALESCE(SUM(qty_ekor_left),0) as ekor, COALESCE(SUM(weight_kg_left),0) as kg')
            ->first();

        return ['ekor' => (int) ($row->ekor ?? 0), 'kg' => (float) ($row->kg ?? 0)];
    }

    /** Nilai persediaan berdasarkan harga pokok tiap lot yang tersisa. */
    public function stockValue(): float
    {
        return (float) $this->lots()
            ->selectRaw('COALESCE(SUM(weight_kg_left * cost_per_kg),0) as nilai')
            ->value('nilai');
    }
}

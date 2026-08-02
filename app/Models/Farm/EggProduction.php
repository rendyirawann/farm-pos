<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Produksi telur harian. Telur TIDAK dibeli dari supplier, jadi tidak lewat Stock In.
 * Harga pokoknya dihitung otomatis dari biaya operasional periode berjalan
 * (lihat App\Services\Farm\EggCostService).
 */
class EggProduction extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_egg_productions';
    protected $fillable = ['tenant_id', 'date', 'coop', 'item_id', 'qty_butir', 'qty_broken', 'user_id', 'notes'];
    protected $casts = ['date' => 'date'];

    public function item(){ return $this->belongsTo(Item::class, 'item_id'); }

    /** Butir yang benar-benar layak jual. */
    public function netButir(): int
    {
        return max(0, (int) $this->qty_butir - (int) $this->qty_broken);
    }
}

<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Buka / Tutup Gudang — pengganti "shift" kasir. TIDAK ada modal & kembalian;
 * yang dipertanggungjawabkan adalah stok fisik, bukan uang.
 */
class WarehouseSession extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_warehouse_sessions';
    protected $fillable = ['tenant_id', 'opened_by', 'closed_by', 'opened_at', 'closed_at',
        'opening_stock', 'closing_stock', 'physical_stock', 'difference', 'status', 'notes'];
    protected $casts = [
        'opened_at' => 'datetime', 'closed_at' => 'datetime',
        'opening_stock' => 'array', 'closing_stock' => 'array',
        'physical_stock' => 'array', 'difference' => 'array',
    ];

    public function opener(){ return $this->belongsTo(User::class, 'opened_by'); }
    public function closer(){ return $this->belongsTo(User::class, 'closed_by'); }

    public function isOpen(): bool { return $this->status === 'open'; }
}

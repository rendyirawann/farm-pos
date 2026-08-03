<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Nota pembelian dari supplier. Tiap barisnya menjadi satu lot FIFO. */
class StockIn extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_stock_ins';
    protected $fillable = ['tenant_id', 'invoice_no', 'date', 'supplier_id', 'user_id', 'total', 'notes', 'photos'];
    protected $casts = ['date' => 'date', 'total' => 'decimal:2', 'photos' => 'array'];

    /** Daftar foto bon (bisa lebih dari satu lembar). */
    public function photoList(): array
    {
        return array_values(array_filter((array) ($this->photos ?? [])));
    }

    public function hasPhotos(): bool
    {
        return count($this->photoList()) > 0;
    }

    public function lines()   { return $this->hasMany(StockInLine::class, 'stock_in_id'); }
    public function supplier(){ return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function user()    { return $this->belongsTo(User::class, 'user_id'); }
}

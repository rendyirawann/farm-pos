<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Pemasok ayam. */
class Supplier extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_suppliers';
    protected $fillable = ['tenant_id', 'name', 'phone', 'address', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function stockIns()
    {
        return $this->hasMany(StockIn::class, 'supplier_id');
    }

    public function realizations()
    {
        return $this->hasMany(StockInRealization::class, 'supplier_id');
    }

    public function deposits()
    {
        return $this->hasMany(SupplierDeposit::class, 'supplier_id');
    }

    /**
     * SALDO DEPOSIT — uang milik kita yang masih dipegang supplier.
     * Dihitung dari buku besar, bukan kolom tersimpan, agar tidak bisa
     * menyimpang tanpa baris penjelas.
     */
    public function depositBalance(): float
    {
        return round((float) $this->deposits()->sum('amount'), 2);
    }

    /** Saldo negatif = kita berutang ke supplier (barang masuk melebihi setoran). */
    public function isOverdrawn(): bool
    {
        return $this->depositBalance() < -0.01;
    }

}

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
    protected $fillable = ['tenant_id', 'invoice_no', 'date', 'supplier_id', 'user_id', 'total', 'notes',
        'photos', 'payment_status', 'paid_amount', 'paid_at'];
    protected $casts = ['date' => 'date', 'paid_at' => 'date', 'total' => 'decimal:2',
        'photos' => 'array', 'paid_amount' => 'decimal:2'];

    /** Daftar foto bon (bisa lebih dari satu lembar). */
    public function photoList(): array
    {
        return array_values(array_filter((array) ($this->photos ?? [])));
    }

    public function hasPhotos(): bool
    {
        return count($this->photoList()) > 0;
    }

    /** Berkas bon bisa berupa gambar (difoto di HP) atau PDF/scan (diunggah dari laptop). */
    public static function isImagePath(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function lines()   { return $this->hasMany(StockInLine::class, 'stock_in_id'); }
    public function supplier(){ return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function user()        { return $this->belongsTo(User::class, 'user_id'); }
    /** Satu nota hanya punya satu realisasi (hasil timbang ulang). */
    public function realization(){ return $this->hasOne(StockInRealization::class, 'stock_in_id'); }

    /** Baris buku besar deposit yang lahir dari nota ini (potongan + pembalikannya). */
    public function depositEntries()
    {
        return $this->hasMany(SupplierDeposit::class, 'reference_id')->where('reference_type', 'stock_in');
    }

    /* ---------- Pembayaran KITA ke supplier ---------- */

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Nilai nota SETELAH koreksi realisasi — inilah nilai yang benar-benar
     * memotong saldo deposit supplier, bukan angka nota mentah.
     *
     * Nilai realisasi bertanda: positif = barang kurang (nilai nota jadi lebih
     * kecil), negatif = barang lebih (nilai nota jadi lebih besar).
     */
    public function netTotal(): float
    {
        $koreksi = (float) ($this->realization?->value ?? 0);

        return max(0, round((float) $this->total - $koreksi, 2));
    }

    /** Sisa yang belum kita bayar tunai untuk nota ini. */
    public function remainingToPay(): float
    {
        return max(0, round($this->netTotal() - (float) $this->paid_amount, 2));
    }
}

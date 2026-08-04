<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris buku besar deposit supplier.
 *
 * Saldo supplier TIDAK disimpan sebagai satu angka melainkan dihitung
 * SUM(amount) dari tabel ini. Konsekuensinya setiap perubahan saldo wajib
 * meninggalkan baris — tidak ada cara saldo bergeser tanpa jejak.
 *
 * amount BERTANDA: positif menambah saldo, negatif mengurangi.
 */
class SupplierDeposit extends Model
{
    use BelongsToTenant;

    public const TYPES = [
        'topup'       => 'Setor Deposit',
        'purchase'    => 'Pemotongan Barang Masuk',
        'realization' => 'Koreksi Realisasi',
        'manual'      => 'Koreksi Manual',
    ];

    protected $table = 'farm_supplier_deposits';
    protected $fillable = ['tenant_id', 'supplier_id', 'date', 'type', 'amount',
        'reference_type', 'reference_id', 'reverses_id', 'proof_path', 'user_id', 'notes'];
    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function supplier(){ return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function user()    { return $this->belongsTo(User::class, 'user_id'); }

    public function typeLabel(): string { return self::TYPES[$this->type] ?? $this->type; }
    public function isCredit(): bool    { return (float) $this->amount >= 0; }

    /** Baris ini adalah pembalikan baris lain (buku besar append-only). */
    public function isReversal(): bool  { return ! empty($this->reverses_id); }

    /** Dokumen sumber, bila baris ini berasal dari nota/realisasi. */
    public function stockIn(): ?StockIn
    {
        return $this->reference_type === 'stock_in' ? StockIn::find($this->reference_id) : null;
    }

    public function hasProof(): bool
    {
        return ! empty($this->proof_path);
    }

    public static function isImageProof(?string $path): bool
    {
        return in_array(strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}

<?php

namespace App\Models\Farm;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Angka nyata per barang pada satu realisasi.
 *
 * Dipisah dari header karena satu nota bisa campuran: broiler kurang 4 kg
 * sementara ayam kampung justru lebih 3 kg. Kalau arah selisih disimpan di
 * tingkat nota, salah satu barang pasti tercatat dengan tanda terbalik.
 */
class StockInRealizationLine extends Model
{
    use BelongsToTenant;

    protected $table = 'farm_stock_in_realization_lines';
    protected $fillable = ['tenant_id', 'realization_id', 'stock_in_line_id', 'lot_id',
        'nota_qty_ekor', 'nota_weight_kg', 'received_qty_ekor', 'received_weight_kg',
        'delta_qty_ekor', 'delta_weight_kg', 'price_basis', 'unit_price', 'value'];
    protected $casts = ['nota_weight_kg' => 'decimal:2', 'received_weight_kg' => 'decimal:2',
        'delta_weight_kg' => 'decimal:2', 'unit_price' => 'decimal:2', 'value' => 'decimal:2'];

    public function realization(){ return $this->belongsTo(StockInRealization::class, 'realization_id'); }
    public function line()       { return $this->belongsTo(StockInLine::class, 'stock_in_line_id'); }
    public function lot()        { return $this->belongsTo(StockLot::class, 'lot_id'); }

    public function isSesuai(): bool
    {
        return abs((float) $this->delta_weight_kg) < 0.005 && (int) $this->delta_qty_ekor === 0;
    }

    /** Keterangan selisih apa adanya: "kurang 4 kg", "lebih 2 ekor / 3,5 kg". */
    public function deltaLabel(): string
    {
        if ($this->isSesuai()) {
            return 'Sesuai nota';
        }

        $bagian = [];
        if ((int) $this->delta_qty_ekor !== 0) {
            $bagian[] = abs((int) $this->delta_qty_ekor) . ' ekor';
        }
        if (abs((float) $this->delta_weight_kg) >= 0.005) {
            $bagian[] = number_format(abs((float) $this->delta_weight_kg), 2, ',', '.') . ' kg';
        }

        // Arah ditentukan oleh besaran yang jadi dasar harga, bukan oleh salah satu
        // dimensi yang kebetulan lebih besar — supaya sejalan dengan nilai uangnya.
        $dasar = $this->price_basis === 'ekor' ? (int) $this->delta_qty_ekor : (float) $this->delta_weight_kg;
        $arah  = $dasar < 0 ? 'Kurang' : 'Lebih';

        return $arah . ' ' . implode(' / ', $bagian);
    }
}

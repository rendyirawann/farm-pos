<?php

namespace App\Models\Laundry;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LaundryService extends Model
{
    use BelongsToTenant;

    protected $table = 'laundry_services';

    protected $fillable = [
        'tenant_id', 'category', 'name', 'unit',
        'price_per_unit', 'estimated_duration_hours', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price_per_unit'           => 'decimal:2',
        'estimated_duration_hours' => 'integer',
        'is_active'                => 'boolean',
        'sort_order'               => 'integer',
    ];

    /** Satuan layanan. kg = kiloan; pcs/meter/pasang = satuan. Express = layanan tersendiri. */
    public const UNITS = [
        'kg'     => 'Kilogram (kiloan)',
        'pcs'    => 'Satuan (pcs)',
        'meter'  => 'Meter',
        'pasang' => 'Pasang',
    ];
}

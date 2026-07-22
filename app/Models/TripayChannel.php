<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Channel pembayaran Tripay — global, dikelola Superadmin.
 * Customer memilih dari channel yang aktif saat checkout (langganan / top-up deposit).
 */
class TripayChannel extends Model
{
    protected $fillable = [
        'name', 'code', 'group', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Channel aktif, urut sort_order lalu nama. */
    public static function activeOrdered()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}

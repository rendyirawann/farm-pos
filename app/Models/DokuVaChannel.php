<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Channel Virtual Account DOKU (SNAP) — global, dikelola Superadmin.
 * @see \App\Services\Doku\DokuSnap
 */
class DokuVaChannel extends Model
{
    protected $fillable = [
        'name', 'channel', 'partner_service_id', 'prefix_customer',
        'environment', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Channel aktif untuk environment saat ini (sandbox/production), urut sort_order. */
    public static function activeForCurrentEnv()
    {
        $env = config('services.doku.is_production') ? 'production' : 'sandbox';
        return static::where('environment', $env)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FAQ / Q&A landing (global, Superadmin). Tampil di section FAQ mooda.id.
 */
class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** FAQ aktif, terurut — untuk ditampilkan di landing. */
    public static function activeOrdered()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}

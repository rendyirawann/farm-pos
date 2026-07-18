<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Logo partner/tenant berlangganan — marquee landing page. Global (Superadmin).
 */
class PartnerLogo extends Model
{
    protected $fillable = ['name', 'image', 'url', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getImageUrlAttribute(): string
    {
        return asset('storage/partners/' . $this->image);
    }

    /** Logo aktif yang ditampilkan di landing (dibatasi limit dari SiteOption). */
    public static function forLanding()
    {
        $limit = (int) SiteOption::get('landing_partner_limit', 12);
        $q = static::where('is_active', true)->orderBy('sort_order')->orderBy('id');
        return $limit > 0 ? $q->limit($limit)->get() : $q->get();
    }
}

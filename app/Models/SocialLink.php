<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Tautan sosial media footer (global, Superadmin). Ikon & label dideteksi
 * OTOMATIS dari URL yang ditautkan.
 */
class SocialLink extends Model
{
    protected $fillable = ['url', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function activeOrdered()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    /** Platform terdeteksi dari URL (instagram/facebook/tiktok/youtube/x/whatsapp/telegram/linkedin/link). */
    public function platform(): string
    {
        $u = Str::lower((string) $this->url);

        return match (true) {
            Str::contains($u, ['instagram.com', 'instagr.am'])         => 'instagram',
            Str::contains($u, 'tiktok.com')                             => 'tiktok',
            Str::contains($u, ['facebook.com', 'fb.com', 'fb.me', 'fb.watch']) => 'facebook',
            Str::contains($u, ['youtube.com', 'youtu.be'])              => 'youtube',
            Str::contains($u, ['twitter.com', 'x.com'])                 => 'x',
            Str::contains($u, ['wa.me', 'whatsapp.com', 'api.whatsapp']) => 'whatsapp',
            Str::contains($u, ['t.me', 'telegram.me', 'telegram.org'])  => 'telegram',
            Str::contains($u, 'linkedin.com')                           => 'linkedin',
            default                                                     => 'link',
        };
    }

    /** Nama platform untuk label/aria. */
    public function label(): string
    {
        return match ($this->platform()) {
            'instagram' => 'Instagram',
            'facebook'  => 'Facebook',
            'tiktok'    => 'TikTok',
            'youtube'   => 'YouTube',
            'x'         => 'X (Twitter)',
            'whatsapp'  => 'WhatsApp',
            'telegram'  => 'Telegram',
            'linkedin'  => 'LinkedIn',
            default     => 'Situs / Tautan',
        };
    }

    /** Render <svg> ikon brand sesuai platform. */
    public function iconSvg(string $class = ''): string
    {
        $path = config('social_icons.' . $this->platform()) ?? config('social_icons.link');
        $cls  = $class !== '' ? ' class="' . e($class) . '"' : '';

        return '<svg' . $cls . ' viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="' . $path . '"/></svg>';
    }
}

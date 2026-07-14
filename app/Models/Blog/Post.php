<?php

namespace App\Models\Blog;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modul BLOG — Artikel (global/marketing, BUKAN per-tenant → tanpa TenantScope).
 * body berisi HTML dari CKEditor yang SUDAH disanitasi di controller (mews/purifier).
 */
class Post extends Model
{
    protected $table = 'blog_posts';

    protected $fillable = [
        'user_id', 'blog_category_id', 'title', 'slug', 'excerpt', 'body',
        'cover', 'status', 'published_at', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'blog_category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Hanya artikel yang sudah tayang (untuk halaman publik). */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /** URL cover (atau null). */
    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover ? asset('storage/blog/' . $this->cover) : null;
    }
}

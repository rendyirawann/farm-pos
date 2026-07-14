<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modul BLOG — Kategori artikel (global, bukan per-tenant).
 */
class Category extends Model
{
    protected $table = 'blog_categories';

    protected $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->hasMany(Post::class, 'blog_category_id');
    }
}

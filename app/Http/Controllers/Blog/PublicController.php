<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Blog\Category;
use App\Models\Blog\Post;
use Illuminate\Http\Request;

/**
 * Modul BLOG (publik) — halaman blog.mooda.id. Hanya menampilkan artikel PUBLISHED.
 */
class PublicController extends Controller
{
    /** Daftar artikel terbaru. */
    public function index(Request $request)
    {
        $posts = Post::published()->with('category')
            ->latest('published_at')->paginate(9);
        $categories = Category::whereHas('posts', fn ($q) => $q->published())->orderBy('name')->get();

        return view('blog.index', [
            'posts'      => $posts,
            'categories' => $categories,
            'activeCat'  => null,
        ]);
    }

    /** Artikel per kategori (memakai template list yang sama). */
    public function category($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $posts = Post::published()->where('blog_category_id', $category->id)->with('category')
            ->latest('published_at')->paginate(9);
        $categories = Category::whereHas('posts', fn ($q) => $q->published())->orderBy('name')->get();

        return view('blog.index', [
            'posts'      => $posts,
            'categories' => $categories,
            'activeCat'  => $category,
        ]);
    }

    /** Detail artikel by slug (hanya yang published). */
    public function show($slug)
    {
        $post = Post::published()->with(['category', 'author'])->where('slug', $slug)->firstOrFail();

        $related = Post::published()->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')->limit(3)->get();

        return view('blog.show', compact('post', 'related'));
    }

    /** sitemap.xml khusus blog.mooda.id (meniru pola sitemap utama). */
    public function sitemap()
    {
        $posts = Post::published()->latest('published_at')->get(['slug', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . '  <url><loc>' . e(route('blog.home')) . '</loc><changefreq>daily</changefreq><priority>0.8</priority></url>' . "\n";

        foreach ($posts as $p) {
            $xml .= '  <url>'
                . '<loc>' . e(route('blog.show', $p->slug)) . '</loc>'
                . '<lastmod>' . optional($p->updated_at)->toAtomString() . '</lastmod>'
                . '<changefreq>weekly</changefreq><priority>0.6</priority>'
                . '</url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

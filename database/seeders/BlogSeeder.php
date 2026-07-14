<?php

namespace Database\Seeders;

use App\Models\Blog\Category;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

/**
 * Seeder BLOG: mengisi kategori + artikel demo dari database/seeders/data/blog_articles.json.
 * Cover di-generate sebagai SVG bertema (indigo/landing) ke storage/app/public/blog/.
 * Idempotent: updateOrCreate by slug. Jalankan: php artisan db:seed --class=BlogSeeder
 */
class BlogSeeder extends Seeder
{
    /** Palet gradient cover (keluarga indigo/blue/emerald — selaras landing mooda.id). */
    private array $palette = [
        ['#4f46e5', '#2563eb'],
        ['#4338ca', '#6366f1'],
        ['#1e40af', '#3b82f6'],
        ['#0e7490', '#0891b2'],
        ['#4f46e5', '#059669'],
        ['#312e81', '#4f46e5'],
        ['#1d4ed8', '#0ea5e9'],
    ];

    public function run(): void
    {
        $path = database_path('seeders/data/blog_articles.json');
        if (! is_file($path)) {
            $this->command?->warn("BlogSeeder: data tidak ditemukan di $path — dilewati.");
            return;
        }

        $articles = json_decode(file_get_contents($path), true) ?: [];
        if (! $articles) {
            $this->command?->warn('BlogSeeder: data artikel kosong — dilewati.');
            return;
        }

        // Penulis = Superadmin pertama (boleh null bila belum ada).
        $author = User::withoutGlobalScopes()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Superadmin'))
            ->first();

        Storage::disk('public')->makeDirectory('blog');

        $i = 0;
        foreach ($articles as $a) {
            $catName = trim($a['category'] ?? 'Umum');
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($catName)],
                ['name' => $catName]
            );

            $key  = $a['key'] ?? Str::slug($a['title']);
            $slug = Str::slug($a['title']);
            [$c1, $c2] = $this->palette[$i % count($this->palette)];

            // Cover SVG bertema (disimpan sebagai file, referensi via kolom cover).
            $coverName = 'seed-' . $key . '.svg';
            Storage::disk('public')->put('blog/' . $coverName, $this->coverSvg($c1, $c2, $catName));

            Post::updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id'          => $author?->id,
                    'blog_category_id' => $category->id,
                    'title'            => $a['title'],
                    'excerpt'          => $a['excerpt'] ?? null,
                    'body'             => isset($a['body']) ? Purifier::clean($a['body'], 'blog') : null,
                    'cover'            => $coverName,
                    'status'           => 'published',
                    // Stagger: artikel pertama di array = paling baru.
                    'published_at'     => now()->subDays($i * 2)->subHours($i * 3),
                    'meta_title'       => $a['title'],
                    'meta_description' => $a['excerpt'] ?? null,
                ]
            );
            $i++;
        }

        $this->command?->info("BlogSeeder: {$i} artikel + " . Category::count() . ' kategori ter-seed.');
    }

    /** Cover SVG 1200x630 bertema: gradient + bentuk lembut + label kategori + wordmark Mooda. */
    private function coverSvg(string $c1, string $c2, string $label): string
    {
        $lbl = htmlspecialchars(mb_strtoupper($label), ENT_QUOTES, 'UTF-8');
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="$c1"/>
      <stop offset="1" stop-color="$c2"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#g)"/>
  <circle cx="1010" cy="110" r="280" fill="#ffffff" opacity="0.09"/>
  <circle cx="150" cy="580" r="220" fill="#ffffff" opacity="0.07"/>
  <circle cx="980" cy="520" r="90" fill="#ffffff" opacity="0.10"/>
  <text x="90" y="330" font-family="Plus Jakarta Sans, Arial, sans-serif" font-size="42" font-weight="700" fill="#ffffff" opacity="0.92" letter-spacing="6">$lbl</text>
  <rect x="90" y="360" width="120" height="6" rx="3" fill="#ffffff" opacity="0.7"/>
  <g transform="translate(90,500)">
    <rect width="56" height="56" rx="16" fill="#ffffff"/>
    <text x="28" y="41" text-anchor="middle" font-family="Arial, sans-serif" font-size="36" font-weight="800" fill="$c1">M</text>
    <text x="76" y="39" font-family="Plus Jakarta Sans, Arial, sans-serif" font-size="32" font-weight="800" fill="#ffffff">Mooda Blog</text>
  </g>
</svg>
SVG;
    }
}

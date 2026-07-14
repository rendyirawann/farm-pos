<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul BLOG (marketing, milik perusahaan — BUKAN per-tenant, tanpa TenantScope).
 * Artikel blog: draft/published, cover, body HTML (disanitasi), SEO meta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable();                 // penulis (users.id = uuid)
            $table->foreignId('blog_category_id')->nullable()
                  ->constrained('blog_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();          // ringkasan (list & fallback meta)
            $table->longText('body')->nullable();                // HTML dari CKEditor (sudah disanitasi)
            $table->string('cover')->nullable();                 // nama file di storage/app/public/blog
            $table->string('status')->default('draft');          // draft | published
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'published_at']);           // query publik (published terbaru)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};

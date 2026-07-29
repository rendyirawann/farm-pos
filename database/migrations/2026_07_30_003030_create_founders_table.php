<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position');
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();   // path di storage/public/founders
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed 3 founder (foto kosong -> placeholder; bisa di-upload Superadmin).
        $now = now();
        DB::table('founders')->insert([
            ['name' => 'Prasti Surya', 'position' => 'CEO / Product', 'sort_order' => 1,
             'bio' => 'Pengalaman di F&B dan teknologi, mengerti pain point UMKM.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rendy', 'position' => 'CTO / Engineering', 'sort_order' => 2,
             'bio' => 'Pengalaman software engineering, membangun SaaS yang scalable.', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Muhammad Rizki Ananda, A.Md', 'position' => 'CMO / Sales', 'sort_order' => 3,
             'bio' => 'Pengalaman sales dan marketing, memiliki jaringan UMKM yang luas.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('founders');
    }
};

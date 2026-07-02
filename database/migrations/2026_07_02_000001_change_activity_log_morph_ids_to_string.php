<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah activity_log.subject_id & causer_id dari UUID menjadi VARCHAR agar bisa
 * menampung UUID (users) maupun bigint (menu/order/kategori/dll). Ini yang
 * memungkinkan pencatatan otomatis untuk seluruh model.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_log')) {
            return;
        }
        // Aman dijalankan baik saat kolom masih uuid maupun sudah varchar.
        DB::statement("ALTER TABLE activity_log ALTER COLUMN subject_id TYPE varchar(255) USING subject_id::text");
        DB::statement("ALTER TABLE activity_log ALTER COLUMN causer_id TYPE varchar(255) USING causer_id::text");
    }

    public function down(): void
    {
        // Revert best-effort (bisa gagal bila sudah ada id non-UUID tersimpan).
        try {
            DB::statement("ALTER TABLE activity_log ALTER COLUMN subject_id TYPE uuid USING subject_id::uuid");
            DB::statement("ALTER TABLE activity_log ALTER COLUMN causer_id TYPE uuid USING causer_id::uuid");
        } catch (\Throwable $e) {
            // biarkan tetap varchar
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Longgarkan kolom `plan` (enum -> string) agar mendukung paket baru
     * (basic/enterprise/customize) + migrasi data lama 'starter' -> 'basic'.
     * Di PostgreSQL, enum Laravel = varchar + CHECK constraint; cukup drop constraint-nya.
     */
    public function up(): void
    {
        // 1) Drop SEMUA CHECK constraint pada kolom `plan` (robust: apa pun nama constraint-nya,
        //    baik nama standar tenants_plan_check maupun berbeda). Hanya untuk PostgreSQL.
        if (DB::getDriverName() === 'pgsql') {
            foreach (['tenants', 'subscriptions'] as $tbl) {
                $rows = DB::select(
                    "SELECT c.conname FROM pg_constraint c
                     JOIN pg_class t ON t.oid = c.conrelid
                     JOIN pg_namespace n ON n.oid = t.relnamespace
                     WHERE t.relname = ? AND c.contype = 'c'
                       AND pg_get_constraintdef(c.oid) ILIKE '%plan%'",
                    [$tbl]
                );
                foreach ($rows as $r) {
                    DB::statement('ALTER TABLE ' . $tbl . ' DROP CONSTRAINT IF EXISTS "' . $r->conname . '"');
                }
            }
        } else {
            // Fallback (MySQL/SQLite): nama constraint standar Laravel.
            DB::statement('ALTER TABLE tenants DROP CONSTRAINT IF EXISTS tenants_plan_check');
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_plan_check');
        }

        // 2) Migrasi data lama: hanya baris 'starter' yang diubah -> 'basic'.
        //    'customize' & NULL tidak disentuh. Non-destruktif untuk langganan berjalan.
        DB::table('tenants')->where('plan', 'starter')->update(['plan' => 'basic']);
        DB::table('subscriptions')->where('plan', 'starter')->update(['plan' => 'basic']);
    }

    public function down(): void
    {
        DB::table('tenants')->where('plan', 'basic')->update(['plan' => 'starter']);
        DB::table('subscriptions')->where('plan', 'basic')->update(['plan' => 'starter']);
        // Constraint enum tidak dibuat ulang (kolom dibiarkan varchar).
    }
};

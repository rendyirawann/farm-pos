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
        DB::statement('ALTER TABLE tenants DROP CONSTRAINT IF EXISTS tenants_plan_check');
        DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_plan_check');

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

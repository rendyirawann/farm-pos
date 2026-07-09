<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Mode langganan tenant: 'monthly' (paket per bulan) atau 'deposit' (poin).
            // Default 'monthly' agar tenant lama tidak berubah perilaku.
            $table->enum('billing_mode', ['monthly', 'deposit'])->default('monthly')->after('plan');

            // Saldo poin deposit (nilai Rupiah). Dibekukan saat mode 'monthly'.
            $table->decimal('deposit_points', 15, 2)->default(0)->after('billing_mode');

            // Kapan poin hangus bila tidak ada aktivitas. Di-reset tiap top-up / pemakaian.
            $table->timestamp('deposit_expires_at')->nullable()->after('deposit_points');

            // Catatan waktu poin terakhir dipakai (untuk tampilan & audit).
            $table->timestamp('deposit_last_used_at')->nullable()->after('deposit_expires_at');

            $table->index('billing_mode');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['billing_mode']);
            $table->dropColumn([
                'billing_mode',
                'deposit_points',
                'deposit_expires_at',
                'deposit_last_used_at',
            ]);
        });
    }
};

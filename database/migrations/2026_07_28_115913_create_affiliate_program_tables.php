<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Setelan program affiliate tingkat-platform (satu baris / singleton).
        Schema::create('affiliate_settings', function (Blueprint $table) {
            $table->id();
            // Komisi affiliate per pendaftaran plan berbayar (Basic/Enterprise).
            $table->string('commission_type', 20)->default('flat'); // 'flat' (Rp) | 'percent'
            $table->decimal('commission_value', 12, 2)->default(0);  // rupiah bila flat; persen bila percent
            // Cashback untuk USER yang daftar via referral, dipotong saat bayar plan.
            $table->decimal('cashback_percent', 5, 2)->default(0);   // 0 = tanpa cashback
            $table->timestamps();
        });

        // Pengajuan pencairan komisi oleh affiliate.
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();                    // kode unik / invoice pencairan
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');        // pending | done | rejected
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });

        // Tautan referral -> pencairan (komisi mana yang ditarik).
        Schema::table('referrals', function (Blueprint $table) {
            $table->foreignId('withdrawal_id')->nullable()->after('commission_status')
                ->constrained('withdrawals')->nullOnDelete();
        });

        // Catatan cashback pada langganan (untuk keterangan pembayaran).
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('cashback_percent', 5, 2)->nullable()->after('amount');
            $table->integer('cashback_amount')->nullable()->after('cashback_percent');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['cashback_percent', 'cashback_amount']);
        });
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_id');
        });
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('affiliate_settings');
    }
};

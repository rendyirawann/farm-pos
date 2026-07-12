<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Rincian yang disimpan saat tutup shift (informasi untuk kartu tutup & sales report):
            //  - budget_amount : anggaran pengeluaran hari itu (kas belanja yg disiapkan).
            //  - expense_total : total pengeluaran selama shift berlangsung.
            if (! Schema::hasColumn('shifts', 'budget_amount')) {
                $table->decimal('budget_amount', 15, 2)->nullable()->after('cash_sales');
            }
            if (! Schema::hasColumn('shifts', 'expense_total')) {
                $table->decimal('expense_total', 15, 2)->nullable()->after('budget_amount');
            }
        });

        // Backfill INFORMASI utk shift yang sudah ditutup (TIDAK mengubah expected_cash/difference,
        // agar riwayat rekonsiliasi tetap apa adanya). Aman diulang (idempoten).
        DB::statement("
            UPDATE shifts s
            SET budget_amount = b.amount
            FROM daily_budgets b
            WHERE b.tenant_id = s.tenant_id
              AND b.date = (s.start_time)::date
              AND s.status = 'closed'
        ");

        DB::statement("
            UPDATE shifts s
            SET expense_total = COALESCE((
                SELECT SUM(e.amount)
                FROM expenses e
                WHERE e.tenant_id = s.tenant_id
                  AND e.created_at >= s.start_time
                  AND (s.end_time IS NULL OR e.created_at <= s.end_time)
            ), 0)
            WHERE s.status = 'closed'
        ");
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'expense_total')) {
                $table->dropColumn('expense_total');
            }
            if (Schema::hasColumn('shifts', 'budget_amount')) {
                $table->dropColumn('budget_amount');
            }
        });
    }
};

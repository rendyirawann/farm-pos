<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Penanda sumber tenant: 'manual' (dibuat Superadmin), 'midtrans' (bayar online),
            // 'self' (registrasi mandiri), null (tak diketahui/lama).
            if (! Schema::hasColumn('tenants', 'created_via')) {
                $table->string('created_via', 20)->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'created_via')) {
                $table->dropColumn('created_via');
            }
        });
    }
};

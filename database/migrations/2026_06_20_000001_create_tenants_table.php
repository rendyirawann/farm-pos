<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');                 // Nama bisnis / resto
            $table->string('slug')->unique();
            $table->string('business_type')->nullable(); // Resto, Cafe, Warung, dll
            $table->uuid('owner_id')->nullable();   // user pemilik (relasi ke users.id yang UUID)
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();

            // Langganan
            $table->enum('plan', ['starter', 'customize'])->nullable();
            $table->enum('subscription_status', ['trial', 'active', 'expired', 'inactive'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            // Kontrol oleh Superadmin (suspend/aktifkan)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('subscription_status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

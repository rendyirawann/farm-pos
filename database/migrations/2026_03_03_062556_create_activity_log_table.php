<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->string('event')->nullable(); // Memastikan kolom event juga ada
            $table->text('description');

            // ID morph disimpan sebagai STRING agar bisa menampung UUID (users)
            // maupun bigint (menu/order/kategori/dll) sekaligus.
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->string('causer_type')->nullable();
            $table->string('causer_id')->nullable();
            $table->index(['causer_type', 'causer_id'], 'causer');

            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable(); // Ini yang tadi bikin bentrok
            $table->timestamps();

            $table->index('log_name');
        });
    }
    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}

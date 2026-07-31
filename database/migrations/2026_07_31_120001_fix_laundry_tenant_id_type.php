<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN: tabel laundry_* dibuat dengan tenant_id bertipe UUID, sedangkan SELURUH
 * tabel ber-tenant lain (users, menus, orders, dst.) memakai BIGINT (FK ke tenants.id).
 * Akibatnya insert data laundry selalu gagal:
 *   SQLSTATE[22P02] invalid input syntax for type uuid: "27"
 *
 * Migrasi ini menyeragamkan tipe ke bigint + menambah FK ke tenants(id).
 * Aman: tabel laundry_* masih kosong saat migrasi ini dibuat.
 */
return new class extends Migration
{
    /** Tabel laundry yang punya kolom tenant_id. */
    private array $tables = ['laundry_services', 'laundry_customers', 'laundry_orders'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            if ($this->columnType($table, 'tenant_id') !== 'uuid') {
                continue; // sudah benar
            }

            // Kosongkan data lama (bila ada) supaya konversi tipe tidak gagal.
            DB::table($table)->delete();

            DB::statement("ALTER TABLE {$table} ALTER COLUMN tenant_id TYPE bigint USING NULL");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN tenant_id SET NOT NULL");
            DB::statement(
                "ALTER TABLE {$table} ADD CONSTRAINT {$table}_tenant_id_foreign "
                . 'FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
            );
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            if ($this->columnType($table, 'tenant_id') === 'uuid') {
                continue;
            }
            DB::table($table)->delete();
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$table}_tenant_id_foreign");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN tenant_id TYPE uuid USING NULL");
        }
    }

    private function columnType(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
            [$table, $column]
        );
        return $row->data_type ?? null;
    }
};

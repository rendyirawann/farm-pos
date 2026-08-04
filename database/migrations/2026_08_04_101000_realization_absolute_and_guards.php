<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan rancangan realisasi & pengaman stok, hasil telaah adversarial.
 *
 * 1) REALISASI DISIMPAN ABSOLUT, BUKAN DELTA.
 *    Menyimpan "kurang 40 kg" membuat klik Simpan dua kali menghasilkan koreksi
 *    ganda (+2x nilai) tanpa cara mendeteksinya. Menyimpan "yang nyata diterima
 *    960 kg" bersifat idempoten: disimpan berapa kali pun hasilnya sama.
 *    Ditambah UNIQUE per baris nota, satu baris nota hanya punya satu realisasi.
 *
 * 2) SATU REALISASI PER BARIS NOTA.
 *    Nota bisa campuran: baris A kurang 4 kg, baris B lebih 3 kg. Bila arah
 *    disimpan di tingkat nota, operator dipaksa memasukkan satu angka neto dan
 *    koreksi lot kedua barang jadi salah.
 *
 * 3) PENGAMAN STOK DI TINGKAT BASIS DATA.
 *    CHECK sisa lot tidak boleh negatif — jaring terakhir bila ada dua kasir
 *    menyimpan bersamaan atau ada jalur kode yang lupa mengunci baris.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabel realisasi lama dibuang & dibuat ulang: bentuknya berubah mendasar
        // (delta -> absolut) dan datanya masih kosong setelah reset tenant.
        Schema::dropIfExists('farm_stock_in_realizations');

        Schema::create('farm_stock_in_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stock_in_id')->index();
            $table->foreignId('stock_in_line_id');
            $table->foreignId('supplier_id')->nullable()->index();
            $table->date('date')->index();

            // Alasan selisih — bukan penentu arah, hanya keterangan.
            $table->string('reason', 30)->default('kurang_timbang');

            // ANGKA NYATA hasil timbang ulang (absolut, bukan selisih).
            $table->integer('received_qty_ekor')->default(0);
            $table->decimal('received_weight_kg', 12, 2)->default(0);

            // Selisih terhadap nota, disimpan bertanda agar mudah dibaca & diaudit.
            // negatif = barang kurang dari nota; positif = lebih.
            $table->integer('delta_qty_ekor')->default(0);
            $table->decimal('delta_weight_kg', 12, 2)->default(0);

            // Nilai koreksi deposit, BERTANDA: positif menambah saldo supplier
            // (karena kita kelebihan potong), negatif mengurangi.
            $table->decimal('value', 15, 2)->default(0);

            // Lot baru yang dibuat bila barang ternyata LEBIH (tidak menambah lot lama,
            // supaya urutan FIFO & harga pokok historis tidak berubah).
            $table->foreignId('extra_lot_id')->nullable();

            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Satu baris nota hanya boleh punya SATU realisasi -> idempoten.
            $table->unique(['tenant_id', 'stock_in_line_id'], 'farm_real_line_unik');
        });

        // Lot menandai asal-usulnya: pembelian, koreksi realisasi, atau produksi.
        Schema::table('farm_stock_lots', function (Blueprint $table) {
            $table->string('source', 20)->default('purchase')->after('supplier_id')->index();
            $table->foreignId('realization_id')->nullable()->after('source');
        });
        DB::statement("UPDATE farm_stock_lots SET source = 'purchase' WHERE source IS NULL");

        // Jejak lot yang dipakai penyesuaian stok — tanpa ini, HPP per supplier
        // tidak bisa dihitung karena tak diketahui lot (dan supplier) mana yang susut.
        Schema::create('farm_adjustment_lot_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('adjustment_id')->index();
            $table->foreignId('lot_id')->index();
            $table->integer('qty_ekor')->default(0);
            $table->decimal('weight_kg', 12, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->timestamps();
        });

        // Jaring terakhir: sisa lot tidak boleh negatif.
        DB::statement('ALTER TABLE farm_stock_lots ADD CONSTRAINT farm_lot_sisa_kg_tidak_negatif CHECK (weight_kg_left >= 0)');
        DB::statement('ALTER TABLE farm_stock_lots ADD CONSTRAINT farm_lot_sisa_ekor_tidak_negatif CHECK (qty_ekor_left >= 0)');

        // Buku besar deposit bersifat APPEND-ONLY: pembatalan dibukukan sebagai
        // baris balik, bukan dengan mengubah/menghapus baris lama.
        Schema::table('farm_supplier_deposits', function (Blueprint $table) {
            $table->foreignId('reverses_id')->nullable()->after('reference_id');
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE farm_stock_lots DROP CONSTRAINT IF EXISTS farm_lot_sisa_kg_tidak_negatif');
        DB::statement('ALTER TABLE farm_stock_lots DROP CONSTRAINT IF EXISTS farm_lot_sisa_ekor_tidak_negatif');
        Schema::dropIfExists('farm_adjustment_lot_usages');
        Schema::table('farm_stock_lots', function (Blueprint $table) {
            $table->dropColumn(['source', 'realization_id']);
        });
        Schema::table('farm_supplier_deposits', function (Blueprint $table) {
            $table->dropColumn('reverses_id');
        });
        Schema::dropIfExists('farm_stock_in_realizations');
    }
};

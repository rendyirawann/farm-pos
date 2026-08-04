<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REALISASI: SATU NOTA = SATU REALISASI.
 *
 * Keputusan pemilik produk: petugas tidak boleh menambah realisasi berkali-kali
 * pada satu nota. Sekali dicatat, koreksi berikutnya hanya lewat "Ubah Realisasi"
 * (batal + catat ulang), bukan menumpuk baris baru.
 *
 * Bentuknya jadi dua tabel:
 *   header (tabel ini)  -> satu baris per NOTA, dikunci UNIQUE(tenant_id, stock_in_id)
 *   baris  (…_lines)    -> angka nyata per barang, karena satu nota bisa campuran:
 *                          broiler kurang 4 kg sementara kampung lebih 3 kg.
 *
 * Angka disimpan ABSOLUT ("yang benar-benar diterima"), bukan selisih, supaya
 * penyimpanan ganda tidak pernah menggandakan koreksi. Selisih (delta) ikut
 * disimpan sebagai keterangan agar mudah dibaca dan diaudit.
 *
 * Barang LEBIH tidak lagi membuat lot baru (permintaan pemilik produk): lot nota
 * itu sendiri yang disesuaikan ke angka nyata, dan catatan "lebih 3 kg" tetap
 * tersimpan pada baris realisasi. Agar harga pokok yang sudah terjual tidak
 * berubah retroaktif, realisasi hanya boleh dilakukan selama lot BELUM terpakai
 * (dijaga di RealizationService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('farm_stock_in_realizations');

        // ---- Header: satu per nota ----
        Schema::create('farm_stock_in_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stock_in_id');
            $table->foreignId('supplier_id')->nullable()->index();
            $table->date('date')->index();

            // Alasan umum selisih — keterangan, bukan penentu arah uang.
            $table->string('reason', 30)->default('kurang_timbang');

            // Rekap selisih seluruh baris, disimpan bertanda:
            // negatif = barang kurang dari nota, positif = lebih.
            $table->integer('delta_qty_ekor')->default(0);
            $table->decimal('delta_weight_kg', 12, 2)->default(0);

            // Koreksi saldo deposit supplier, BERTANDA:
            // positif = saldo supplier NAIK (kita kelebihan potong karena barang kurang),
            // negatif = saldo supplier TURUN (barang lebih, kurang dipotong).
            $table->decimal('value', 15, 2)->default(0);

            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Inti keputusan: satu nota hanya boleh punya SATU realisasi.
            $table->unique(['tenant_id', 'stock_in_id'], 'farm_real_nota_unik');
        });

        // ---- Baris: angka nyata per barang ----
        Schema::create('farm_stock_in_realization_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('realization_id')->index();
            $table->foreignId('stock_in_line_id');
            $table->foreignId('lot_id')->nullable()->index();

            // Angka pada nota, disalin saat realisasi dicatat supaya laporan lama
            // tetap terbaca walau nota diedit setelahnya.
            $table->integer('nota_qty_ekor')->default(0);
            $table->decimal('nota_weight_kg', 12, 2)->default(0);

            // ANGKA NYATA hasil timbang ulang (absolut).
            $table->integer('received_qty_ekor')->default(0);
            $table->decimal('received_weight_kg', 12, 2)->default(0);

            // Selisih = nyata − nota. Negatif kurang, positif lebih.
            $table->integer('delta_qty_ekor')->default(0);
            $table->decimal('delta_weight_kg', 12, 2)->default(0);

            // Dasar & harga satuan disalin dari nota: nilai selisih dihitung dengan
            // dasar yang SAMA seperti saat membeli, tidak menebak per kg atau per ekor.
            $table->string('price_basis', 10)->default('kg');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('value', 15, 2)->default(0);      // bertanda, sama arah dengan header

            $table->timestamps();

            // Satu baris nota tidak mungkin direalisasi dua kali -> idempoten.
            $table->unique(['tenant_id', 'stock_in_line_id'], 'farm_real_baris_unik');
        });

        // Lot tidak lagi dibuat oleh realisasi, jadi rujukan ini tidak dipakai.
        // Kolom `source` tetap ada karena masih membedakan pembelian vs produksi telur.
        if (Schema::hasColumn('farm_stock_lots', 'realization_id')) {
            Schema::table('farm_stock_lots', function (Blueprint $table) {
                $table->dropColumn('realization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_stock_in_realization_lines');
        Schema::dropIfExists('farm_stock_in_realizations');
        Schema::table('farm_stock_lots', function (Blueprint $table) {
            $table->foreignId('realization_id')->nullable();
        });
    }
};

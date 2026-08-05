<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1) FOTO BUKTI PENYESUAIAN STOK.
 *    Penyesuaian mengurangi stok dan membebani laba tanpa dokumen dari pihak luar
 *    — tidak ada nota supplier atau nota agen yang bisa dicocokkan. Fotonya
 *    itulah satu-satunya bukti bahwa ayamnya memang mati / rusak / susut.
 *    Dikecualikan hanya untuk alasan HILANG: barang yang hilang memang tidak
 *    ada wujudnya untuk difoto.
 *
 * 2) PRODUKSI TELUR DIKAITKAN KE LOT-nya.
 *    Setiap pencatatan produksi sudah membuat satu lot stok, tetapi rujukannya
 *    tidak disimpan. Akibatnya sisa telur per pencatatan tidak bisa ditampilkan,
 *    dan menghapus catatan produksi meninggalkan lot menggantung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_stock_adjustments', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('notes');
        });

        Schema::table('farm_egg_productions', function (Blueprint $table) {
            $table->foreignId('lot_id')->nullable()->after('item_id')->index();
        });

        // Sambungkan catatan produksi lama ke lot yang dibuatnya: dicocokkan lewat
        // item + tanggal + jumlah butir bersih, dan hanya lot tanpa supplier
        // (lot produksi sendiri) yang boleh ikut.
        foreach (DB::table('farm_egg_productions')->whereNull('lot_id')->get() as $p) {
            $bersih = (int) $p->qty_butir - (int) $p->qty_broken;
            if ($bersih <= 0) {
                continue;
            }

            $lot = DB::table('farm_stock_lots')
                ->where('tenant_id', $p->tenant_id)
                ->where('item_id', $p->item_id)
                ->whereNull('supplier_id')
                ->whereNull('stock_in_line_id')
                ->where('date', $p->date)
                ->where('qty_ekor_initial', $bersih)
                ->whereNotIn('id', function ($q) {
                    $q->select('lot_id')->from('farm_egg_productions')->whereNotNull('lot_id');
                })
                ->orderBy('id')->first();

            if ($lot) {
                DB::table('farm_egg_productions')->where('id', $p->id)->update(['lot_id' => $lot->id]);
                // Sekalian tandai asalnya supaya laporan bisa memisahkan
                // produksi sendiri dari pembelian.
                DB::table('farm_stock_lots')->where('id', $lot->id)->update(['source' => 'production']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('farm_stock_adjustments', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
        Schema::table('farm_egg_productions', function (Blueprint $table) {
            $table->dropColumn('lot_id');
        });
    }
};

<?php

namespace App\Console\Commands;

use App\Models\Farm\Agent;
use App\Models\Farm\AgentPayment;
use App\Models\Farm\Item;
use App\Models\Farm\StockAdjustment;
use App\Models\Farm\StockIn;
use App\Models\Farm\StockInLine;
use App\Models\Farm\StockLot;
use App\Models\Farm\StockOut;
use App\Models\Farm\StockOutLine;
use App\Models\Farm\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Farm\DepositService;
use App\Services\Farm\FarmStockService;
use App\Services\Farm\RealizationService;
use App\Tenancy\TenantManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data contoh untuk modul peternakan (Mooda Stok).
 *
 * Dibuat lewat SERVICE yang sama dengan yang dipakai layar sungguhan
 * (DepositService, RealizationService, FarmStockService), bukan lewat INSERT
 * langsung. Kalau data contoh dibuat dengan jalan pintas, angka saldo/HPP-nya
 * tidak akan pernah cocok dengan hasil pemakaian nyata — dan justru itu yang
 * ingin diuji sebelum diserahkan ke klien.
 *
 * Akun pengguna TIDAK disentuh.
 */
class FarmSeedDummy extends Command
{
    protected $signature = 'farm:seed-dummy
        {--tenant= : ID tenant peternakan (bawaan: tenant vertical=farm pertama)}
        {--suppliers=8 : jumlah supplier}
        {--agents=6 : jumlah agen}
        {--days=60 : rentang hari ke belakang}
        {--in=45 : jumlah nota barang masuk}
        {--out=38 : jumlah nota barang keluar}
        {--fresh : hapus dulu seluruh data transaksi & master peternakan}
        {--force : jalankan tanpa bertanya}';

    protected $description = 'Isi farm.mooda.id dengan data contoh (supplier, deposit, barang masuk/keluar, realisasi, penyesuaian)';

    /** Diacak dengan benih tetap supaya hasilnya bisa diulang & diperiksa. */
    private int $benih = 20260804;

    public function handle(
        TenantManager $tenancy,
        DepositService $deposit,
        RealizationService $realisasi,
        FarmStockService $stok,
    ): int {
        $tenant = $this->option('tenant')
            ? Tenant::find((int) $this->option('tenant'))
            : Tenant::where('vertical', 'farm')->orderBy('id')->first();

        if (! $tenant) {
            $this->error('Tenant peternakan tidak ditemukan.');

            return self::FAILURE;
        }

        $tenancy->setTenant($tenant);
        mt_srand($this->benih);

        $petugas = User::where('tenant_id', $tenant->id)->orderBy('created_at')->first();
        if ($petugas) {
            Auth::login($petugas);      // supaya kolom user_id terisi seperti pemakaian nyata
        }

        $this->info("Tenant: {$tenant->name} (#{$tenant->id})");

        if (! $this->option('force') && ! $this->confirm('Lanjutkan mengisi data contoh?', true)) {
            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->bersihkan($tenant->id);
        }

        $hariMulai = Carbon::now()->subDays((int) $this->option('days'))->startOfDay();

        $items     = $this->buatItem();
        $suppliers = $this->buatSupplier((int) $this->option('suppliers'));
        $agents    = $this->buatAgen((int) $this->option('agents'));

        $this->line('');
        $this->info('1/5 Setoran deposit awal ke supplier');
        foreach ($suppliers as $s) {
            // Setoran awal 2-3 kali, tanggalnya menyebar di awal periode.
            foreach (range(1, mt_rand(2, 3)) as $n) {
                $deposit->topup($s, [
                    'amount' => mt_rand(15, 60) * 1_000_000,
                    'date'   => $hariMulai->copy()->addDays(mt_rand(0, 10))->toDateString(),
                    'notes'  => 'Transfer awal ' . $n,
                ]);
            }
        }
        $this->line('    ' . $suppliers->count() . ' supplier menerima setoran');

        $this->info('2/5 Nota barang masuk + realisasi');
        $notaMasuk = $this->buatBarangMasuk(
            (int) $this->option('in'), $hariMulai, $suppliers, $items, $deposit, $realisasi
        );

        $this->info('3/5 Nota barang keluar (FIFO)');
        $notaKeluar = $this->buatBarangKeluar(
            (int) $this->option('out'), $hariMulai, $agents, $items, $stok
        );

        $this->info('4/5 Penyesuaian stok (mati / susut)');
        $penyesuaian = $this->buatPenyesuaian($hariMulai, $items, $stok);

        $this->info('5/6 Pembayaran sebagian dari agen');
        $bayar = $this->buatPembayaranAgen();

        $this->info('6/6 Setoran lanjutan (menutup saldo minus)');
        $this->tutupSaldoMinus($deposit, $suppliers);

        $this->line('');
        $this->table(['Data', 'Jumlah'], [
            ['Supplier',            Supplier::count()],
            ['Agen',                Agent::count()],
            ['Item',                Item::count()],
            ['Baris buku deposit',  \App\Models\Farm\SupplierDeposit::count()],
            ['Nota barang masuk',   $notaMasuk],
            ['Realisasi',           \App\Models\Farm\StockInRealization::count()],
            ['Nota barang keluar',  $notaKeluar],
            ['Penyesuaian stok',    $penyesuaian],
            ['Pembayaran agen',     $bayar],
            ['Lot stok',            StockLot::count()],
        ]);

        $this->line('');
        $this->info('Saldo deposit per supplier:');
        foreach (Supplier::orderBy('name')->get() as $s) {
            $saldo = $s->depositBalance();
            $this->line(sprintf('    %-28s %15s %s', $s->name,
                number_format($saldo, 0, ',', '.'), $saldo < 0 ? '(kita belum bayar)' : ''));
        }

        return self::SUCCESS;
    }

    /** Hapus seluruh data peternakan. Akun & langganan tidak disentuh. */
    private function bersihkan(int $tenantId): void
    {
        $this->warn('Menghapus data peternakan yang ada…');

        // Berkas bon & bukti transfer ikut dibuang, bukan hanya barisnya.
        foreach (StockIn::whereNotNull('photos')->pluck('photos') as $daftar) {
            foreach ((array) $daftar as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
        foreach (\App\Models\Farm\SupplierDeposit::whereNotNull('proof_path')->pluck('proof_path') as $path) {
            Storage::disk('public')->delete($path);
        }

        // Urutan dari anak ke induk supaya tidak ada rujukan menggantung.
        $tabel = [
            'farm_stock_out_lot_usages', 'farm_stock_out_lines', 'farm_agent_payments', 'farm_stock_outs',
            'farm_adjustment_lot_usages', 'farm_stock_adjustments',
            'farm_stock_in_realization_lines', 'farm_stock_in_realizations',
            'farm_supplier_deposits',
            'farm_stock_lots', 'farm_stock_in_lines', 'farm_stock_ins',
            'farm_egg_productions', 'farm_warehouse_sessions',
            'farm_items', 'farm_agents', 'farm_suppliers',
        ];

        DB::transaction(function () use ($tabel, $tenantId) {
            foreach ($tabel as $t) {
                $n = DB::table($t)->where('tenant_id', $tenantId)->delete();
                if ($n) {
                    $this->line("    {$t}: {$n} baris");
                }
            }
        });
    }

    private function buatItem()
    {
        $daftar = [
            ['Ayam Broiler',      'ayam',  'kg',    false],
            ['Ayam Kampung',      'ayam',  'kg',    false],
            ['Ayam Putih',        'ayam',  'kg',    false],
            ['Ayam Petelur Afkir', 'ayam', 'kg',    false],
            ['Telur Ayam',        'telur', 'butir', true],
        ];

        return collect($daftar)->map(fn ($d) => Item::firstOrCreate(
            ['name' => $d[0]],
            ['category' => $d[1], 'primary_unit' => $d[2], 'is_produced' => $d[3], 'is_active' => true]
        ));
    }

    private function buatSupplier(int $jumlah)
    {
        $nama = [
            'BERKAH MAJU REJEKI', 'PS Sumber Unggas', 'CV Ternak Jaya Abadi',
            'H. Ujang Broiler', 'Peternakan Sari Ayam', 'UD Mitra Unggas Sejahtera',
            'Kandang Makmur Farm', 'PT Cipta Ayam Nusantara', 'Sentra Ayam Cianjur',
            'Poultry Barokah', 'Agro Ayam Lestari', 'KUD Tani Unggas',
        ];
        $kota = ['Cianjur', 'Sukabumi', 'Bogor', 'Subang', 'Karawang', 'Purwakarta', 'Bandung'];

        return collect(array_slice($nama, 0, $jumlah))->map(fn ($n, $i) => Supplier::firstOrCreate(
            ['name' => $n],
            [
                'phone'     => '08' . mt_rand(11, 99) . mt_rand(1000000, 9999999),
                'address'   => 'Jl. Raya ' . $kota[$i % count($kota)] . ' No. ' . mt_rand(1, 120),
                'is_active' => true,
            ]
        ));
    }

    private function buatAgen(int $jumlah)
    {
        $nama = [
            'Agen Pasar Induk', 'Toko Daging Sejahtera', 'Warung Bu Tini',
            'RM Ayam Bakar Pak Slamet', 'Agen Ayam Kilat', 'Pasar Cibitung',
            'Catering Sehat Bunda', 'Agen Pasar Minggu',
        ];

        return collect(array_slice($nama, 0, $jumlah))->map(fn ($n) => Agent::firstOrCreate(
            ['name' => $n],
            [
                'phone'        => '08' . mt_rand(11, 99) . mt_rand(1000000, 9999999),
                'address'      => 'Kios ' . mt_rand(1, 60),
                'term_days'    => [0, 7, 14, 30][mt_rand(0, 3)],
                'credit_limit' => mt_rand(5, 40) * 1_000_000,
                'is_active'    => true,
            ]
        ));
    }

    private function buatBarangMasuk(int $jumlah, Carbon $mulai, $suppliers, $items,
        DepositService $deposit, RealizationService $realisasi): int
    {
        $ayam = $items->where('is_produced', false)->values();
        $dibuat = 0;
        $hari = (int) $this->option('days');
        $bar = $this->output->createProgressBar($jumlah);

        for ($i = 0; $i < $jumlah; $i++) {
            $tanggal = $mulai->copy()->addDays(mt_rand(0, max(1, $hari - 2)));
            $supplier = $suppliers->random();

            $in = StockIn::create([
                'invoice_no' => 'BELI-' . $tanggal->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'date'       => $tanggal->toDateString(),
                'supplier_id' => $supplier->id,
                'user_id'    => Auth::id(),
                'total'      => 0,
                'notes'      => mt_rand(0, 4) === 0 ? 'Pengiriman pagi, mobil box' : null,
            ]);

            $total = 0.0;
            foreach (range(1, mt_rand(1, 2)) as $b) {
                $item = $ayam->random();
                $ekor = mt_rand(60, 400);
                // Bobot rata-rata 1,4-2,1 kg/ekor: rentang wajar ayam potong.
                $kg    = round($ekor * (mt_rand(140, 210) / 100), 2);
                $harga = mt_rand(24, 32) * 1000;
                $subtotal = round($harga * $kg, 2);

                $line = StockInLine::create([
                    'stock_in_id' => $in->id,
                    'item_id'     => $item->id,
                    'qty_ekor'    => $ekor,
                    'weight_kg'   => $kg,
                    'price_basis' => 'kg',
                    'unit_price'  => $harga,
                    'subtotal'    => $subtotal,
                ]);

                StockLot::create([
                    'item_id'           => $item->id,
                    'stock_in_line_id'  => $line->id,
                    'supplier_id'       => $in->supplier_id,
                    'date'              => $in->date,
                    'qty_ekor_initial'  => $ekor,
                    'weight_kg_initial' => $kg,
                    'qty_ekor_left'     => $ekor,
                    'weight_kg_left'    => $kg,
                    'cost_per_kg'       => round($subtotal / $kg, 2),
                    'cost_per_ekor'     => round($subtotal / $ekor, 2),
                    'source'            => 'purchase',
                ]);

                $total += $subtotal;
            }

            $in->update(['total' => round($total, 2)]);
            $deposit->chargePurchase($in->fresh());

            // Sebagian nota ditandai lunas ke supplier supaya kolom status terisi
            // dua-duanya di daftar.
            if (mt_rand(0, 2) > 0) {
                $in->update([
                    'payment_status' => 'paid',
                    'paid_amount'    => $in->fresh()->netTotal(),
                    'paid_at'        => $tanggal->copy()->addDays(mt_rand(0, 3))->toDateString(),
                ]);
            }

            // Kira-kira sepertiga nota mengalami selisih timbangan. Dicatat lewat
            // service yang sama dengan layar realisasi, termasuk koreksi depositnya.
            if (mt_rand(0, 2) === 0) {
                $baris = [];
                foreach ($in->lines as $l) {
                    // −2% sampai +1%: kurang lebih sesuai kenyataan susut perjalanan.
                    $faktor = mt_rand(-200, 100) / 10000;
                    $baris[$l->id] = [
                        'qty_ekor'  => (int) $l->qty_ekor - ($faktor < 0 ? mt_rand(0, 3) : 0),
                        'weight_kg' => round((float) $l->weight_kg * (1 + $faktor), 2),
                    ];
                }

                try {
                    $realisasi->record($in->fresh(), [
                        'date'   => $tanggal->copy()->addDay()->toDateString(),
                        'reason' => ['kurang_timbang', 'susut', 'mati'][mt_rand(0, 2)],
                        'lines'  => $baris,
                        'notes'  => 'Hasil timbang ulang di gudang',
                    ]);
                } catch (\Throwable $e) {
                    // Tidak ada selisih / lot sudah terpakai -> lewati, bukan kegagalan.
                }
            }

            $dibuat++;
            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        return $dibuat;
    }

    private function buatBarangKeluar(int $jumlah, Carbon $mulai, $agents, $items, FarmStockService $stok): int
    {
        $hari = (int) $this->option('days');
        $dibuat = 0;
        $bar = $this->output->createProgressBar($jumlah);

        for ($i = 0; $i < $jumlah; $i++) {
            // Penjualan selalu SETELAH ada barang masuk: tanggalnya diambil dari
            // paruh kedua periode supaya FIFO punya lot untuk diambil.
            $tanggal = $mulai->copy()->addDays(mt_rand((int) ($hari * 0.2), $hari));
            $agen = $agents->random();
            $lunas = mt_rand(0, 2) > 0;

            $out = StockOut::create([
                'invoice_no' => 'JUAL-' . $tanggal->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'date'       => $tanggal->toDateString(),
                'agent_id'   => $agen->id,
                'user_id'    => Auth::id(),
                'payment_status' => $lunas ? 'paid' : 'unpaid',
                'due_date'   => $lunas ? null : $tanggal->copy()->addDays((int) ($agen->term_days ?: 7))->toDateString(),
                'paid_amount' => 0,
            ]);

            $totalJual = $totalModal = 0.0;

            foreach (range(1, mt_rand(1, 3)) as $b) {
                // Hanya item yang benar-benar punya stok — supaya HPP tidak 0.
                $tersedia = $items->where('is_produced', false)->filter(function ($it) {
                    return (float) StockLot::where('item_id', $it->id)->sum('weight_kg_left') > 20;
                })->values();

                if ($tersedia->isEmpty()) {
                    break;
                }

                $item = $tersedia->random();
                $sisaKg = (float) StockLot::where('item_id', $item->id)->sum('weight_kg_left');
                $sisaEkor = (int) StockLot::where('item_id', $item->id)->sum('qty_ekor_left');

                // Ambil 10-40% sisa stok, dibatasi agar tidak pernah melebihi stok.
                $kg = round(min($sisaKg * (mt_rand(10, 40) / 100), $sisaKg), 2);
                if ($kg < 5) {
                    continue;
                }
                $ekor = max(1, (int) round($kg / max(0.1, $sisaKg / max(1, $sisaEkor))));
                $ekor = min($ekor, $sisaEkor);

                $hpp   = $stok->previewCost($item->id, $kg, $ekor);
                $dasar = $hpp['hpp_per_kg'] ?: 26000;
                // Harga jual di atas harga pokok: marginnya wajar, bukan acak buta.
                $harga = (int) (round(($dasar + mt_rand(1500, 4500)) / 100) * 100);

                $subtotal = round($harga * $kg, 2);

                $line = StockOutLine::create([
                    'stock_out_id' => $out->id,
                    'item_id'      => $item->id,
                    'qty_ekor'     => $ekor,
                    'weight_kg'    => $kg,
                    'price_basis'  => 'kg',
                    'unit_price'   => $harga,
                    'subtotal'     => $subtotal,
                ]);

                $hasil = $stok->consumeFifo($item->id, $kg, $ekor);
                $stok->recordUsages($line, $hasil['usages']);
                $line->update([
                    'cost'   => $hasil['cost'],
                    'profit' => round($subtotal - $hasil['cost'], 2),
                ]);

                $totalJual  += $subtotal;
                $totalModal += $hasil['cost'];
            }

            if ($out->lines()->count() === 0) {
                $out->delete();
                $bar->advance();
                continue;
            }

            $out->update([
                'total_sale'   => round($totalJual, 2),
                'total_cost'   => round($totalModal, 2),
                'gross_profit' => round($totalJual - $totalModal, 2),
                'paid_amount'  => $lunas ? round($totalJual, 2) : 0,
                'paid_at'      => $lunas ? $tanggal->toDateString() : null,
            ]);

            $dibuat++;
            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        return $dibuat;
    }

    private function buatPenyesuaian(Carbon $mulai, $items, FarmStockService $stok): int
    {
        $dibuat = 0;

        foreach (range(1, 12) as $i) {
            $lot = StockLot::where('weight_kg_left', '>', 5)->where('qty_ekor_left', '>', 2)
                ->inRandomOrder()->first();
            if (! $lot) {
                break;
            }

            $ekor = mt_rand(1, min(5, (int) $lot->qty_ekor_left));
            $kg   = round(min((float) $lot->weight_kg_left, $ekor * 1.7), 2);

            $adj = StockAdjustment::create([
                'ref_no'    => 'ADJ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'date'      => $lot->date->copy()->addDays(mt_rand(1, 5))->toDateString(),
                'item_id'   => $lot->item_id,
                'lot_id'    => $lot->id,
                'reason'    => ['mati', 'susut', 'rusak'][mt_rand(0, 2)],
                'qty_ekor'  => $ekor,
                'weight_kg' => $kg,
                'user_id'   => Auth::id(),
                'notes'     => 'Temuan saat pengecekan kandang',
            ]);

            $stok->applyAdjustment($adj);
            $dibuat++;
        }

        return $dibuat;
    }

    /**
     * Setoran lanjutan supaya sebagian besar supplier bersaldo positif.
     *
     * DUA supplier sengaja dibiarkan minus: keadaan "kita belum bayar" adalah
     * kondisi yang paling perlu dilihat klien saat mencoba, jadi harus ada
     * contohnya di data — bukan cuma teori di layar kosong.
     */
    private function tutupSaldoMinus(DepositService $deposit, $suppliers): void
    {
        $dibiarkanMinus = $suppliers->sortBy('name')->take(2)->pluck('id')->all();

        foreach ($suppliers as $s) {
            $saldo = $s->depositBalance();
            if ($saldo >= 0 || in_array($s->id, $dibiarkanMinus, true)) {
                continue;
            }

            // Ditransfer melebihi kekurangannya, seperti kebiasaan owner: sekalian
            // menyiapkan saldo untuk pembelian berikutnya.
            $jumlah = (int) (ceil((abs($saldo) + mt_rand(5, 25) * 1_000_000) / 500_000) * 500_000);

            $deposit->topup($s, [
                'amount' => $jumlah,
                'date'   => Carbon::now()->subDays(mt_rand(0, 5))->toDateString(),
                'notes'  => 'Pelunasan + setoran untuk pembelian berikutnya',
            ]);
        }
    }

    /** Sebagian nota agen dibayar dicicil, supaya kartu piutang ada isinya. */
    private function buatPembayaranAgen(): int
    {
        $dibuat = 0;

        foreach (StockOut::where('payment_status', 'unpaid')->get() as $out) {
            if (mt_rand(0, 1) === 0) {
                continue;
            }

            $sisa = $out->remaining();
            if ($sisa <= 1000) {
                continue;
            }

            $bayar = round($sisa * (mt_rand(30, 70) / 100), 2);

            AgentPayment::create([
                'agent_id'     => $out->agent_id,
                'stock_out_id' => $out->id,
                'date'         => $out->date->copy()->addDays(mt_rand(1, 10))->toDateString(),
                'amount'       => $bayar,
                'method'       => ['cash', 'transfer'][mt_rand(0, 1)],
                'user_id'      => Auth::id(),
                'notes'        => 'Cicilan pertama',
            ]);

            $out->update(['paid_amount' => round((float) $out->paid_amount + $bayar, 2)]);
            $dibuat++;
        }

        return $dibuat;
    }
}

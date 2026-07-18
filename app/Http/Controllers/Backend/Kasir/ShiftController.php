<?php

namespace App\Http\Controllers\Backend\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DailySalesTarget; // Model Target Penjualan
use App\Models\Expense;          // Pengeluaran (rekonsiliasi tutup shift)

class ShiftController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Hak akses:
        // - kasir       : buka/tutup shift-nya sendiri ('shift.operate').
        // - owner/admin : LIHAT-SAJA seluruh shift toko + boleh "Buka Kembali" (reopen)
        //                 shift kasir yang tak sengaja ditutup ('shift.reopen'). TIDAK buka/tutup.
        // - Superadmin  : semua (via Gate::before), melihat lintas tenant.
        $canOperate = $user->can('shift.operate');
        $canReopen  = $user->can('shift.reopen');
        $ownOnly    = $user->hasRole('kasir') && ! $user->hasRole('Superadmin');

        $currentShift  = null;
        $cashSales     = 0;
        $qrisSales     = 0;
        $shiftExpenses = 0;

        // Panel operasional (kartu shift berjalan + form tutup) hanya untuk operator (kasir).
        if ($canOperate) {
            $currentShift = Shift::where('user_id', $user->id)->where('status', 'open')->first();

            if ($currentShift) {
                // Pendapatan TUNAI (masuk laci) & QRIS (info saja, tidak masuk laci) sejak shift dibuka.
                $cashSales = Order::where('payment_method', 'cash')
                    ->where('payment_status', 'paid')
                    ->where('shift_id', $currentShift->id)
                    ->whereNull('voided_at') // pesanan salah = refund penuh, tak masuk kas
                    ->sum('grand_total');

                $qrisSales = Order::where('payment_method', 'qris')
                    ->where('payment_status', 'paid')
                    ->where('shift_id', $currentShift->id)
                    ->whereNull('voided_at') // pesanan salah tak dihitung
                    ->sum('grand_total');

                // Pengeluaran laci: yang dibebankan ke shift ini (shift_id). Entri yang
                // shift_id-nya dikosongkan (mis. backdated/susulan) tak mengurangi laci.
                $shiftExpenses = Expense::where('shift_id', $currentShift->id)->sum('amount');
            }
        }

        // Riwayat: kasir -> shift miliknya saja; owner/admin/superadmin -> SEMUA shift toko.
        $history = Shift::with('user')
            ->where('status', 'closed')
            ->when($ownOnly, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get();

        // Untuk peninjau (owner/admin): daftar shift yang SEDANG berjalan (memantau siapa yang buka).
        $openShiftsAll = collect();
        if (! $canOperate) {
            $openShiftsAll = Shift::with('user')->where('status', 'open')->orderBy('start_time')->get();
        }

        // Setup harian: minta TARGET penjualan bila hari ini belum punya target (shift pertama).
        $today = Carbon::today();
        $needTarget = ! DailySalesTarget::whereDate('date', $today)->exists();

        return view('backend.kasir.shift.index', compact(
            'currentShift', 'cashSales', 'qrisSales', 'shiftExpenses', 'history',
            'canOperate', 'canReopen', 'ownOnly', 'openShiftsAll', 'needTarget'
        ));
    }

    public function openShift(Request $request)
    {
        $today = Carbon::today();
        // Minta TARGET penjualan bila hari ini belum ada target (shift pertama hari itu).
        $needTarget = !DailySalesTarget::whereDate('date', $today)->exists();

        // 1. Validasi Dinamis
        $rules = ['starting_cash' => 'required|numeric|min:0'];
        if ($needTarget) {
            $rules['target_penjualan'] = 'required|numeric|min:0';
        }

        // Bersihkan pemisah ribuan pada input uang (mis. "300.000" -> "300000") sebelum validasi.
        foreach (['starting_cash', 'target_penjualan'] as $mf) {
            if ($request->has($mf)) {
                $request->merge([$mf => preg_replace('/\D/', '', (string) $request->input($mf))]);
            }
        }
        $request->validate($rules);

        // 2. Cegah buka shift ganda
        $activeShift = Shift::where('user_id', Auth::id())->where('status', 'open')->first();
        if ($activeShift) {
            return redirect()->back()->with('error', 'Anda masih memiliki shift yang aktif!');
        }

        // 2b. Anti-curang (khususnya plan deposit): jangan izinkan buka shift baru
        //     bila masih ada pesanan menggantung (belum selesai / belum dibayar) dari
        //     sesi sebelumnya. Wajib diselesaikan dulu agar biaya transaksi tidak dihindari.
        $pendingOrders = Order::where(function ($query) {
            $query->whereIn('order_status', ['pending', 'cooking', 'served'])
                ->orWhere('payment_status', 'unpaid');
        })->count();
        if ($pendingOrders > 0) {
            return redirect()->back()->with('error', 'Tidak bisa membuka shift baru! Masih ada ' . $pendingOrders . ' pesanan yang belum diselesaikan atau belum dibayar. Harap selesaikan semua pesanan di menu Kasir terlebih dahulu.');
        }

        DB::beginTransaction();
        try {
            // 3. Simpan target penjualan harian bila hari ini belum punya (shift pertama).
            //    Shift ke-2+ pada hari yang sama: target sudah ada -> dilewati.
            if ($needTarget) {
                DailySalesTarget::create([
                    'date'   => $today,
                    'amount' => $request->target_penjualan,
                ]);
            }

            // 4. Buka Shift untuk Kasir tersebut. Modal laci = kembalian + kas pengeluaran (menyatu).
            Shift::create([
                'user_id'       => Auth::id(),
                'start_time'    => now(),
                'starting_cash' => $request->starting_cash,
                'status'        => 'open'
            ]);

            DB::commit();
            return redirect()->route('kasir.index')->with('success', 'Shift berhasil dibuka! Selamat bekerja.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal membuka shift: ' . $e->getMessage());
        }
    }

    public function closeShift(Request $request, $id)
    {
        // Bersihkan pemisah ribuan (mis. "400.000" -> "400000") sebelum validasi,
        // agar tidak salah dibaca sebagai desimal (400.000 -> 400).
        $request->merge(['actual_cash' => preg_replace('/\D/', '', (string) $request->input('actual_cash'))]);

        $request->validate([
            'actual_cash' => 'required|numeric|min:0'
        ]);

        try {
            // 1. Ambil data shift terlebih dahulu untuk mendapatkan waktu mulai (start_time)
            $shift = Shift::where('user_id', Auth::id())->where('status', 'open')->findOrFail($id);

            // 2. Cegat order menggantung KHUSUS shift ini (where(function) agar orWhere tak menabrak filter waktu).
            $pendingOrders = Order::where('shift_id', $shift->id)
                ->where(function ($query) {
                    $query->whereIn('order_status', ['pending', 'cooking', 'served'])
                        ->orWhere('payment_status', 'unpaid');
                })
                ->count();

            if ($pendingOrders > 0) {
                return redirect()->back()->with('error', 'Akses Ditolak! Masih ada ' . $pendingOrders . ' pesanan yang belum dibayar atau meja yang belum dikosongkan. Harap selesaikan semua meja di menu Kasir terlebih dahulu.');
            }

            DB::beginTransaction();

            // Penjualan TUNAI (masuk laci) selama shift ini.
            $cashSales = Order::where('payment_method', 'cash')
                ->where('payment_status', 'paid')
                ->where('shift_id', $shift->id)
                ->whereNull('voided_at') // pesanan salah = refund penuh, tak masuk kas laci
                ->sum('grand_total');

            // Pengeluaran laci shift ini (by shift_id). Entri yang shift_id-nya dikosongkan
            // (mis. backdated/susulan) tak mengurangi laci -> hanya masuk laporan by date.
            $shiftExpenses = Expense::where('shift_id', $shift->id)->sum('amount');

            // Uang fisik SEHARUSNYA di laci = Modal Laci + Penjualan TUNAI - Pengeluaran.
            // (QRIS tidak masuk laci; konsep anggaran terpisah sudah dihapus -> modal laci menyatu.)
            $expectedCash = $shift->starting_cash + $cashSales - $shiftExpenses;
            $actualCash   = $request->actual_cash;
            $difference   = $actualCash - $expectedCash;

            // Tutup Shift (simpan rincian pengeluaran utk info tutup + sales report).
            $shift->update([
                'end_time'      => Carbon::now(),
                'cash_sales'    => $cashSales,
                'expense_total' => $shiftExpenses,
                'expected_cash' => $expectedCash,
                'actual_cash'   => $actualCash,
                'difference'    => $difference,
                'status'        => 'closed'
            ]);

            DB::commit();
            return redirect()->route('shifts.index')->with('success', 'Shift berhasil ditutup. Laporan kasir telah disimpan.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    /**
     * Buka kembali (undo) shift/kas yang ditutup HARI INI — untuk mengatasi
     * penutupan tak sengaja. Status kembali 'open', data penutupan direset;
     * selisih dihitung ulang saat ditutup lagi.
     */
    public function reopenShift($id)
    {
        // Hanya owner/admin (punya 'shift.reopen') — untuk mengoreksi shift kasir yang
        // tak sengaja ditutup. Route juga dijaga middleware can:shift.reopen.
        // Tenant-scoped otomatis (owner/admin: tenant sendiri; Superadmin: lintas tenant).
        $shift = Shift::where('status', 'closed')->findOrFail($id);

        // Hanya boleh membuka kembali yang ditutup HARI INI (jangan ubah histori lama).
        if (!$shift->end_time || !Carbon::parse($shift->end_time)->isToday()) {
            return redirect()->back()->with('error', 'Hanya kas/shift yang ditutup hari ini yang bisa dibuka kembali.');
        }

        // Kasir pemilik shift tidak boleh sedang punya shift lain yang terbuka.
        if (Shift::where('user_id', $shift->user_id)->where('status', 'open')->exists()) {
            return redirect()->back()->with('error', 'Kasir pemilik shift ini masih punya shift lain yang terbuka. Tutup dulu shift itu.');
        }

        $shift->update([
            'status'        => 'open',
            'end_time'      => null,
            // cash_sales kolom NOT NULL (default 0). Pakai 0 (bukan null) agar identik
            // dengan shift yang baru dibuka & tidak melanggar constraint. Nilai sebenarnya
            // dihitung ulang dari Order saat shift ditutup kembali.
            'cash_sales'    => 0,
            'expense_total' => null,
            'expected_cash' => null,
            'actual_cash'   => null,
            'difference'    => null,
        ]);

        return redirect()->route('shifts.index')->with('success', 'Kas/shift kasir dibuka kembali. Kasir dapat melanjutkan transaksi.');
    }

    /**
     * Owner/admin mengoreksi Uang Modal Laci sebuah shift yang SEDANG berjalan.
     * Berguna bila kasir salah input modal. Dijaga middleware can:shift.reopen.
     */
    public function updateModal(Request $request, $id)
    {
        // Bersihkan pemisah ribuan sebelum validasi.
        $request->merge(['starting_cash' => preg_replace('/\D/', '', (string) $request->input('starting_cash'))]);
        $request->validate(['starting_cash' => 'required|numeric|min:0']);

        // Tenant-scoped otomatis; hanya shift yang masih terbuka yang boleh dikoreksi.
        $shift = Shift::where('status', 'open')->findOrFail($id);
        $shift->update(['starting_cash' => $request->starting_cash]);

        return redirect()->route('shifts.index')->with('success', 'Uang modal laci shift diperbarui.');
    }
}

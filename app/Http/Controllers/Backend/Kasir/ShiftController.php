<?php

namespace App\Http\Controllers\Backend\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\Order; // <-- Ganti Sale jadi Order
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DailySalesTarget; // Model Target Penjualan
use App\Models\DailyBudget;      // Anggaran pengeluaran harian
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
        $shiftExpenses = 0;
        $shiftBudget   = 0;

        // Panel operasional (kartu shift berjalan + form tutup) hanya untuk operator (kasir).
        if ($canOperate) {
            $currentShift = Shift::where('user_id', $user->id)->where('status', 'open')->first();

            if ($currentShift) {
                $cashSales = Order::where('payment_method', 'cash')
                    ->where('payment_status', 'paid')
                    ->where('created_at', '>=', $currentShift->start_time)
                    ->sum('grand_total');

                $shiftExpenses = Expense::where('created_at', '>=', $currentShift->start_time)->sum('amount');

                // Anggaran pengeluaran hari itu (ikut dihitung sebagai kas fisik yang ditaruh di laci).
                $scopeDate   = Carbon::parse($currentShift->start_time)->toDateString();
                $shiftBudget = (float) (DailyBudget::whereDate('date', $scopeDate)->value('amount') ?? 0);
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

        // Setup harian dipisah: minta TARGET bila hari ini belum punya target,
        // minta ANGGARAN bila hari ini belum punya anggaran (independen satu sama lain).
        $today = Carbon::today();
        $needTarget = ! DailySalesTarget::whereDate('date', $today)->exists();
        $needBudget = ! DailyBudget::whereDate('date', $today)->exists();

        return view('backend.kasir.shift.index', compact(
            'currentShift', 'cashSales', 'shiftExpenses', 'shiftBudget', 'history',
            'canOperate', 'canReopen', 'ownOnly', 'openShiftsAll', 'needTarget', 'needBudget'
        ));
    }

    public function openShift(Request $request)
    {
        $today = Carbon::today();
        // Dipisah: minta TARGET bila hari ini belum ada target; minta ANGGARAN bila belum ada anggaran.
        // (Sebelumnya keduanya digabung "shift pertama" -> anggaran terlewat bila target diset manual.)
        $needTarget = !DailySalesTarget::whereDate('date', $today)->exists();
        $needBudget = !DailyBudget::whereDate('date', $today)->exists();

        // 1. Validasi Dinamis
        $rules = ['starting_cash' => 'required|numeric|min:0'];
        if ($needTarget) {
            $rules['target_penjualan'] = 'required|numeric|min:0';
        }
        if ($needBudget) {
            $rules['daily_budget'] = 'required|numeric|min:0'; // anggaran pengeluaran hari ini
        }

        // Bersihkan pemisah ribuan pada input uang (mis. "300.000" -> "300000") sebelum validasi.
        foreach (['starting_cash', 'target_penjualan', 'daily_budget'] as $mf) {
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
            // 3. JIKA SHIFT PERTAMA: Simpan Target Penjualan Harian
            if ($isFirstShiftOfDay) {
                DailySalesTarget::create([
                    'date'   => $today,
                    'amount' => $request->target_penjualan,
                ]);
                DailyBudget::create([
                    'date'   => $today,
                    'amount' => $request->daily_budget,
                ]);
            }

            // 4. Buka Shift untuk Kasir tersebut
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

    // public function closeShift(Request $request, $id)
    // {
    //     $request->validate([
    //         'actual_cash' => 'required|numeric|min:0'
    //     ]);

    //     try {
    //         // 🔥 LOGIKA PENCEGAT: Cek apakah masih ada orderan yang menggantung
    //         // Mencari order yang statusnya masih unpaid (Kuning) 
    //         // ATAU status dapurnya belum completed (Meja Merah yang belum dikosongkan)
    //         $pendingOrders = Order::whereIn('order_status', ['pending', 'cooking', 'served'])
    //             ->orWhere('payment_status', 'unpaid')
    //             ->count();

    //         if ($pendingOrders > 0) {
    //             // Jika masih ada, tendang kembali ke halaman shift dengan pesan error
    //             return redirect()->back()->with('error', 'Akses Ditolak! Masih ada ' . $pendingOrders . ' pesanan yang belum dibayar atau meja yang belum dikosongkan. Harap selesaikan semua meja di menu Kasir terlebih dahulu.');
    //         }

    //         DB::beginTransaction();

    //         $shift = Shift::where('user_id', Auth::id())->where('status', 'open')->findOrFail($id);

    //         // Hitung ulang dari tabel Order
    //         $cashSales = Order::where('payment_method', 'cash')
    //             ->where('payment_status', 'paid')
    //             ->where('created_at', '>=', $shift->start_time)
    //             ->sum('grand_total');

    //         // Kalkulasi
    //         $expectedCash = $shift->starting_cash + $cashSales;
    //         $actualCash   = $request->actual_cash;
    //         $difference   = $actualCash - $expectedCash;

    //         // Tutup Shift
    //         $shift->update([
    //             'end_time'      => Carbon::now(),
    //             'cash_sales'    => $cashSales,
    //             'expected_cash' => $expectedCash,
    //             'actual_cash'   => $actualCash,
    //             'difference'    => $difference,
    //             'status'        => 'closed'
    //         ]);

    //         DB::commit();
    //         return redirect()->route('shifts.index')->with('success', 'Shift berhasil ditutup. Laporan kasir telah disimpan.');
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return redirect()->back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
    //     }
    // }

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

            // 2. 🔥 PERBAIKAN LOGIKA PENCEGAT: 
            // Hanya cari orderan yang dibuat SELAMA shift ini berlangsung.
            // Gunakan where(function(...)) agar orWhere tidak menabrak filter waktu.
            $pendingOrders = Order::where('created_at', '>=', $shift->start_time)
                ->where(function ($query) {
                    $query->whereIn('order_status', ['pending', 'cooking', 'served'])
                        ->orWhere('payment_status', 'unpaid');
                })
                ->count();

            if ($pendingOrders > 0) {
                return redirect()->back()->with('error', 'Akses Ditolak! Masih ada ' . $pendingOrders . ' pesanan yang belum dibayar atau meja yang belum dikosongkan. Harap selesaikan semua meja di menu Kasir terlebih dahulu.');
            }

            DB::beginTransaction();

            // Hitung ulang dari tabel Order (hanya yang masuk di shift ini)
            $cashSales = Order::where('payment_method', 'cash')
                ->where('payment_status', 'paid')
                ->where('created_at', '>=', $shift->start_time)
                ->sum('grand_total');

            // Pengeluaran selama shift ini (uang keluar dari laci).
            $shiftExpenses = Expense::where('created_at', '>=', $shift->start_time)->sum('amount');

            // Anggaran pengeluaran hari itu = kas belanja yang ikut ditaruh fisik di laci saat buka.
            $scopeDate   = Carbon::parse($shift->start_time)->toDateString();
            $shiftBudget = (float) (DailyBudget::whereDate('date', $scopeDate)->value('amount') ?? 0);

            // Uang fisik SEHARUSNYA = Modal + Anggaran (kas belanja) + Penjualan Tunai - Pengeluaran
            $expectedCash = $shift->starting_cash + $shiftBudget + $cashSales - $shiftExpenses;
            $actualCash   = $request->actual_cash;
            $difference   = $actualCash - $expectedCash;

            // Tutup Shift (simpan rincian anggaran & pengeluaran utk info tutup + sales report)
            $shift->update([
                'end_time'      => Carbon::now(),
                'cash_sales'    => $cashSales,
                'budget_amount' => $shiftBudget,
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
            'budget_amount' => null,
            'expense_total' => null,
            'expected_cash' => null,
            'actual_cash'   => null,
            'difference'    => null,
        ]);

        return redirect()->route('shifts.index')->with('success', 'Kas/shift kasir dibuka kembali. Kasir dapat melanjutkan transaksi.');
    }
}

<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\StockIn;
use App\Models\Farm\Supplier;
use App\Models\Farm\SupplierDeposit;
use App\Services\Farm\DepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * DEPOSIT SUPPLIER — uang yang kita setor lebih dulu ke supplier.
 *
 * Alurnya: owner transfer uang -> jadi SALDO supplier -> setiap nota barang masuk
 * memotong saldo itu -> realisasi (barang ternyata kurang/lebih) mengoreksinya.
 * Menggantikan konsep "piutang supplier" yang dihapus.
 *
 * Yang boleh MENAMBAH uang ke saldo hanya owner/admin: kalau petugas gudang bisa
 * mengarang saldo, seluruh angka deposit kehilangan artinya.
 */
class DepositController extends Controller
{
    private const PENGELOLA = ['Superadmin', 'owner', 'admin'];

    public function __construct(private DepositService $deposit) {}

    public function index(Request $request)
    {
        $cari = trim((string) $request->input('q'));

        $suppliers = Supplier::query()
            ->when($cari !== '', fn ($q) => $q->where('name', 'ilike', '%' . $cari . '%'))
            ->orderBy('name')->get()
            ->map(function (Supplier $s) {
                $s->saldo   = $s->depositBalance();
                $s->ringkas = $this->deposit->summary($s->id);

                return $s;
            });

        return view('backend.farm.deposits.index', [
            'rows'       => $suppliers,
            'q'          => $cari,
            'totalSaldo' => round($suppliers->sum('saldo'), 2),
            'minus'      => $suppliers->filter(fn ($s) => $s->saldo < -0.01),
            'bolehIsi'   => $this->bolehKelola(),
        ]);
    }

    public function show(Supplier $supplier, Request $request)
    {
        $entries = SupplierDeposit::where('supplier_id', $supplier->id)
            ->with('user')
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        // Saldo berjalan dihitung dari yang paling lama, supaya kolom "saldo setelah
        // transaksi" pada riwayat benar walau halaman ditampilkan terbalik.
        $urut = SupplierDeposit::where('supplier_id', $supplier->id)
            ->orderBy('date')->orderBy('id')->get(['id', 'amount']);
        $jalan = 0.0;
        $saldoSetelah = [];
        foreach ($urut as $e) {
            $jalan = round($jalan + (float) $e->amount, 2);
            $saldoSetelah[$e->id] = $jalan;
        }

        return view('backend.farm.deposits.show', [
            'supplier'     => $supplier,
            'entries'      => $entries,
            'saldoSetelah' => $saldoSetelah,
            'saldo'        => $supplier->depositBalance(),
            'ringkas'      => $this->deposit->summary($supplier->id),
            'notaTerakhir' => StockIn::where('supplier_id', $supplier->id)
                ->orderByDesc('date')->orderByDesc('id')->limit(5)->get(),
            'bolehIsi'     => $this->bolehKelola(),
        ]);
    }

    /** Tambah saldo (setoran uang ke supplier) + bukti transfer. */
    public function topup(Request $request, Supplier $supplier)
    {
        if (! $this->bolehKelola()) {
            return back()->with('error', 'Hanya owner/admin yang boleh menambah deposit.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'date'   => ['required', 'date'],
            'notes'  => ['nullable', 'string', 'max:255'],
            'proof'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ], [], ['proof' => 'bukti transfer']);

        // Setoran yang sama persis dalam 10 menit terakhir hampir selalu tombol
        // Simpan yang tertekan dua kali, bukan dua transfer sungguhan.
        $kembar = SupplierDeposit::where('supplier_id', $supplier->id)
            ->where('type', 'topup')
            ->where('amount', round((float) $data['amount'], 2))
            ->where('date', $data['date'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if ($kembar && ! $request->boolean('confirm_duplicate')) {
            return back()->with('error', 'Setoran Rp ' . number_format((float) $data['amount'], 0, ',', '.')
                . ' untuk supplier ini sudah tercatat ' . $kembar->created_at->diffForHumans()
                . '. Bila ini memang transfer kedua, centang "Ya, ini setoran berbeda" lalu simpan lagi.');
        }

        $path = null;
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $path = $file->storeAs('farm/deposit', 'tf-' . $supplier->id . '-' . Str::random(8) . '.' . $ext, 'public');
        }

        try {
            $this->deposit->topup($supplier, $data, $path);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deposit Rp ' . number_format((float) $data['amount'], 0, ',', '.')
            . ' tercatat. Saldo sekarang Rp ' . number_format($supplier->depositBalance(), 0, ',', '.') . '.');
    }

    /** Koreksi manual saldo — wajib beralasan, hanya owner/admin. */
    public function adjust(Request $request, Supplier $supplier)
    {
        if (! $this->bolehKelola()) {
            return back()->with('error', 'Hanya owner/admin yang boleh mengoreksi saldo.');
        }

        $data = $request->validate([
            'arah'   => ['required', 'in:tambah,kurang'],
            'amount' => ['required', 'numeric', 'min:1'],
            'date'   => ['required', 'date'],
            'notes'  => ['required', 'string', 'max:255'],
        ], [], ['notes' => 'alasan koreksi']);

        $nilai = $data['arah'] === 'kurang' ? -1 * (float) $data['amount'] : (float) $data['amount'];

        try {
            $this->deposit->manualAdjust($supplier, $nilai, $data['date'], $data['notes']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Koreksi tercatat. Saldo sekarang Rp '
            . number_format($supplier->depositBalance(), 0, ',', '.') . '.');
    }

    /**
     * Batalkan satu baris buku besar. Barisnya TIDAK dihapus — dibukukan baris
     * balik, supaya angka yang pernah tampil tetap bisa ditelusuri.
     */
    public function reverse(Request $request, SupplierDeposit $deposit)
    {
        if (! $this->bolehKelola()) {
            return back()->with('error', 'Hanya owner/admin yang boleh membatalkan baris deposit.');
        }

        // Potongan pembelian & koreksi realisasi mengikuti dokumennya, tidak boleh
        // dibatalkan sendirian — kalau tidak, saldo lepas dari nota yang mendasarinya.
        if (in_array($deposit->type, ['purchase', 'realization'], true)) {
            return back()->with('error', 'Baris ini lahir dari nota/realisasi. Batalkan lewat dokumennya, bukan dari sini.');
        }

        if ($deposit->reverses_id || SupplierDeposit::where('reverses_id', $deposit->id)->exists()) {
            return back()->with('error', 'Baris ini sudah dibatalkan sebelumnya.');
        }

        SupplierDeposit::create([
            'supplier_id' => $deposit->supplier_id,
            'date'        => now()->toDateString(),
            'type'        => $deposit->type,
            'amount'      => -1 * (float) $deposit->amount,
            'reverses_id' => $deposit->id,
            'user_id'     => Auth::id(),
            'notes'       => 'Pembatalan baris #' . $deposit->id
                . ($request->filled('reason') ? ' — ' . $request->input('reason') : ''),
        ]);

        return back()->with('success', 'Baris dibatalkan dengan jurnal balik.');
    }

    /** Bukti transfer hanya dihapus bersama pembatalan — di sini cuma pengecekan hak. */
    private function bolehKelola(): bool
    {
        return Auth::user()?->hasAnyRole(self::PENGELOLA) ?? false;
    }

    /** Dipakai view untuk memutuskan bukti bisa ditampilkan sebagai gambar atau tautan. */
    public static function proofUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}

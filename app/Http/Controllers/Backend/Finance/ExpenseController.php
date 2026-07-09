<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use App\Models\DailyBudget;
use App\Models\DailySalesTarget;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ExpenseController extends Controller
{
    /** Halaman pencatatan pengeluaran + ringkasan anggaran hari ini. */
    public function index()
    {
        $today  = Carbon::today()->toDateString();
        $budget = (float) (DailyBudget::whereDate('date', $today)->value('amount') ?? 0);
        $spent  = (float) Expense::whereDate('date', $today)->sum('amount');

        return view('backend.finance.expenses.index', compact('budget', 'spent'));
    }

    /** Sumber DataTables server-side (ter-scope otomatis per tenant). */
    public function getDataExpenses(Request $request)
    {
        $data = Expense::with('user')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->select('expenses.*');

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('date', fn ($row) => '<span class="badge badge-light-primary fs-7">' . Carbon::parse($row->date)->translatedFormat('d M Y') . '</span>')
            ->addColumn('title', fn ($row) => '<span class="fw-bold text-gray-800">' . e($row->category) . '</span>'
                . ($row->notes ? '<br><span class="text-muted fs-7">' . e(Str::limit($row->notes, 50)) . '</span>' : ''))
            ->addColumn('amount', fn ($row) => '<span class="fw-bold text-danger">Rp ' . number_format($row->amount, 0, ',', '.') . '</span>')
            ->addColumn('user', fn ($row) => e($row->user->name ?? 'Sistem'))
            ->addColumn('action', function ($row) {
                $d = htmlspecialchars(json_encode([
                    'id'       => $row->id,
                    'date'     => Carbon::parse($row->date)->format('Y-m-d'),
                    'category' => $row->category,
                    'amount'   => (int) $row->amount,
                    'notes'    => $row->notes,
                ]), ENT_QUOTES, 'UTF-8');

                return '<div class="d-flex justify-content-end gap-2">'
                    . '<button class="btn btn-sm btn-icon btn-light-primary btn-edit-expense" data-row="' . $d . '"><i class="ki-outline ki-pencil fs-4"></i></button>'
                    . '<button class="btn btn-sm btn-icon btn-light-danger btn-del-expense" data-id="' . $row->id . '"><i class="ki-outline ki-trash fs-4"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['date', 'title', 'amount', 'action'])
            ->make(true);
    }

    /** Catat pengeluaran baru. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'     => 'required|date',
            'category' => 'required|string|max:255',
            'amount'   => 'required|numeric|min:0',
            'notes'    => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();
            Expense::create([
                'date'     => $data['date'],
                'category' => $data['category'],
                'amount'   => $data['amount'],
                'notes'    => $data['notes'] ?? null,
                'user_id'  => Auth::id(),
            ]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pengeluaran berhasil dicatat!']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /** Ubah pengeluaran (ter-scope per tenant via findOrFail). */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'date'     => 'required|date',
            'category' => 'required|string|max:255',
            'amount'   => 'required|numeric|min:0',
            'notes'    => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();
            $expense = Expense::findOrFail($id);
            $expense->update([
                'date'     => $data['date'],
                'category' => $data['category'],
                'amount'   => $data['amount'],
                'notes'    => $data['notes'] ?? null,
            ]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pengeluaran berhasil diubah!']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal mengubah: ' . $e->getMessage()], 500);
        }
    }

    /** Hapus pengeluaran. */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            Expense::findOrFail($id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pengeluaran berhasil dihapus.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Backend\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateSetting;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

/**
 * Modul AFFILIATE (admin) — kelola afiliator, referral, & payout. Khusus Superadmin
 * (can:affiliate.manage). Komisi ONE-TIME (config/affiliate.php).
 */
class AdminController extends Controller
{
    public function index()
    {
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);
        $stats = [
            'total'      => Affiliate::count(),
            'pending'    => Affiliate::where('status', 'pending')->count(),
            'active'     => Affiliate::where('status', 'active')->count(),
            'referrals'  => Referral::count(),
            'unpaid'     => (float) Referral::where('commission_status', '!=', 'paid')->sum('commission_amount'),
            'paid'       => (float) Referral::where('commission_status', 'paid')->sum('commission_amount'),
        ];
        return view('backend.affiliate.index', compact('tenants', 'stats'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Affiliate::with('tenant')->withCount('referrals')->orderByDesc('created_at')->select('affiliates.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('affiliate', fn ($r) => '<div><span class="fw-bold text-gray-800 d-block">' . e($r->name) . '</span>'
                    . '<span class="text-muted fs-8">' . e($r->email ?: '-') . ($r->phone ? ' · ' . e($r->phone) : '') . '</span></div>')
                ->addColumn('code', fn ($r) => '<span class="badge badge-light-primary fs-7">' . e($r->code) . '</span>')
                ->addColumn('type', fn ($r) => $r->type === 'tenant'
                    ? '<span class="badge badge-light-info">Tenant: ' . e(optional($r->tenant)->name ?? '-') . '</span>'
                    : '<span class="badge badge-light-dark">Eksternal</span>')
                ->addColumn('status', function ($r) {
                    $map = ['pending' => 'warning', 'active' => 'success', 'suspended' => 'danger'];
                    $c = $map[$r->status] ?? 'secondary';
                    return '<span class="badge badge-light-' . $c . ' text-capitalize">' . e($r->status) . '</span>';
                })
                ->addColumn('referrals', fn ($r) => '<span class="fw-bold">' . $r->referrals_count . '</span> tenant')
                ->addColumn('action', function ($r) {
                    $b = '<div class="d-flex justify-content-end gap-1">';
                    $b .= '<button class="btn btn-sm btn-icon btn-light-primary btn-referrals" data-id="' . $r->id . '" data-name="' . e($r->name) . '" title="Lihat referral"><i class="ki-outline ki-eye fs-4"></i></button>';
                    if ($r->status !== 'active') {
                        $b .= '<button class="btn btn-sm btn-icon btn-light-success btn-status" data-id="' . $r->id . '" data-to="active" title="Aktifkan"><i class="ki-outline ki-check fs-4"></i></button>';
                    }
                    if ($r->status !== 'suspended') {
                        $b .= '<button class="btn btn-sm btn-icon btn-light-warning btn-status" data-id="' . $r->id . '" data-to="suspended" title="Tangguhkan"><i class="ki-outline ki-lock fs-4"></i></button>';
                    }
                    $b .= '<button class="btn btn-sm btn-icon btn-light-danger btn-del-aff" data-id="' . $r->id . '" data-name="' . e($r->name) . '" title="Hapus"><i class="ki-outline ki-trash fs-4"></i></button>';
                    return $b . '</div>';
                })
                ->rawColumns(['affiliate', 'code', 'type', 'status', 'referrals', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|max:255',
            'phone'     => 'nullable|string|max:30',
            'type'      => 'required|in:external,tenant',
            'tenant_id' => 'required_if:type,tenant|nullable|exists:tenants,id',
        ], [
            'name.required'       => 'Nama afiliator wajib diisi.',
            'tenant_id.required_if' => 'Pilih tenant untuk afiliator tipe tenant.',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();
            $aff = Affiliate::create([
                'code'      => Affiliate::generateCode($request->name),
                'name'      => $request->name,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'type'      => $request->type,
                'tenant_id' => $request->type === 'tenant' ? $request->tenant_id : null,
                'user_id'   => $request->type === 'tenant' ? optional(Tenant::find($request->tenant_id))->owner_id : null,
                'status'    => 'active', // dibuat manual oleh admin -> langsung aktif
            ]);
            activity()->useLog('affiliate')->causedBy(Auth::user())->performedOn($aff)
                ->log('Menambah afiliator: ' . $aff->name . ' (' . $aff->code . ')');
            DB::commit();
            return response()->json(['success' => 'Afiliator ditambahkan. Kode: ' . $aff->code, 'judul' => 'Berhasil'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $aff = Affiliate::findOrFail($id);
            $to = $request->input('to');
            abort_unless(in_array($to, ['active', 'suspended', 'pending'], true), 422);
            $aff->update(['status' => $to]);
            activity()->useLog('affiliate')->causedBy(Auth::user())->performedOn($aff)
                ->log('Ubah status afiliator ' . $aff->code . ' -> ' . $to);
            return response()->json(['success' => 'Status afiliator: ' . $to, 'judul' => 'Berhasil']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    /** Daftar referral (tenant yang pakai kode) utk modal. */
    public function referrals($id)
    {
        $aff = Affiliate::with(['referrals' => fn ($q) => $q->orderByDesc('created_at'), 'referrals.tenant'])->findOrFail($id);
        $rows = $aff->referrals->map(fn ($r) => [
            'id'                => $r->id,
            'tenant'            => optional($r->tenant)->name ?? $r->tenant_name ?? '(tenant dihapus)',
            'date'              => optional($r->created_at)->locale('id')->translatedFormat('d M Y'),
            'status'            => $r->status,
            'commission'        => (float) $r->commission_amount,
            'commission_status' => $r->commission_status,
        ]);
        return response()->json([
            'affiliate' => ['name' => $aff->name, 'code' => $aff->code, 'url' => $aff->referralUrl()],
            'referrals' => $rows,
        ]);
    }

    /** Cairkan komisi 1 referral (one-time): set nominal dari config lalu tandai paid. */
    public function payReferral(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $ref = Referral::with('tenant')->findOrFail($id);

            if ($ref->commission_amount <= 0) {
                $ref->commission_amount = $this->commissionFor($ref);
            }
            $ref->status            = 'subscribed';
            $ref->subscribed_at     = $ref->subscribed_at ?? now();
            $ref->commission_status = 'paid';
            $ref->paid_at           = now();
            $ref->save();

            activity()->useLog('affiliate')->causedBy(Auth::user())->performedOn($ref)
                ->log('Cairkan komisi referral #' . $ref->id . ' Rp' . $ref->commission_amount);
            DB::commit();
            return response()->json(['success' => 'Komisi ditandai lunas (Rp ' . number_format($ref->commission_amount, 0, ',', '.') . ').', 'judul' => 'Berhasil']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $aff = Affiliate::findOrFail($id);
            $name = $aff->name;
            $aff->delete(); // referrals ikut terhapus (cascade)
            activity()->useLog('affiliate')->causedBy(Auth::user())->log('Hapus afiliator: ' . $name);
            return response()->json(['success' => 'Afiliator dihapus.', 'judul' => 'Berhasil']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    /** Hitung komisi one-time dari config (flat rupiah / persen dari langganan pertama). */
    private function commissionFor(Referral $ref): float
    {
        $type  = config('affiliate.commission_type', 'flat');
        $value = (float) config('affiliate.commission_value', 50000);
        if ($type === 'percent' && $ref->tenant_id) {
            $sub = Subscription::where('tenant_id', $ref->tenant_id)->where('status', 'paid')->orderBy('paid_at')->first();
            return $sub ? round((float) $sub->amount * $value / 100, 2) : 0;
        }
        return $value; // flat
    }

    /* ===================== SETELAN PROGRAM (komisi & cashback) ===================== */

    public function settings()
    {
        $setting = AffiliateSetting::current();
        return view('backend.affiliate.settings', compact('setting'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'commission_type'  => ['required', 'in:flat,percent'],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'cashback_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        AffiliateSetting::current()->update($data);
        return back()->with('success', 'Setelan program affiliate berhasil disimpan.');
    }

    /* ===================== PENCAIRAN (withdraw) ===================== */

    public function withdrawals()
    {
        $withdrawals = Withdrawal::with('affiliate')->latest()->paginate(30);
        return view('backend.affiliate.withdrawals', compact('withdrawals'));
    }

    /** Tandai pencairan selesai (dicairkan): komisi terkait -> 'paid'. */
    public function withdrawalDone($id)
    {
        $wd = Withdrawal::findOrFail($id);
        if ($wd->status !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }
        DB::transaction(function () use ($wd) {
            $wd->referrals()->update(['commission_status' => 'paid', 'paid_at' => now()]);
            $wd->update(['status' => 'done', 'done_at' => now()]);
        });
        return back()->with('success', 'Pencairan ' . $wd->code . ' ditandai SELESAI (dicairkan).');
    }

    /** Tolak pencairan: komisi dikembalikan ke 'pending' (bisa diajukan lagi). */
    public function withdrawalReject($id)
    {
        $wd = Withdrawal::findOrFail($id);
        if ($wd->status !== 'pending') {
            return back()->with('error', 'Pengajuan sudah diproses sebelumnya.');
        }
        DB::transaction(function () use ($wd) {
            $wd->referrals()->update(['commission_status' => 'pending', 'withdrawal_id' => null]);
            $wd->update(['status' => 'rejected', 'done_at' => now()]);
        });
        return back()->with('success', 'Pencairan ' . $wd->code . ' ditolak; komisi dikembalikan ke saldo affiliate.');
    }
}

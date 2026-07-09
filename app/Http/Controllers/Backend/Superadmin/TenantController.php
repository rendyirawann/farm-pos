<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TenantController extends Controller
{
    public function index()
    {
        $stats = [
            'total'    => Tenant::count(),
            'active'   => Tenant::where('subscription_status', 'active')->count(),
            'inactive' => Tenant::whereIn('subscription_status', ['inactive', 'expired'])->count(),
            'users'    => User::whereNotNull('tenant_id')->count(),
        ];

        return view('backend.superadmin.tenants.index', compact('stats'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $query = Tenant::withCount('users')->orderByDesc('created_at');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('business', function ($row) {
                    return '<div class="fw-bold text-gray-800">' . e($row->name) . '</div>'
                        . '<div class="fs-8 text-muted">' . e($row->business_type ?? '-') . ' · ' . e($row->email ?? '-') . '</div>';
                })
                ->addColumn('plan', function ($row) {
                    return $row->plan ? (Plan::name($row->plan)) : '<span class="text-muted">—</span>';
                })
                ->addColumn('status', function ($row) {
                    $map = [
                        'active'   => ['Aktif', 'success'],
                        'trial'    => ['Trial', 'info'],
                        'expired'  => ['Kedaluwarsa', 'danger'],
                        'inactive' => ['Belum Aktif', 'warning'],
                    ];
                    [$label, $color] = $map[$row->subscription_status] ?? ['-', 'secondary'];
                    $suspended = $row->is_active ? '' : ' <span class="badge badge-light-danger">Suspended</span>';
                    return '<span class="badge badge-light-' . $color . '">' . $label . '</span>' . $suspended;
                })
                ->addColumn('ends_at', function ($row) {
                    return $row->subscription_ends_at ? $row->subscription_ends_at->translatedFormat('d M Y') : '—';
                })
                ->addColumn('users_count', fn($row) => $row->users_count)
                ->addColumn('action', function ($row) {
                    $toggleLabel = $row->is_active ? 'Suspend' : 'Aktifkan';
                    $toggleColor = $row->is_active ? 'warning' : 'success';
                    $html = '<div class="d-flex gap-2">'
                        . '<a href="' . route('tenants.show', $row->id) . '" class="btn btn-sm btn-light-primary">Detail</a>'
                        . '<button class="btn btn-sm btn-light-' . $toggleColor . ' btn-toggle-active" data-id="' . $row->id . '">' . $toggleLabel . '</button>';
                    // Hapus hanya untuk tenant yang di-suspend (nonaktif).
                    if (! $row->is_active) {
                        $html .= '<button class="btn btn-sm btn-light-danger btn-delete-tenant" data-id="' . $row->id . '" data-name="' . e($row->name) . '">Hapus</button>';
                    }
                    return $html . '</div>';
                })
                ->rawColumns(['business', 'plan', 'status', 'action'])
                ->make(true);
        }
    }

    public function show($id)
    {
        $tenant = Tenant::withCount('users')->findOrFail($id);
        $users = User::where('tenant_id', $tenant->id)->with('roles')->get();
        $subscriptions = $tenant->subscriptions()->orderByDesc('created_at')->limit(30)->get();
        $plans = Plan::all();

        return view('backend.superadmin.tenants.show', compact('tenant', 'users', 'subscriptions', 'plans'));
    }

    public function toggleActive(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);

        if (function_exists('activity')) {
            activity()->useLog('tenant')->causedBy(Auth::user())
                ->withProperties(['tenant_id' => $tenant->id, 'is_active' => $tenant->is_active])
                ->log(($tenant->is_active ? 'Mengaktifkan' : 'Men-suspend') . ' tenant: ' . $tenant->name);
        }

        return response()->json(['success' => true, 'is_active' => $tenant->is_active]);
    }

    /**
     * Override manual oleh Superadmin (mis. aktivasi tanpa Midtrans / koreksi data).
     */
    public function updateSubscription(Request $request, $id)
    {
        $request->validate([
            'plan'                => ['nullable', 'in:' . implode(',', array_keys(Plan::all()))],
            'subscription_status' => ['required', 'in:trial,active,expired,inactive'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $tenant = Tenant::findOrFail($id);
        $tenant->update([
            'plan'                 => $request->plan,
            'subscription_status'  => $request->subscription_status,
            'subscription_ends_at' => $request->subscription_ends_at,
        ]);

        return back()->with('success', 'Langganan tenant diperbarui.');
    }

    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);

        // Guard: hanya tenant yang di-suspend (nonaktif) yang boleh dihapus.
        if ($tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant yang masih aktif tidak bisa dihapus. Suspend dulu tenant ini sebelum menghapusnya.',
            ], 422);
        }

        $name = $tenant->name;

        DB::transaction(function () use ($tenant) {
            // Hapus user-user tenant lalu tenant-nya (data lain ikut via FK cascade/nullOnDelete)
            User::where('tenant_id', $tenant->id)->delete();
            $tenant->delete();
        });

        if (function_exists('activity')) {
            activity()->useLog('tenant')->causedBy(Auth::user())
                ->log('Menghapus tenant: ' . $name);
        }

        return response()->json(['success' => true]);
    }
}

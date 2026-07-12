<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use App\Tenancy\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class TenantController extends Controller
{
    public function index()
    {
        $stats = [
            'total'    => Tenant::count(),
            'active'   => Tenant::where('subscription_status', 'active')->count(),
            'inactive' => Tenant::whereIn('subscription_status', ['inactive', 'expired'])->count(),
            'users'    => User::withoutGlobalScopes()->whereNotNull('tenant_id')->count(),
        ];

        return view('backend.superadmin.tenants.index', compact('stats'));
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            // Superadmin lintas-tenant: hitung user tanpa TenantScope (kalau superadmin sedang
            // mode POS di satu toko, scope User bisa salah -> jumlah user keliru).
            $query = Tenant::withCount(['users' => fn($q) => $q->withoutGlobalScopes()])->orderByDesc('created_at');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('business', function ($row) {
                    $cat = $row->category ? ' · <span class="badge badge-light-dark">' . strtoupper($row->category) . '</span>' : '';
                    $src = $row->created_via === 'manual'
                        ? ' <span class="badge badge-light-primary">Manual Superadmin</span>'
                        : ($row->created_via === 'midtrans' ? ' <span class="badge badge-light-info">Midtrans</span>' : '');
                    return '<div class="fw-bold text-gray-800">' . e($row->name) . $src . '</div>'
                        . '<div class="fs-8 text-muted">' . e($row->business_type ?? '-') . $cat . ' · ' . e($row->email ?? '-') . '</div>';
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
                    $html = '<div class="d-flex gap-2 flex-wrap">'
                        . '<a href="' . route('tenants.show', $row->id) . '" class="btn btn-sm btn-light-primary">Detail</a>'
                        . '<a href="' . route('tenants.edit', $row->id) . '" class="btn btn-sm btn-light-info">Edit</a>'
                        . '<button class="btn btn-sm btn-light-' . $toggleColor . ' btn-toggle-active" data-id="' . $row->id . '">' . $toggleLabel . '</button>';
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
        $tenant = Tenant::withCount(['users' => fn($q) => $q->withoutGlobalScopes()])->findOrFail($id);
        // withoutGlobalScopes: superadmin melihat user tenant mana pun (tak kena TenantScope).
        $users = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->with('roles')->get();
        $subscriptions = $tenant->subscriptions()->orderByDesc('created_at')->limit(30)->get();
        $plans = Plan::all();

        return view('backend.superadmin.tenants.show', compact('tenant', 'users', 'subscriptions', 'plans'));
    }

    /** Form buat tenant baru (+ akun owner & opsional kasir). */
    public function create()
    {
        $plans = Plan::all();
        return view('backend.superadmin.tenants.create', compact('plans'));
    }

    /** Simpan tenant baru + owner + (opsional) kasir + langganan manual. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'business_type'  => ['nullable', 'string', 'max:100'],
            'category'       => ['nullable', 'in:resto,cafe,umkm'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
            // langganan
            'plan'                 => ['nullable', 'in:' . implode(',', array_keys(Plan::all()))],
            'billing_months'       => ['nullable', 'integer', 'min:0', 'max:120'],
            'extra_days'           => ['nullable', 'integer', 'min:0', 'max:365'],
            'subscription_ends_at' => ['nullable', 'date'],
            // akun owner (wajib)
            'owner_name'     => ['required', 'string', 'max:255'],
            'owner_email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:6'],
            // akun kasir (opsional)
            'kasir_name'     => ['nullable', 'string', 'max:255'],
            'kasir_email'    => ['nullable', 'email', 'max:255', 'unique:users,email', 'different:owner_email'],
            'kasir_password' => ['nullable', 'string', 'min:6'],
        ]);

        DB::transaction(function () use ($data, &$tenant) {
            $plan   = $data['plan'] ?? null;
            $months = (int) ($data['billing_months'] ?? 0);
            $days   = (int) ($data['extra_days'] ?? 0);

            // Expiry: pakai tanggal manual bila diisi; jika tidak, hitung dari bulan + hari ekstra.
            $endsAt = null;
            if (! empty($data['subscription_ends_at'])) {
                $endsAt = Carbon::parse($data['subscription_ends_at'])->endOfDay();
            } elseif ($plan && ($months > 0 || $days > 0)) {
                $endsAt = now()->addMonthsNoOverflow($months)->addDays($days);
            }
            $status = ($plan && $endsAt) ? 'active' : 'inactive';

            $tenant = Tenant::create([
                'name'                 => $data['name'],
                'slug'                 => $this->uniqueSlug($data['name']),
                'business_type'        => $data['business_type'] ?? null,
                'category'             => $data['category'] ?? null,
                'phone'                => $data['phone'] ?? null,
                'email'                => $data['email'] ?? null,
                'address'              => $data['address'] ?? null,
                'plan'                 => $plan,
                'billing_mode'         => 'monthly',
                'subscription_status'  => $status,
                'trial_ends_at'        => null,
                'subscription_ends_at' => $endsAt,
                'is_active'            => true,
                'created_via'          => 'manual',
            ]);

            // Owner (wajib)
            $owner = User::create([
                'tenant_id'         => $tenant->id,
                'name'              => $data['owner_name'],
                'email'             => $data['owner_email'],
                'username'          => $this->uniqueUsername($data['owner_email']),
                'password'          => Hash::make($data['owner_password']),
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);
            $owner->assignRole(Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']));
            $tenant->update(['owner_id' => $owner->id]);

            // Kasir (opsional)
            if (! empty($data['kasir_email'])) {
                $kasir = User::create([
                    'tenant_id'         => $tenant->id,
                    'name'              => $data['kasir_name'] ?: 'Kasir',
                    'email'             => $data['kasir_email'],
                    'username'          => $this->uniqueUsername($data['kasir_email']),
                    'password'          => Hash::make($data['kasir_password'] ?: 'kasir123'),
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]);
                $kasir->assignRole(Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']));
            }

            // Riwayat langganan manual (biar konsisten dgn alur Midtrans).
            if ($plan && $endsAt) {
                Subscription::create([
                    'tenant_id'         => $tenant->id,
                    'plan'              => $plan,
                    'amount'            => (float) (Plan::periodAmount($plan, max(1, $months)) ?? 0),
                    'billing_period'    => (string) max(1, $months),
                    'status'            => 'paid',
                    'payment_type'      => 'manual',
                    'midtrans_order_id' => 'MANUAL-' . strtoupper(Str::random(10)),
                    'starts_at'         => now(),
                    'ends_at'           => $endsAt,
                    'paid_at'           => now(),
                ]);
            }
        });

        if (function_exists('activity')) {
            activity()->useLog('tenant')->causedBy(Auth::user())->log('Membuat tenant manual: ' . $data['name']);
        }

        return redirect()->route('tenants.show', $tenant->id)->with('success', 'Tenant "' . $data['name'] . '" berhasil dibuat.');
    }

    /** Form edit profil tenant. */
    public function edit($id)
    {
        $tenant = Tenant::findOrFail($id);
        return view('backend.superadmin.tenants.edit', compact('tenant'));
    }

    /** Update profil tenant (bukan langganan). */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'category'      => ['nullable', 'in:resto,cafe,umkm'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:255'],
            'address'       => ['nullable', 'string', 'max:500'],
        ]);
        $tenant->update($data);

        return redirect()->route('tenants.show', $tenant->id)->with('success', 'Profil tenant diperbarui.');
    }

    /** Tambah akun (user + role) untuk tenant. */
    public function storeUser(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', 'in:owner,admin,kasir,kitchen'],
        ]);

        $user = User::create([
            'tenant_id'         => $tenant->id,
            'name'              => $data['name'],
            'email'             => $data['email'],
            'username'          => $this->uniqueUsername($data['email']),
            'password'          => Hash::make($data['password']),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => $data['role'], 'guard_name' => 'web']));

        if ($data['role'] === 'owner' && ! $tenant->owner_id) {
            $tenant->update(['owner_id' => $user->id]);
        }

        return redirect()->route('tenants.show', $tenant->id)->with('success', 'Akun ' . $data['role'] . ' "' . $data['name'] . '" ditambahkan.');
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
     * Set/override langganan manual oleh Superadmin. Expiry di-generate dari plan + jumlah bulan
     * (+ hari ekstra), ATAU tanggal manual bila diisi (Superadmin bisa mengeditnya).
     */
    public function updateSubscription(Request $request, $id)
    {
        $data = $request->validate([
            'plan'                 => ['nullable', 'in:' . implode(',', array_keys(Plan::all()))],
            'subscription_status'  => ['required', 'in:trial,active,expired,inactive'],
            'billing_months'       => ['nullable', 'integer', 'min:0', 'max:120'],
            'extra_days'           => ['nullable', 'integer', 'min:0', 'max:365'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $tenant = Tenant::findOrFail($id);
        $months = (int) ($data['billing_months'] ?? 0);
        $days   = (int) ($data['extra_days'] ?? 0);

        // Expiry: tanggal manual menang; jika kosong & ada bulan/hari -> hitung dari SEKARANG.
        $endsAt = $tenant->subscription_ends_at;
        if (! empty($data['subscription_ends_at'])) {
            $endsAt = Carbon::parse($data['subscription_ends_at'])->endOfDay();
        } elseif ($months > 0 || $days > 0) {
            $endsAt = now()->addMonthsNoOverflow($months)->addDays($days);
        }

        $isActive = $data['subscription_status'] === 'active';

        $tenant->update([
            'plan'                 => $data['plan'],
            'subscription_status'  => $data['subscription_status'],
            'subscription_ends_at' => $endsAt,
            'billing_mode'         => 'monthly',
            'is_active'            => $isActive ? true : $tenant->is_active,
        ]);

        // Catat riwayat langganan manual bila diaktifkan dgn plan + expiry.
        if ($isActive && $data['plan'] && $endsAt) {
            Subscription::create([
                'tenant_id'         => $tenant->id,
                'plan'              => $data['plan'],
                'amount'            => (float) (Plan::periodAmount($data['plan'], max(1, $months)) ?? 0),
                'billing_period'    => (string) max(1, $months),
                'status'            => 'paid',
                'payment_type'      => 'manual',
                'midtrans_order_id' => 'MANUAL-' . strtoupper(Str::random(10)),
                'starts_at'         => now(),
                'ends_at'           => $endsAt,
                'paid_at'           => now(),
            ]);
        }

        return back()->with('success', 'Langganan tenant diperbarui.');
    }

    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);

        if ($tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant yang masih aktif tidak bisa dihapus. Suspend dulu tenant ini sebelum menghapusnya.',
            ], 422);
        }

        $name = $tenant->name;

        DB::transaction(function () use ($tenant) {
            $tid = $tenant->id;

            // ID user tenant (tanpa TenantScope -> aman walau superadmin sedang mode POS).
            $userIds = User::withoutGlobalScopes()->where('tenant_id', $tid)->pluck('id');
            if ($userIds->isNotEmpty()) {
                DB::table('model_has_roles')->whereIn('model_id', $userIds)->delete();
                DB::table('model_has_permissions')->whereIn('model_id', $userIds)->delete();
            }

            // Hapus TUNTAS data milik tenant (query builder -> lolos TenantScope; anak seperti
            // order_details & menu_addons ikut terhapus via FK ON DELETE CASCADE). Urutan menjaga
            // FK antar tabel: menus sebelum categories; orders sebelum menus; shifts/expenses/
            // deposit_transactions sebelum users. -> tidak ada data yatim (tenant_id NULL) tersisa.
            foreach ([
                'orders', 'menus', 'categories', 'promos', 'dining_tables',
                'expenses', 'shifts', 'daily_sales_targets', 'daily_budgets',
                'deposit_transactions', 'deposit_topups', 'subscriptions', 'settings', 'users',
            ] as $table) {
                DB::table($table)->where('tenant_id', $tid)->delete();
            }

            $tenant->delete();
        });

        if (function_exists('activity')) {
            activity()->useLog('tenant')->causedBy(Auth::user())->log('Menghapus tenant (beserta seluruh datanya): ' . $name);
        }

        return response()->json(['success' => true]);
    }

    /** Slug unik dari nama bisnis. */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $i = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    /** Username unik dari bagian lokal email. */
    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_') ?: 'user';
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . (++$i);
        }
        return $username;
    }
}

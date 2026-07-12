@extends('backend.layout.app')
@section('title', 'Detail Tenant')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="fw-bold text-gray-800 mb-0">{{ $tenant->name }}</h2>
                    @if ($tenant->created_via === 'manual')
                        <span class="badge badge-light-primary">Manual Superadmin</span>
                    @elseif ($tenant->created_via === 'midtrans')
                        <span class="badge badge-light-info">Midtrans</span>
                    @endif
                    @if ($tenant->category)
                        <span class="badge badge-light-dark text-uppercase">{{ $tenant->category }}</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('tenants.edit', $tenant->id) }}" class="btn btn-light-info btn-sm">Edit Profil</a>
                    <a href="{{ route('tenants.index') }}" class="btn btn-light-primary btn-sm">← Kembali</a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-6">
                {{-- INFO + OVERRIDE --}}
                <div class="col-lg-5">
                    <div class="card card-flush mb-6">
                        <div class="card-header pt-5"><h3 class="card-title fw-bold">Informasi Bisnis</h3></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Jenis</span><span class="fw-bold">{{ $tenant->business_type ?? '-' }}</span></div>
                            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Kategori</span><span class="fw-bold text-uppercase">{{ $tenant->category ?? '-' }}</span></div>
                            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Sumber</span><span class="fw-bold">{{ $tenant->created_via === 'manual' ? 'Manual Superadmin' : ($tenant->created_via === 'midtrans' ? 'Midtrans' : '-') }}</span></div>
                            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Email</span><span class="fw-bold">{{ $tenant->email ?? '-' }}</span></div>
                            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Telepon</span><span class="fw-bold">{{ $tenant->phone ?? '-' }}</span></div>
                            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Jumlah User</span><span class="fw-bold">{{ $tenant->users_count }}</span></div>
                            <div class="d-flex justify-content-between py-2"><span class="text-muted">Suspended</span><span class="fw-bold">{{ $tenant->is_active ? 'Tidak' : 'Ya' }}</span></div>
                        </div>
                    </div>

                    <div class="card card-flush">
                        <div class="card-header pt-5"><h3 class="card-title fw-bold">Override Langganan (Manual)</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('tenants.subscription.update', $tenant->id) }}">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label">Paket</label>
                                    <select name="plan" class="form-select form-select-solid">
                                        <option value="">— Tidak ada —</option>
                                        @foreach ($plans as $key => $plan)
                                            <option value="{{ $key }}" @selected($tenant->plan === $key)>{{ $plan['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Status</label>
                                    <select name="subscription_status" class="form-select form-select-solid">
                                        @foreach (['active' => 'Aktif', 'trial' => 'Trial', 'expired' => 'Kedaluwarsa', 'inactive' => 'Belum Aktif'] as $val => $label)
                                            <option value="{{ $val }}" @selected($tenant->subscription_status === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col-6">
                                        <label class="form-label">Durasi (bulan)</label>
                                        <input type="number" name="billing_months" min="0" max="120" class="form-control form-control-solid" placeholder="mis. 12" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Hari ekstra</label>
                                        <input type="number" name="extra_days" min="0" max="365" class="form-control form-control-solid" placeholder="mis. 15" />
                                    </div>
                                </div>
                                <div class="form-text mb-4">Isi durasi → "Aktif s/d" otomatis dihitung dari <b>hari ini</b> (mis. 12 bulan + 15 hari). Atau kosongkan durasi & set tanggal manual di bawah (bisa diedit kapan saja).</div>
                                <div class="mb-4">
                                    <label class="form-label">Aktif s/d <span class="text-muted fs-8">(manual / override)</span></label>
                                    <input type="date" name="subscription_ends_at" class="form-control form-control-solid"
                                        value="{{ optional($tenant->subscription_ends_at)->format('Y-m-d') }}" />
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- USERS + SUBSCRIPTIONS --}}
                <div class="col-lg-7">
                    <div class="card card-flush mb-6">
                        <div class="card-header pt-5"><h3 class="card-title fw-bold">User Tenant</h3></div>
                        <div class="card-body">
                            <table class="table table-row-dashed align-middle gy-3">
                                <thead><tr class="fw-bold text-muted"><th>Nama</th><th>Email</th><th>Role</th><th>Aktif</th></tr></thead>
                                <tbody>
                                    @forelse ($users as $u)
                                        <tr>
                                            <td class="fw-bold">{{ $u->name }}</td>
                                            <td>{{ $u->email }}</td>
                                            <td>{{ $u->roles->pluck('name')->implode(', ') ?: '-' }}</td>
                                            <td>{!! $u->is_active ? '<span class="badge badge-light-success">Ya</span>' : '<span class="badge badge-light-danger">Tidak</span>' !!}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada user.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card card-flush mb-6">
                        <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah Akun</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('tenants.users.store', $tenant->id) }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6"><input name="name" class="form-control form-control-solid" placeholder="Nama" required></div>
                                    <div class="col-md-6"><input type="email" name="email" class="form-control form-control-solid" placeholder="Email (untuk login)" required></div>
                                    <div class="col-md-4"><input name="password" class="form-control form-control-solid" placeholder="Password (min 6)" required></div>
                                    <div class="col-md-4">
                                        <select name="role" class="form-select form-select-solid" required>
                                            <option value="owner">Owner</option>
                                            <option value="admin">Admin</option>
                                            <option value="kasir" selected>Kasir</option>
                                            <option value="kitchen">Kitchen</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Tambah Akun</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-flush">
                        <div class="card-header pt-5"><h3 class="card-title fw-bold">Riwayat Langganan</h3></div>
                        <div class="card-body">
                            <table class="table table-row-dashed align-middle gy-3">
                                <thead><tr class="fw-bold text-muted"><th>Tanggal</th><th>Paket</th><th>Jumlah</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($subscriptions as $sub)
                                        <tr>
                                            <td>{{ $sub->created_at->translatedFormat('d M Y H:i') }}</td>
                                            <td>{{ $plans[$sub->plan]['name'] ?? ucfirst($sub->plan) }}</td>
                                            <td>Rp {{ number_format($sub->amount, 0, ',', '.') }}</td>
                                            <td><span class="badge badge-light-{{ ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger'][$sub->status] ?? 'secondary' }} text-uppercase">{{ $sub->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

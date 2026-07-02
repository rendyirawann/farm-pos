@extends('backend.layout.app')
@section('title', 'Detail Tenant')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-gray-800">{{ $tenant->name }}</h2>
                <a href="{{ route('tenants.index') }}" class="btn btn-light-primary btn-sm">← Kembali</a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row g-6">
                {{-- INFO + OVERRIDE --}}
                <div class="col-lg-5">
                    <div class="card card-flush mb-6">
                        <div class="card-header pt-5"><h3 class="card-title fw-bold">Informasi Bisnis</h3></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Jenis</span><span class="fw-bold">{{ $tenant->business_type ?? '-' }}</span></div>
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
                                <div class="mb-4">
                                    <label class="form-label">Aktif s/d</label>
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

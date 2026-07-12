@extends('backend.layout.app')
@section('title', 'Edit Profil Tenant')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-gray-800">Edit Profil — {{ $tenant->name }}</h2>
                <a href="{{ route('tenants.show', $tenant->id) }}" class="btn btn-light-primary btn-sm">← Kembali</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="card card-flush">
                <div class="card-body">
                    <form method="POST" action="{{ route('tenants.update', $tenant->id) }}">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="required form-label">Nama Bisnis</label>
                                <input name="name" value="{{ old('name', $tenant->name) }}" class="form-control form-control-solid" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Usaha</label>
                                <input name="business_type" value="{{ old('business_type', $tenant->business_type) }}" class="form-control form-control-solid">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select name="category" class="form-select form-select-solid">
                                    <option value="">— Tidak ditentukan —</option>
                                    @foreach (['resto' => 'Resto', 'cafe' => 'Cafe', 'umkm' => 'UMKM'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('category', $tenant->category) === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input name="phone" value="{{ old('phone', $tenant->phone) }}" class="form-control form-control-solid">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Bisnis</label>
                                <input type="email" name="email" value="{{ old('email', $tenant->email) }}" class="form-control form-control-solid">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" rows="2" class="form-control form-control-solid">{{ old('address', $tenant->address) }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary fw-bold">Simpan Profil</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

@endsection

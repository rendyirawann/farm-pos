@extends('backend.layout.app')
@section('title', 'Supplier Bahan')
@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.fnb._nav', ['active' => 'suppliers'])
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row g-6">
      <div class="col-lg-4">
        <div class="card card-flush">
          <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5">Tambah Supplier</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('fnb.suppliers.store') }}">@csrf
              <label class="form-label fw-semibold fs-7 required">Nama Supplier</label>
              <input type="text" name="name" class="form-control form-control-solid mb-4" required>
              <label class="form-label fw-semibold fs-7">Kontak Person</label>
              <input type="text" name="contact_person" class="form-control form-control-solid mb-4">
              <label class="form-label fw-semibold fs-7">No. Telepon / WA</label>
              <input type="text" name="phone" class="form-control form-control-solid mb-4">
              <label class="form-label fw-semibold fs-7">Alamat</label>
              <textarea name="address" rows="2" class="form-control form-control-solid mb-5"></textarea>
              <button class="btn btn-primary w-100">Simpan Supplier</button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card card-flush">
          <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5">Daftar Supplier</h3></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-row-bordered align-middle gy-3 mb-0">
                <thead><tr class="fw-bold text-muted bg-light"><th class="ps-6">Supplier</th><th>Kontak</th><th>Telepon</th><th class="text-end pe-6">Aksi</th></tr></thead>
                <tbody>
                @forelse ($suppliers as $s)
                  <tr>
                    <td class="ps-6 fw-bold text-gray-800">{{ $s->name }}<div class="fs-8 text-muted">{{ $s->address }}</div></td>
                    <td class="text-muted">{{ $s->contact_person ?: '-' }}</td>
                    <td class="text-muted">{{ $s->phone ?: '-' }}</td>
                    <td class="text-end pe-6">
                      <form method="POST" action="{{ route('fnb.suppliers.destroy', $s->id) }}" class="d-inline" onsubmit="return confirm('Hapus supplier ini?')">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-light-danger py-1 px-3 fs-8">Hapus</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted py-10">Belum ada supplier.</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

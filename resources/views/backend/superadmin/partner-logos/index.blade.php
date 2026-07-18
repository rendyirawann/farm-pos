@extends('backend.layout.app')
@section('title', 'Logo Partner')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <div class="alert alert-primary fs-7">
            Logo partner/tenant berlangganan yang berjalan (marquee) di <b>landing page</b>. Saat di-hover, <b>nama tenant</b> muncul.
            Atur <b>jumlah yang ditampilkan</b>, tambah/edit/hapus di bawah. Format: <b>JPG/JPEG/PNG, maks 1MB</b>. Saat submit otomatis <b>dikompres</b> (lossless). Opsional: <b>hapus background</b> & <b>jadikan hitam putih</b> (paling optimal untuk logo berlatar polos/terang).
        </div>

        <div class="row g-6">
            {{-- KIRI: pengaturan + form tambah --}}
            <div class="col-lg-5">
                {{-- Jumlah ditampilkan --}}
                <div class="card card-flush mb-6">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Jumlah Ditampilkan di Landing</h3></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('partner-logos.limit') }}" class="d-flex gap-3 align-items-end">
                            @csrf
                            <div class="flex-grow-1">
                                <label class="form-label">Berapa logo tampil (0 = semua yang aktif)</label>
                                <input type="number" name="limit" min="0" max="100" value="{{ $limit }}" class="form-control form-control-solid">
                            </div>
                            <button class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>

                {{-- Tambah logo --}}
                <div class="card card-flush">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah Logo Partner</h3></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('partner-logos.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label required">Nama Tenant (muncul saat hover)</label>
                                <input type="text" name="name" class="form-control form-control-solid" placeholder="Kopi Nusantara" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Logo <span class="text-muted fw-normal fs-8">(JPG/JPEG/PNG, maks 1MB — auto-kompres)</span></label>
                                <input type="file" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="form-control form-control-solid" required>
                            </div>
                            <div class="d-flex flex-column gap-2 mb-4">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="remove_bg" value="1">
                                    <span class="form-check-label ms-2 fs-7">Hapus background otomatis</span>
                                </label>
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="grayscale" value="1">
                                    <span class="form-check-label ms-2 fs-7">Jadikan hitam putih <span class="text-muted fs-8">(default: warna asli)</span></span>
                                </label>
                            </div>
                            <div class="row">
                                <div class="col-8 mb-3">
                                    <label class="form-label">Link (opsional)</label>
                                    <input type="url" name="url" class="form-control form-control-solid" placeholder="https://...">
                                </div>
                                <div class="col-4 mb-3">
                                    <label class="form-label">Urutan</label>
                                    <input type="number" name="sort_order" value="0" min="0" class="form-control form-control-solid">
                                </div>
                            </div>
                            <button class="btn btn-primary w-100">Tambah Logo</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- KANAN: daftar logo --}}
            <div class="col-lg-7">
                <div class="card card-flush">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Daftar Logo ({{ $logos->count() }})</h3></div>
                    <div class="card-body">
                        <div class="row g-4">
                            @forelse ($logos as $logo)
                                <div class="col-6 col-md-4">
                                    <div class="card card-bordered h-100 {{ $logo->is_active ? '' : 'opacity-50' }}">
                                        <div class="d-flex align-items-center justify-content-center bg-light rounded-top" style="height:90px">
                                            <img src="{{ $logo->image_url }}" alt="{{ $logo->name }}" style="max-height:70px;max-width:90%;object-fit:contain">
                                        </div>
                                        <div class="p-2">
                                            <div class="fw-bold fs-8 text-truncate" title="{{ $logo->name }}">{{ $logo->name }}</div>
                                            <div class="text-muted fs-9 mb-2">urutan {{ $logo->sort_order }} · {{ $logo->is_active ? 'aktif' : 'nonaktif' }}</div>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-icon btn-light-primary btn-edit-logo flex-fill"
                                                    data-id="{{ $logo->id }}" data-name="{{ $logo->name }}" data-url="{{ $logo->url }}"
                                                    data-sort="{{ $logo->sort_order }}" data-active="{{ $logo->is_active ? 1 : 0 }}" title="Edit"><i class="ki-outline ki-pencil fs-6"></i></button>
                                                <form method="POST" action="{{ route('partner-logos.toggle', $logo) }}" class="flex-fill">@csrf
                                                    <button class="btn btn-sm btn-icon btn-light-{{ $logo->is_active ? 'success' : 'secondary' }} w-100" title="Aktif/Nonaktif"><i class="ki-outline ki-{{ $logo->is_active ? 'eye' : 'eye-slash' }} fs-6"></i></button>
                                                </form>
                                                <form method="POST" action="{{ route('partner-logos.destroy', $logo) }}" class="flex-fill" onsubmit="return confirm('Hapus logo {{ $logo->name }}?')">@csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-icon btn-light-danger w-100" title="Hapus"><i class="ki-outline ki-trash fs-6"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-8">Belum ada logo. Tambahkan di sebelah kiri.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editLogoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editLogoForm" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header"><h3 class="modal-title">Edit Logo Partner</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Nama Tenant</label><input type="text" name="name" id="el-name" class="form-control form-control-solid" required></div>
                    <div class="mb-3"><label class="form-label">Ganti Logo <span class="text-muted fw-normal fs-8">(JPG/JPEG/PNG, maks 1MB — kosongkan bila tak diubah)</span></label><input type="file" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="form-control form-control-solid"></div>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="remove_bg" value="1"><span class="form-check-label ms-2 fs-7">Hapus background otomatis <span class="text-muted fs-8">(saat ganti logo)</span></span></label>
                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="grayscale" value="1"><span class="form-check-label ms-2 fs-7">Jadikan hitam putih</span></label>
                    </div>
                    <div class="row">
                        <div class="col-8 mb-3"><label class="form-label">Link (opsional)</label><input type="url" name="url" id="el-url" class="form-control form-control-solid"></div>
                        <div class="col-4 mb-3"><label class="form-label">Urutan</label><input type="number" name="sort_order" id="el-sort" min="0" class="form-control form-control-solid"></div>
                    </div>
                    <div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" id="el-active" class="form-check-input"><label class="form-check-label ms-2" for="el-active">Aktif</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.btn-edit-logo').forEach(function (b) {
        b.addEventListener('click', function () {
            const d = b.dataset;
            document.getElementById('editLogoForm').action = "{{ url('admin/partner-logos') }}/" + d.id;
            document.getElementById('el-name').value = d.name;
            document.getElementById('el-url').value = d.url || '';
            document.getElementById('el-sort').value = d.sort;
            document.getElementById('el-active').checked = d.active === '1';
            new bootstrap.Modal(document.getElementById('editLogoModal')).show();
        });
    });
</script>
@endpush
@endsection

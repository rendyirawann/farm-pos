@extends('backend.layout.app')
@section('title', 'Mode Pemeliharaan')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">

                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif

                <div class="card card-flush">
                    <div class="card-header pt-6">
                        <div class="card-title flex-column">
                            <h2 class="fw-bold">🛠️ Mode Pemeliharaan</h2>
                            <div class="text-muted fs-7 mt-1">Kunci akses aplikasi sementara untuk semua pengguna kecuali Superadmin.</div>
                        </div>
                        <div class="card-toolbar">
                            @if ($enabled)
                                <span class="badge badge-light-danger fs-7 fw-bold px-3 py-2">● AKTIF</span>
                            @else
                                <span class="badge badge-light-success fs-7 fw-bold px-3 py-2">● NONAKTIF</span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-{{ $enabled ? 'danger' : 'primary' }} fs-7">
                            @if ($enabled)
                                <b>Mode pemeliharaan sedang AKTIF.</b> Pengguna yang membuka halaman login melihat pop-up pemeliharaan (tidak bisa ditutup), dan sesi yang sedang aktif akan diminta keluar. <b>Anda (Superadmin) tetap bisa mengakses semuanya</b> untuk mematikan mode ini.
                            @else
                                Saat diaktifkan: halaman login menampilkan <b>pop-up pemeliharaan</b> yang tidak bisa ditutup, dan pengguna yang sedang login akan mendapat notifikasi lalu <b>otomatis logout</b> saat menekan OK. Superadmin tidak terpengaruh.
                            @endif
                        </div>

                        <form method="POST" action="{{ route('maintenance-settings.update') }}">
                            @csrf

                            <div class="d-flex align-items-center justify-content-between border border-dashed rounded p-5 mb-6">
                                <div class="me-4">
                                    <div class="fw-bold fs-5 text-gray-900">Aktifkan Mode Pemeliharaan</div>
                                    <div class="text-muted fs-7">Geser untuk menghidupkan/mematikan.</div>
                                </div>
                                <label class="form-check form-switch form-check-custom form-check-solid form-switch-lg">
                                    <input class="form-check-input h-30px w-50px" type="checkbox" name="enabled" value="1" id="maint-toggle" {{ $enabled ? 'checked' : '' }} />
                                </label>
                            </div>

                            <div class="mb-6">
                                <label class="form-label fw-semibold">Pesan Pemeliharaan (opsional)</label>
                                <textarea name="message" rows="3" class="form-control" maxlength="500"
                                    placeholder="Contoh: Kami sedang melakukan pemeliharaan sistem. Aplikasi akan kembali normal dalam beberapa saat. Terima kasih.">{{ old('message', $message) }}</textarea>
                                <div class="form-text">Ditampilkan di pop-up login & halaman kunci. Kosongkan untuk memakai pesan bawaan.</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary" id="maint-save">
                                    <span class="indicator-label">Simpan Pengaturan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    // Konfirmasi saat MENGAKTIFKAN (aksi berdampak besar ke seluruh pengguna).
    (function () {
        const form = document.querySelector('form[action="{{ route('maintenance-settings.update') }}"]');
        const toggle = document.getElementById('maint-toggle');
        const wasEnabled = {{ $enabled ? 'true' : 'false' }};
        if (!form) return;
        form.addEventListener('submit', function (e) {
            if (toggle.checked && !wasEnabled) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Aktifkan Mode Pemeliharaan?',
                    html: 'Semua pengguna (kecuali Superadmin) akan <b>otomatis keluar</b> dan tidak bisa login sampai mode ini dimatikan.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Aktifkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#f1416c',
                }).then((r) => { if (r.isConfirmed) form.submit(); });
            }
        });

        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Tersimpan', text: @json(session('success')), timer: 3500, showConfirmButton: false });
        @endif
    })();
</script>
@endpush
@endsection

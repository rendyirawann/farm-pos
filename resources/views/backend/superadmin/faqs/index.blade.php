@extends('backend.layout.app')
@section('title', 'FAQ Landing')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="alert alert-primary fs-7">
            Kelola <b>FAQ / Q&amp;A</b> yang tampil di section <b>FAQ</b> landing <b>mooda.id</b>. Bisa <b>tambah, edit, hapus</b>, dan
            <b>aktif/nonaktifkan</b>. Urutan angka lebih kecil tampil lebih dulu. Hanya FAQ <b>Aktif</b> yang muncul di landing.
        </div>

        {{-- ===== TAMBAH ===== --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah FAQ</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('faqs.store') }}">
                    @csrf
                    <div class="row g-4">
                        <div class="col-md-9">
                            <label class="form-label required fw-semibold">Pertanyaan</label>
                            <input type="text" name="question" class="form-control form-control-solid" maxlength="500" placeholder="mis. Apa itu Mooda?" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" name="sort_order" class="form-control form-control-solid" min="0" placeholder="otomatis">
                        </div>
                        <div class="col-12">
                            <label class="form-label required fw-semibold">Jawaban</label>
                            <textarea name="answer" rows="3" class="form-control form-control-solid" maxlength="5000" placeholder="Tulis jawaban FAQ di sini…" required></textarea>
                        </div>
                    </div>
                    <div class="mt-4 text-end"><button type="submit" class="btn btn-primary">Tambah FAQ</button></div>
                </form>
            </div>
        </div>

        {{-- ===== DAFTAR ===== --}}
        <div class="card card-flush">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Daftar FAQ ({{ $faqs->count() }})</h3></div>
            <div class="card-body">
                <div class="text-muted fs-8 mb-3"><i class="ki-outline ki-information-5 fs-6"></i> Seret ikon <b>⠿</b> untuk mengubah urutan — otomatis tersimpan.</div>
                <div id="faq-sortable">
                @forelse ($faqs as $f)
                    <div class="faq-row border rounded p-4 mb-3 {{ $f->is_active ? '' : 'bg-light-secondary' }}" data-id="{{ $f->id }}">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div class="flex-grow-1" style="min-width:260px;">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="faq-drag text-gray-500" style="cursor:grab;font-size:18px;line-height:1;user-select:none;" title="Seret untuk mengubah urutan">⠿</span>
                                    <span class="badge badge-light-secondary faq-order">#{{ $f->sort_order }}</span>
                                    <span class="badge badge-light-{{ $f->is_active ? 'success' : 'secondary' }}">{{ $f->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    <span class="fw-bold text-gray-900">{{ $f->question }}</span>
                                </div>
                                <div class="text-muted fs-7">{{ $f->answer }}</div>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="collapse" data-bs-target="#edit-{{ $f->id }}">Edit</button>
                                <form method="POST" action="{{ route('faqs.toggle', $f) }}" class="m-0">@csrf
                                    <button type="submit" class="btn btn-sm btn-light-{{ $f->is_active ? 'warning' : 'success' }}">{{ $f->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                                <form method="POST" action="{{ route('faqs.destroy', $f) }}" class="m-0" onsubmit="return confirm('Hapus FAQ ini?')">@csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <div class="collapse mt-4" id="edit-{{ $f->id }}">
                            <form method="POST" action="{{ route('faqs.update', $f) }}" class="border-top pt-4">
                                @csrf @method('PUT')
                                <div class="row g-4">
                                    <div class="col-md-9">
                                        <label class="form-label required fw-semibold">Pertanyaan</label>
                                        <input type="text" name="question" class="form-control form-control-solid" value="{{ $f->question }}" maxlength="500" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Urutan</label>
                                        <input type="number" name="sort_order" class="form-control form-control-solid" value="{{ $f->sort_order }}" min="0">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label required fw-semibold">Jawaban</label>
                                        <textarea name="answer" rows="3" class="form-control form-control-solid" maxlength="5000" required>{{ $f->answer }}</textarea>
                                    </div>
                                </div>
                                <div class="mt-3 text-end"><button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button></div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-muted text-center py-8">Belum ada FAQ. Tambahkan lewat form di atas.</div>
                @endforelse
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('faq-sortable');
                if (!el || typeof Sortable === 'undefined') return;
                Sortable.create(el, {
                    handle: '.faq-drag',
                    animation: 150,
                    ghostClass: 'bg-light-primary',
                    onEnd: function () {
                        var rows = el.querySelectorAll('.faq-row');
                        var ids = Array.prototype.map.call(rows, function (r) { return r.getAttribute('data-id'); });
                        // update nomor urut secara visual
                        Array.prototype.forEach.call(rows, function (r, i) {
                            var b = r.querySelector('.faq-order');
                            if (b) b.textContent = '#' + (i + 1);
                        });
                        fetch("{{ route('faqs.reorder') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ order: ids }),
                        }).then(function (r) { return r.json(); }).then(function () {
                            if (window.toastr) toastr.success('Urutan FAQ disimpan.');
                        }).catch(function () {
                            if (window.Swal) Swal.fire('Gagal', 'Gagal menyimpan urutan. Muat ulang halaman.', 'error');
                        });
                    },
                });
            });
        </script>

    </div>
</div>
@endsection

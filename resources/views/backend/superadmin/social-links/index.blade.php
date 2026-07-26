@extends('backend.layout.app')
@section('title', 'Sosial Media')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="alert alert-primary fs-7">
            Kelola tautan <b>sosial media</b> yang tampil sebagai ikon di <b>footer landing mooda.id</b>.
            Cukup tempel <b>URL</b> — <b>ikon terdeteksi otomatis</b> (Instagram, TikTok, Facebook, YouTube, X, WhatsApp, dll).
            Bisa tambah, edit, hapus, dan aktif/nonaktifkan. Hanya yang <b>Aktif</b> yang tampil.
        </div>

        {{-- ===== TAMBAH ===== --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah Tautan</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('social-links.store') }}">
                    @csrf
                    <div class="row g-4 align-items-end">
                        <div class="col-md-9">
                            <label class="form-label required fw-semibold">URL Sosial Media</label>
                            <input type="url" name="url" class="form-control form-control-solid" placeholder="https://www.instagram.com/akun-anda" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" name="sort_order" class="form-control form-control-solid" min="0" placeholder="otomatis">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Tambah</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===== DAFTAR ===== --}}
        <div class="card card-flush">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Daftar Tautan ({{ $links->count() }})</h3></div>
            <div class="card-body">
                @forelse ($links as $s)
                    <div class="border rounded p-4 mb-3 {{ $s->is_active ? '' : 'bg-light-secondary' }}">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width:240px;">
                                <span class="d-grid rounded" style="width:40px;height:40px;place-items:center;background:#0f172a;color:#fff;flex:0 0 auto;">
                                    {!! $s->iconSvg() !!}
                                </span>
                                <div class="flex-grow-1" style="min-width:0;">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge badge-light-secondary">#{{ $s->sort_order }}</span>
                                        <span class="badge badge-light-primary">{{ $s->label() }}</span>
                                        <span class="badge badge-light-{{ $s->is_active ? 'success' : 'secondary' }}">{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </div>
                                    <a href="{{ $s->url }}" target="_blank" rel="noopener" class="text-muted fs-8 text-truncate d-block" style="max-width:520px;">{{ $s->url }}</a>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="collapse" data-bs-target="#edit-{{ $s->id }}">Edit</button>
                                <form method="POST" action="{{ route('social-links.toggle', $s) }}" class="m-0">@csrf
                                    <button type="submit" class="btn btn-sm btn-light-{{ $s->is_active ? 'warning' : 'success' }}">{{ $s->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                                <form method="POST" action="{{ route('social-links.destroy', $s) }}" class="m-0" onsubmit="return confirm('Hapus tautan ini?')">@csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <div class="collapse mt-4" id="edit-{{ $s->id }}">
                            <form method="POST" action="{{ route('social-links.update', $s) }}" class="border-top pt-4">
                                @csrf @method('PUT')
                                <div class="row g-4 align-items-end">
                                    <div class="col-md-9">
                                        <label class="form-label required fw-semibold">URL</label>
                                        <input type="url" name="url" class="form-control form-control-solid" value="{{ $s->url }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Urutan</label>
                                        <input type="number" name="sort_order" class="form-control form-control-solid" value="{{ $s->sort_order }}" min="0">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-sm btn-primary w-100">Simpan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-muted text-center py-8">Belum ada tautan sosial media. Tambahkan lewat form di atas.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

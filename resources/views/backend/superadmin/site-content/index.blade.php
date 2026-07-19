@extends('backend.layout.app')
@section('title', 'Kelola Situs')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="alert alert-primary fs-7">
            Kelola konten <b>landing page</b> tiap situs secara terpisah — <b>logo, teks setiap section, dan gambar</b>.
            Ubah teks lalu <b>Simpan</b>. Kosongkan sebuah kolom untuk memakai <b>teks bawaan</b>.
            Gambar: format <b>JPG / JPEG / PNG, maksimal 1MB</b>.
        </div>

        {{-- ===== TAB SITUS ===== --}}
        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x fs-5 fw-semibold mb-6">
            @foreach ($sites as $skey => $site)
                <li class="nav-item">
                    <a class="nav-link {{ $active === $skey ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $skey }}">
                        {{ $site['label'] ?? $skey }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach ($sites as $skey => $site)
                <div class="tab-pane fade {{ $active === $skey ? 'show active' : '' }}" id="tab-{{ $skey }}" role="tabpanel">

                    @if (! empty($site['url']))
                        <div class="text-muted fs-7 mb-4">Situs: <a href="{{ $site['url'] }}" target="_blank" rel="noopener" class="fw-semibold">{{ $site['url'] }}</a></div>
                    @endif

                    <form method="POST" action="{{ route('site-content.update', $skey) }}" enctype="multipart/form-data">
                        @csrf

                        @foreach ($site['groups'] as $group)
                            <div class="card card-flush mb-5">
                                <div class="card-header pt-5">
                                    <h3 class="card-title fw-bold">{{ $group['label'] }}</h3>
                                </div>
                                <div class="card-body pt-2">
                                    @foreach ($group['fields'] as $f)
                                        @php
                                            $optKey = $skey . '.' . $f['key'];
                                            $stored = $values[$optKey] ?? null;
                                            $type   = $f['type'] ?? 'text';
                                            $default = $f['default'] ?? '';
                                        @endphp

                                        <div class="mb-7">
                                            <label class="form-label fw-semibold">{{ $f['label'] }}</label>

                                            @if ($type === 'image')
                                                <div class="d-flex align-items-center flex-wrap gap-4 mb-3">
                                                    @php
                                                        $imgSrc = $stored
                                                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($stored)
                                                            : ($default !== '' ? asset($default) : '');
                                                    @endphp
                                                    @if ($imgSrc)
                                                        <img src="{{ $imgSrc }}" alt="" style="max-height:72px;max-width:200px;border-radius:.65rem;background:#f1f1f4;padding:4px;object-fit:contain;">
                                                    @endif
                                                    <span class="badge badge-light-{{ $stored ? 'primary' : 'secondary' }}">
                                                        {{ $stored ? 'Gambar kustom aktif' : 'Memakai bawaan' }}
                                                    </span>
                                                </div>
                                                <input type="file" name="images[{{ $f['key'] }}]" accept=".jpg,.jpeg,.png" class="form-control form-control-solid">
                                                <div class="form-text">JPG / JPEG / PNG, maks 1MB. Biarkan kosong untuk mempertahankan gambar saat ini.</div>
                                                @if ($stored)
                                                    <label class="form-check form-check-sm form-check-custom mt-3">
                                                        <input type="checkbox" class="form-check-input" name="remove_image[{{ $f['key'] }}]" value="1">
                                                        <span class="form-check-label text-muted">Hapus gambar kustom — kembali ke bawaan</span>
                                                    </label>
                                                @endif

                                            @elseif ($type === 'textarea')
                                                <textarea name="fields[{{ $f['key'] }}]" rows="3" class="form-control form-control-solid" placeholder="{{ $default }}">{{ $stored ?? $default }}</textarea>

                                            @else
                                                <input type="text" name="fields[{{ $f['key'] }}]" value="{{ $stored ?? $default }}" placeholder="{{ $default }}" class="form-control form-control-solid">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-end mb-12">
                            <a href="{{ $site['url'] ?? '#' }}" target="_blank" rel="noopener" class="btn btn-light me-3">Lihat Situs</a>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan — {{ $site['label'] ?? $skey }}</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection

@extends('backend.layout.app')
@section('title', 'Tentang Kami — Founder')
@section('content')
    <div class="app-container container-xxl">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Tentang Kami — Founder</h1>
                <span class="text-muted fs-7">Kelola nama, jabatan, bio, & foto founder yang tampil di halaman
                    <a href="{{ route('tentang') }}" target="_blank">Tentang Kami</a>.</span>
            </div>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('founders.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-6">
                @foreach ($founders as $f)
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="mb-4">
                                    <div class="symbol symbol-125px symbol-circle mx-auto">
                                        @if ($f->photoUrl())
                                            <img src="{{ $f->photoUrl() }}" alt="{{ $f->name }}" style="object-fit:cover">
                                        @else
                                            <span class="symbol-label bg-light-primary text-primary fs-2 fw-bold">
                                                {{ strtoupper(mb_substr($f->name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-start">
                                    <label class="form-label fw-semibold required">Nama</label>
                                    <input type="text" name="founders[{{ $f->id }}][name]" value="{{ $f->name }}" class="form-control form-control-sm mb-3">

                                    <label class="form-label fw-semibold required">Jabatan</label>
                                    <input type="text" name="founders[{{ $f->id }}][position]" value="{{ $f->position }}" class="form-control form-control-sm mb-3">

                                    <label class="form-label fw-semibold">Bio singkat</label>
                                    <textarea name="founders[{{ $f->id }}][bio]" rows="3" class="form-control form-control-sm mb-3">{{ $f->bio }}</textarea>

                                    <label class="form-label fw-semibold">Foto (jpg/png/webp, maks 3MB)</label>
                                    <input type="file" name="founders[{{ $f->id }}][photo]" accept="image/*" class="form-control form-control-sm">
                                </div>
                            </div>
                            @if ($f->photoUrl())
                                <div class="card-footer text-center py-3">
                                    <button type="submit" formaction="{{ route('founders.remove-photo', $f->id) }}"
                                        class="btn btn-sm btn-light-danger"
                                        onclick="return confirm('Hapus foto {{ $f->name }}?')">Hapus Foto</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <span class="text-muted fs-8 ms-3">Perubahan langsung tampil di halaman Tentang Kami.</span>
            </div>
        </form>
    </div>
@endsection

{{-- Jejak halaman + tautan kembali ke menu induknya.
     Isinya dibangun otomatis dari nama route (App\Support\FarmBreadcrumb), jadi
     tiap halaman mendapatkannya tanpa perlu disunting satu per satu. --}}
@php $jejak = \App\Support\FarmBreadcrumb::jejak(); @endphp

@if (count($jejak) > 1)
  <div class="app-container container-xxl pt-4 pb-0">
    <div class="d-flex flex-wrap align-items-center gap-2">
      {{-- Tautan "kembali" ke butir terdekat yang punya alamat: di HP inilah yang
           paling sering dipakai, karena jejak panjang sulit ditekan dengan jempol. --}}
      @php
        $indukTerdekat = collect($jejak)->filter(fn ($j) => ! empty($j['url']))->last();
      @endphp
      @if ($indukTerdekat)
        <a href="{{ $indukTerdekat['url'] }}" class="btn btn-sm btn-icon btn-light me-1"
           title="Kembali ke {{ $indukTerdekat['judul'] }}">
          <i class="ki-outline ki-black-left fs-4"></i>
        </a>
      @endif

      <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-8 my-0 flex-wrap">
        @foreach ($jejak as $i => $j)
          <li class="breadcrumb-item {{ $loop->last ? 'text-gray-800 fw-bold' : 'text-muted' }}">
            @if (! empty($j['url']))
              <a href="{{ $j['url'] }}" class="text-muted text-hover-primary">{{ $j['judul'] }}</a>
            @else
              {{ $j['judul'] }}
            @endif
          </li>
          @unless ($loop->last)
            <li class="breadcrumb-item">
              <span class="bullet bg-gray-400 w-5px h-2px"></span>
            </li>
          @endunless
        @endforeach
      </ul>
    </div>
  </div>
@endif

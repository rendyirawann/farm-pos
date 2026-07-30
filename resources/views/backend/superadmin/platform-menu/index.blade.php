@extends('backend.layout.app')
@section('title', 'Platform Menu')

@push('stylesheets')
<style>
    .pm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:16px}
    .pm-card{display:flex;flex-direction:column;align-items:flex-start;gap:2px;background:#fff;border:1px solid #eceef7;
        border-radius:16px;padding:20px 18px;text-decoration:none;transition:transform .16s,box-shadow .22s,border-color .16s;
        box-shadow:0 10px 26px -22px rgba(15,23,42,.6);height:100%;cursor:pointer;text-align:left;width:100%}
    .pm-card:hover{transform:translateY(-4px);border-color:#dfe3f6;box-shadow:0 22px 40px -24px rgba(79,70,229,.55)}
    .pm-ic{width:46px;height:46px;border-radius:13px;display:grid;place-items:center;margin-bottom:12px}
    .pm-ic i{font-size:22px}
    .pm-title{font-weight:700;font-size:14.5px;color:#0f172a;line-height:1.3}
    .pm-desc{font-size:11.5px;color:#94a3b8;margin-top:4px;line-height:1.45}
    .pm-badge{margin-top:10px;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;
        color:#4f46e5;background:#eef2ff;padding:3px 9px;border-radius:999px}
    .pm-group-title{font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;margin:26px 0 12px}
    .pm-sub-item{display:flex;align-items:center;gap:12px;padding:13px 15px;border:1px solid #eceef7;border-radius:12px;
        text-decoration:none;transition:.15s;margin-bottom:10px;background:#fff}
    .pm-sub-item:hover{background:#f6f7ff;border-color:#dfe3f6;transform:translateX(3px)}
    .pm-sub-item i{font-size:19px;color:#4f46e5}
    .pm-sub-item span{font-weight:600;font-size:13.5px;color:#1e293b}
</style>
@endpush

@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Platform Menu</h1>
                <span class="text-muted fs-7">Semua menu Superadmin dalam satu halaman — berguna saat sidebar/menu atas terpotong di layar kecil.</span>
            </div>
        </div>

        @foreach ($groups as $g)
            @php
                // Saring kartu sesuai permission user.
                $cards = collect($g['cards'])->filter(fn ($c) => empty($c['can']) || auth()->user()->can($c['can']))->values();
            @endphp
            @if ($cards->isNotEmpty())
                <div class="pm-group-title">{{ $g['title'] }}</div>
                <div class="pm-grid">
                    @foreach ($cards as $c)
                        @if (!empty($c['items']))
                            {{-- Kartu dengan sub-menu -> buka pop-up --}}
                            <button type="button" class="pm-card" data-bs-toggle="modal" data-bs-target="#pm-modal-{{ Str::slug($c['label']) }}">
                                <span class="pm-ic bg-light-{{ $c['color'] }}"><i class="ki-outline {{ $c['icon'] }} text-{{ $c['color'] }}"></i></span>
                                <span class="pm-title">{{ $c['label'] }}</span>
                                <span class="pm-desc">{{ $c['desc'] ?? '' }}</span>
                                <span class="pm-badge">{{ count($c['items']) }} menu</span>
                            </button>
                        @else
                            <a href="{{ route($c['route']) }}" class="pm-card">
                                <span class="pm-ic bg-light-{{ $c['color'] }}"><i class="ki-outline {{ $c['icon'] }} text-{{ $c['color'] }}"></i></span>
                                <span class="pm-title">{{ $c['label'] }}</span>
                                <span class="pm-desc">{{ $c['desc'] ?? '' }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        @endforeach

        {{-- Pop-up sub-menu --}}
        @foreach ($groups as $g)
            @foreach ($g['cards'] as $c)
                @if (!empty($c['items']) && (empty($c['can']) || auth()->user()->can($c['can'])))
                    <div class="modal fade" id="pm-modal-{{ Str::slug($c['label']) }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered mw-520px">
                            <div class="modal-content rounded-4">
                                <div class="modal-header border-0 pb-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="pm-ic bg-light-{{ $c['color'] }} mb-0"><i class="ki-outline {{ $c['icon'] }} text-{{ $c['color'] }}"></i></span>
                                        <div>
                                            <h2 class="fw-bold text-gray-900 fs-4 mb-0">{{ $c['label'] }}</h2>
                                            <span class="text-muted fs-8">{{ $c['desc'] ?? '' }}</span>
                                        </div>
                                    </div>
                                    <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </div>
                                </div>
                                <div class="modal-body pt-2 pb-6">
                                    @foreach ($c['items'] as $it)
                                        <a href="{{ route($it['route']) }}" class="pm-sub-item">
                                            <i class="ki-outline {{ $it['icon'] }}"></i>
                                            <span>{{ $it['label'] }}</span>
                                            <i class="ki-outline ki-arrow-right ms-auto fs-4 text-gray-400"></i>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endforeach

    </div>
</div>
@endsection

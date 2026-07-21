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

                        {{-- ===== SEKSI REPEATER (Fitur, Kenapa, dst — tambah/ubah/hapus) ===== --}}
                        @foreach ($site['repeaters'] ?? [] as $rkey => $rdef)
                            @php $rid = $skey . '-' . $rkey; @endphp
                            <div class="card card-flush mb-5">
                                <div class="card-header pt-5 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h3 class="card-title fw-bold mb-0">{{ $rdef['label'] }}</h3>
                                    <button type="button" class="btn btn-sm btn-light-primary rep-add" data-rows="#rep-rows-{{ $rid }}" data-tpl="#rep-tpl-{{ $rid }}">
                                        <i class="ki-outline ki-plus fs-7"></i> Tambah {{ $rdef['item_label'] ?? 'Item' }}
                                    </button>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="rep-rows" id="rep-rows-{{ $rid }}">
                                        @foreach (\App\Support\SiteContent::repeater($skey, $rkey) as $i => $item)
                                            @include('backend.superadmin.site-content._repeater-row', ['rkey' => $rkey, 'i' => $i, 'item' => $item])
                                        @endforeach
                                    </div>
                                    <template id="rep-tpl-{{ $rid }}">@include('backend.superadmin.site-content._repeater-row', ['rkey' => $rkey, 'i' => '__IDX__', 'item' => []])</template>
                                    <div class="text-muted fs-8">Kosongkan semua item lalu simpan untuk kembali ke bawaan.</div>
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

<script>
(function () {
    const COLORS = @json(\App\Support\SiteContent::colors(), JSON_HEX_TAG);
    const ICONS  = @json(collect(\App\Support\SiteContent::icons())->map(fn ($i) => $i['svg']), JSON_HEX_TAG);
    let repCounter = {{ (int) (microtime(true) * 1000) % 1000000 }};

    function iconHtml(val) {
        if (val && ICONS[val]) return '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">' + ICONS[val] + '</svg>';
        const t = (val && val.length) ? val.replace(/[<>&]/g, '') : '★';
        return '<span style="font-size:18px">' + t + '</span>';
    }
    function refreshPreview(row) {
        if (!row) return;
        const input = row.querySelector('.rep-icon-input');
        const colorSel = row.querySelector('.rep-color');
        const prev = row.querySelector('.rep-icon-preview');
        if (!prev) return;
        const color = COLORS[colorSel ? colorSel.value : 'indigo'] || { hex: '#4f46e5' };
        prev.style.background = color.hex;
        if (!prev.dataset.img) prev.innerHTML = iconHtml(input ? input.value.trim() : '');
    }

    document.addEventListener('click', function (e) {
        const add = e.target.closest('.rep-add');
        if (add) {
            const rows = document.querySelector(add.dataset.rows);
            const tpl = document.querySelector(add.dataset.tpl);
            if (rows && tpl) {
                const html = tpl.innerHTML.replace(/__IDX__/g, 'n' + (repCounter++));
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                const node = wrap.firstElementChild;
                rows.appendChild(node);
                refreshPreview(node);
                node.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            return;
        }
        const del = e.target.closest('.rep-del');
        if (del) { const r = del.closest('.rep-row'); if (r) r.remove(); return; }
        const pick = e.target.closest('.rep-icon-pick');
        if (pick) {
            const row = pick.closest('.rep-row');
            const input = row.querySelector('.rep-icon-input');
            if (input) input.value = pick.dataset.icon;
            const prev = row.querySelector('.rep-icon-preview'); if (prev) delete prev.dataset.img;
            const file = row.querySelector('.rep-img-input'); if (file) file.value = '';
            const rm = row.querySelector('input[name*="[remove_image]"]'); if (rm) rm.checked = true;
            refreshPreview(row);
            return;
        }
    });
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('rep-icon-input')) {
            const row = e.target.closest('.rep-row');
            const prev = row.querySelector('.rep-icon-preview'); if (prev) delete prev.dataset.img;
            refreshPreview(row);
        }
    });
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('rep-color')) refreshPreview(e.target.closest('.rep-row'));
        if (e.target.classList.contains('rep-img-input')) {
            const row = e.target.closest('.rep-row');
            const prev = row.querySelector('.rep-icon-preview');
            const f = e.target.files && e.target.files[0];
            if (f && prev) {
                prev.dataset.img = '1';
                prev.innerHTML = '<img src="' + URL.createObjectURL(f) + '" style="width:100%;height:100%;object-fit:cover">';
            }
        }
    });
})();
</script>
@endsection

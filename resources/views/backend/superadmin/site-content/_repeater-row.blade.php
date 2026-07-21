@php
    /** @var string $rkey  @var mixed $i  @var array $item */
    $item = $item ?? [];
    $iconVal = $item['icon'] ?? '';
    $colorVal = $item['color'] ?? 'indigo';
    $imgPath = $item['image'] ?? '';
    $col = \App\Support\SiteContent::color($colorVal);
    $imgUrl = $imgPath ? \App\Support\SiteContent::itemImage($imgPath) : '';
    $svg = \App\Support\SiteContent::iconSvg($iconVal, 'h-5 w-5');
    $n = "rep[$rkey][$i]";
@endphp
<div class="rep-row border border-gray-300 rounded p-4 mb-3 position-relative">
    <button type="button" class="btn btn-icon btn-sm btn-light-danger rep-del position-absolute" style="top:10px;right:10px;z-index:2" title="Hapus item">
        <i class="ki-outline ki-trash fs-6"></i>
    </button>
    <div class="row g-4">
        {{-- IKON --}}
        <div class="col-md-5">
            <label class="form-label fw-semibold fs-8 text-muted">Ikon</label>
            <div class="d-flex align-items-center gap-3 mb-2">
                <span class="rep-icon-preview d-grid rounded" style="width:40px;height:40px;place-items:center;background:{{ $col['hex'] }};color:#fff;overflow:hidden;flex:0 0 auto">
                    @if ($imgUrl)
                        <img src="{{ $imgUrl }}" style="width:100%;height:100%;object-fit:cover">
                    @elseif ($svg)
                        {!! $svg !!}
                    @else
                        <span style="font-size:18px">{{ $iconVal !== '' ? $iconVal : '★' }}</span>
                    @endif
                </span>
                <input type="text" class="form-control form-control-sm form-control-solid rep-icon-input" name="{{ $n }}[icon]" value="{{ $iconVal }}" placeholder="emoji / pilih ikon" style="max-width:150px">
            </div>
            <div class="rep-icon-picker d-flex flex-wrap gap-1 mb-3" style="max-height:96px;overflow-y:auto">
                @foreach (\App\Support\SiteContent::icons() as $ikey => $idef)
                    <button type="button" class="btn btn-icon btn-sm btn-light rep-icon-pick" data-icon="{{ $ikey }}" title="{{ $idef['label'] }}" style="width:32px;height:32px">
                        {!! \App\Support\SiteContent::iconSvg($ikey, 'h-4 w-4') !!}
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="{{ $n }}[image_existing]" value="{{ $imgPath }}">
            <input type="file" class="form-control form-control-sm form-control-solid rep-img-input" name="rep_img[{{ $rkey }}][{{ $i }}]" accept=".jpg,.jpeg,.png">
            <div class="form-text fs-9">Upload gambar (JPG/PNG, maks 1MB) untuk mengganti ikon.</div>
            @if ($imgPath)
                <label class="form-check form-check-sm mt-1">
                    <input type="checkbox" class="form-check-input" name="{{ $n }}[remove_image]" value="1">
                    <span class="form-check-label text-muted fs-8">Hapus gambar (pakai ikon)</span>
                </label>
            @endif
        </div>
        {{-- JUDUL / DESKRIPSI / WARNA --}}
        <div class="col-md-7">
            <label class="form-label fw-semibold fs-8 text-muted">Judul</label>
            <input type="text" class="form-control form-control-sm form-control-solid mb-3" name="{{ $n }}[title]" value="{{ $item['title'] ?? '' }}" placeholder="Judul kartu">
            <label class="form-label fw-semibold fs-8 text-muted">Deskripsi</label>
            <textarea class="form-control form-control-sm form-control-solid mb-3" rows="2" name="{{ $n }}[desc]" placeholder="Deskripsi singkat">{{ $item['desc'] ?? '' }}</textarea>
            <label class="form-label fw-semibold fs-8 text-muted">Warna Ikon</label>
            <select class="form-select form-select-sm form-select-solid rep-color" name="{{ $n }}[color]">
                @foreach (\App\Support\SiteContent::colors() as $ckey => $cdef)
                    <option value="{{ $ckey }}" @selected($colorVal === $ckey)>{{ $cdef['label'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

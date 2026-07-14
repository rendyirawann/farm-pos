@php($p = $post ?? null)
<div class="row g-4">
    <div class="col-12">
        <label class="form-label required">Judul</label>
        <input type="text" name="title" class="form-control form-control-solid" value="{{ $p->title ?? '' }}" placeholder="Judul artikel">
        <span class="text-danger error-text fs-8 title_error_{{ $mode }}"></span>
    </div>

    <div class="col-md-6">
        <label class="form-label">Kategori</label>
        <select name="blog_category_id" class="form-select form-select-solid">
            <option value="">— Tanpa kategori —</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(($p->blog_category_id ?? null) == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text fs-8 blog_category_id_error_{{ $mode }}"></span>
    </div>

    <div class="col-md-6">
        <label class="form-label required">Status</label>
        <select name="status" class="form-select form-select-solid">
            <option value="draft" @selected(($p->status ?? 'draft') === 'draft')>Draf (belum tayang)</option>
            <option value="published" @selected(($p->status ?? '') === 'published')>Terbit</option>
        </select>
        <span class="text-danger error-text fs-8 status_error_{{ $mode }}"></span>
    </div>

    <div class="col-12">
        <label class="form-label">Ringkasan (excerpt)</label>
        <textarea name="excerpt" rows="2" class="form-control form-control-solid" placeholder="Ringkasan singkat untuk daftar artikel & hasil pencarian">{{ $p->excerpt ?? '' }}</textarea>
        <span class="text-danger error-text fs-8 excerpt_error_{{ $mode }}"></span>
    </div>

    <div class="col-12">
        <label class="form-label">Isi Artikel</label>
        {{-- body sudah disanitasi purifier saat simpan; aman ditaruh di textarea (RCDATA) --}}
        <textarea id="{{ $mode }}_body" name="body">{!! $p->body ?? '' !!}</textarea>
        <span class="text-danger error-text fs-8 body_error_{{ $mode }}"></span>
    </div>

    <div class="col-md-6">
        <label class="form-label">Cover</label>
        @if ($p && $p->cover)
            <div class="mb-2"><img src="{{ asset('storage/blog/' . $p->cover) }}" style="height:60px;border-radius:6px;object-fit:cover;"></div>
        @endif
        <input type="file" name="cover" class="form-control form-control-solid" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">JPG/PNG/WEBP, maks 2MB.@if ($p && $p->cover) Kosongkan bila tak ingin mengubah.@endif</div>
        <span class="text-danger error-text fs-8 cover_error_{{ $mode }}"></span>
    </div>

    <div class="col-md-6">
        <label class="form-label">Meta Title (SEO)</label>
        <input type="text" name="meta_title" class="form-control form-control-solid" value="{{ $p->meta_title ?? '' }}" placeholder="Default: judul artikel">
        <span class="text-danger error-text fs-8 meta_title_error_{{ $mode }}"></span>
    </div>

    <div class="col-12">
        <label class="form-label">Meta Description (SEO)</label>
        <textarea name="meta_description" rows="2" class="form-control form-control-solid" placeholder="Default: ringkasan artikel">{{ $p->meta_description ?? '' }}</textarea>
        <span class="text-danger error-text fs-8 meta_description_error_{{ $mode }}"></span>
    </div>
</div>

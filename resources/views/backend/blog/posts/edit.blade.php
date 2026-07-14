{{-- Partial form edit (di-inject ke #EditPostBody). Tanpa @extends. --}}
<input type="hidden" id="edit_post_id" value="{{ $post->id }}">
@include('backend.blog.posts._fields', ['mode' => 'edit', 'post' => $post, 'categories' => $categories])

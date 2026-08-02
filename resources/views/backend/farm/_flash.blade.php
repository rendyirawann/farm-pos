@if (session('success'))
  <div class="alert alert-success d-flex align-items-center py-3">
    <i class="ki-outline ki-check-circle fs-2 me-2"></i><div>{{ session('success') }}</div>
  </div>
@endif
@if (session('error'))
  <div class="alert alert-danger d-flex align-items-center py-3">
    <i class="ki-outline ki-information-5 fs-2 me-2"></i><div>{{ session('error') }}</div>
  </div>
@endif
@if ($errors->any())
  <div class="alert alert-danger py-3">
    <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

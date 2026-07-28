@extends('backend.layout.app')
@section('title', 'Pencairan Komisi Affiliate')
@section('content')
    <div class="app-container container-xxl">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Pencairan Komisi Affiliate</h1>
                <span class="text-muted fs-7">Verifikasi pengajuan pencairan lewat <b>kode unik</b>, lalu tandai selesai/ tolak.</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('affiliates.index') }}" class="btn btn-sm btn-light">← Affiliate</a>
                <a href="{{ route('affiliates.settings') }}" class="btn btn-sm btn-light-primary">Setelan</a>
            </div>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-4 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-6">Kode</th><th>Affiliate</th><th>Diajukan</th><th class="text-end">Jumlah</th><th>Status</th><th class="text-end pe-6">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($withdrawals as $wd)
                                @php($badge = ['pending' => 'warning', 'done' => 'success', 'rejected' => 'danger'][$wd->status] ?? 'secondary')
                                <tr>
                                    <td class="ps-6"><span class="fw-bold text-gray-900">{{ $wd->code }}</span></td>
                                    <td>
                                        <span class="fw-semibold text-gray-800 d-block">{{ optional($wd->affiliate)->name ?? '-' }}</span>
                                        <span class="text-muted fs-8">{{ optional($wd->affiliate)->email }}</span>
                                    </td>
                                    <td class="text-muted fs-7">{{ optional($wd->requested_at ?? $wd->created_at)->translatedFormat('d M Y H:i') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format((float) $wd->amount, 0, ',', '.') }}</td>
                                    <td><span class="badge badge-light-{{ $badge }}">{{ ucfirst($wd->status) }}</span></td>
                                    <td class="text-end pe-6">
                                        @if ($wd->status === 'pending')
                                            <form method="POST" action="{{ route('affiliates.withdrawals.done', $wd->id) }}" class="d-inline" onsubmit="return confirm('Tandai pencairan {{ $wd->code }} SELESAI (dicairkan)?')">@csrf
                                                <button class="btn btn-sm btn-light-success">Tandai Selesai</button>
                                            </form>
                                            <form method="POST" action="{{ route('affiliates.withdrawals.reject', $wd->id) }}" class="d-inline" onsubmit="return confirm('Tolak pencairan {{ $wd->code }}? Komisi dikembalikan ke saldo affiliate.')">@csrf
                                                <button class="btn btn-sm btn-light-danger">Tolak</button>
                                            </form>
                                        @else
                                            <span class="text-muted fs-8">{{ optional($wd->done_at)->translatedFormat('d M Y H:i') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-10">Belum ada pengajuan pencairan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">{{ $withdrawals->onEachSide(1)->links() }}</div>
    </div>
@endsection

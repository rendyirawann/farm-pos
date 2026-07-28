@extends('affiliate.app')
@section('title', 'Pencairan Komisi — Mooda Affiliate')

@php
    $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $adminWa = '6285760366666';
    $isActive = $affiliate->status === 'active';
    $waText = fn ($wd) => rawurlencode(
        "Halo Admin Mooda, saya {$affiliate->name} ({$affiliate->email}) ingin mencairkan komisi affiliate.\n"
        . "Kode Pencairan: {$wd->code}\n"
        . "Jumlah: Rp" . number_format((float) $wd->amount, 0, ',', '.') . "\n"
        . "Berikut saya lampirkan bukti komisi saya:"
    );
@endphp

@section('content')
    <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Pencairan Komisi</h1>
            <p class="text-slate-500">Ajukan penarikan komisi yang sudah bisa dicairkan.</p>
        </div>

        @if (session('wd_error'))
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 mb-5">{{ session('wd_error') }}</div>
        @endif

        @unless ($isActive)
            <div class="rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50 p-8 text-center">
                <div class="text-4xl mb-2">🔒</div>
                <div class="font-bold text-amber-800">Belum bisa mencairkan</div>
                <p class="text-sm text-amber-700 mt-1">Akunmu masih menunggu persetujuan Superadmin.</p>
            </div>
        @else
            {{-- Saldo tersedia --}}
            <div class="rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-600 text-white p-6 sm:p-8 mb-6">
                <div class="text-sm text-emerald-100 mb-1">Komisi bisa dicairkan</div>
                <div class="text-4xl font-black">{{ $rupiah($available) }}</div>
            </div>

            @if ($pending)
                {{-- Ada pengajuan yang menunggu --}}
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6 mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-3 py-1 text-xs font-bold">● Menunggu diproses</span>
                    </div>
                    <div class="text-sm text-slate-600">Kode pencairan (tunjukkan ke Admin untuk verifikasi):</div>
                    <div class="text-2xl font-black tracking-wider text-indigo-700 my-1">{{ $pending->code }}</div>
                    <div class="text-sm text-slate-600 mb-4">Jumlah diajukan: <b>{{ $rupiah($pending->amount) }}</b></div>
                    <a href="https://wa.me/{{ $adminWa }}?text={{ $waText($pending) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 text-white font-semibold px-5 py-3 hover:bg-emerald-600 transition text-sm">
                        Kirim bukti ke WhatsApp Admin →
                    </a>
                    <p class="text-xs text-slate-500 mt-3">Kirim bukti komisi via WhatsApp beserta kode di atas. Kamu belum bisa mengajukan pencairan baru sampai yang ini selesai.</p>
                </div>
            @else
                {{-- Form ajukan --}}
                <form method="POST" action="{{ route('affiliate.withdraw.submit') }}" class="rounded-2xl border border-slate-200 bg-white p-6 mb-6">
                    @csrf
                    <p class="text-sm text-slate-600 mb-4">Sekali ajukan = menarik <b>seluruh</b> komisi yang bisa dicairkan ({{ $rupiah($available) }}). Kamu akan mendapat <b>kode pencairan unik</b> untuk dikirim ke Admin via WhatsApp sebagai verifikasi.</p>
                    <button type="submit" @disabled($available <= 0)
                        class="w-full rounded-xl bg-indigo-600 text-white font-semibold py-3 hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ $available > 0 ? 'Ajukan Pencairan ' . $rupiah($available) : 'Belum ada komisi untuk dicairkan' }}
                    </button>
                </form>
            @endif

            {{-- Riwayat --}}
            <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white">
                <div class="px-6 py-4 border-b border-slate-100 font-bold text-slate-900">Riwayat pencairan</div>
                @if ($withdrawals->count())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 text-left">
                                <tr><th class="px-6 py-3 font-semibold">Kode</th><th class="px-6 py-3 font-semibold">Tanggal</th><th class="px-6 py-3 font-semibold">Status</th><th class="px-6 py-3 font-semibold text-right">Jumlah</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($withdrawals as $wd)
                                    @php($badge = ['pending' => ['Menunggu', 'bg-amber-50 text-amber-700'], 'done' => ['Dicairkan', 'bg-emerald-50 text-emerald-700'], 'rejected' => ['Ditolak', 'bg-red-50 text-red-700']][$wd->status] ?? [$wd->status, 'bg-slate-100 text-slate-600'])
                                    <tr>
                                        <td class="px-6 py-4 font-bold text-slate-800">{{ $wd->code }}</td>
                                        <td class="px-6 py-4 text-slate-500">{{ optional($wd->requested_at ?? $wd->created_at)->locale('id')->translatedFormat('d M Y H:i') }}</td>
                                        <td class="px-6 py-4"><span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span></td>
                                        <td class="px-6 py-4 text-right font-semibold text-slate-800">{{ $rupiah($wd->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-slate-400 text-sm">Belum ada pengajuan pencairan.</div>
                @endif
            </div>
        @endunless
    </div>
@endsection

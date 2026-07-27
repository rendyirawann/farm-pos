@extends('affiliate.app')
@section('title', 'Dashboard Affiliate — Mooda')

@php($rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'))

@section('content')
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Halo, {{ auth()->user()->name }} 👋</h1>
                <p class="text-slate-500">Pantau referral & komisimu di sini.</p>
            </div>
            @if ($affiliate->status === 'active')
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-1.5 text-sm font-semibold">● Aktif</span>
            @elseif ($affiliate->status === 'pending')
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-4 py-1.5 text-sm font-semibold">● Menunggu persetujuan</span>
            @else
                <span class="inline-flex items-center gap-2 rounded-full bg-red-50 text-red-700 border border-red-200 px-4 py-1.5 text-sm font-semibold">● Ditangguhkan</span>
            @endif
        </div>

        @if ($affiliate->status === 'pending')
            <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-5 py-4 mb-8 text-sm">
                Akun afiliatormu <b>sedang ditinjau admin</b>. Kamu sudah bisa membagikan link di bawah — referral akan tetap tercatat, dan komisi diproses setelah akun disetujui.
            </div>
        @endif

        {{-- Link referral --}}
        <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white p-6 sm:p-8 mb-8">
            <div class="text-sm text-indigo-100 mb-1">Kode referral kamu</div>
            <div class="text-3xl font-black tracking-wide mb-4">{{ $affiliate->code }}</div>
            <div class="text-sm text-indigo-100 mb-2">Link referral (bagikan ini):</div>
            <div class="flex flex-col sm:flex-row gap-2">
                <input id="ref-link" readonly value="{{ $affiliate->referralUrl() }}"
                    class="flex-1 rounded-xl bg-white/15 border border-white/25 px-4 py-2.5 text-white placeholder-white/60 outline-none text-sm">
                <button id="copy-link" class="rounded-xl bg-white text-indigo-700 font-semibold px-5 py-2.5 hover:bg-indigo-50 transition text-sm">Salin Link</button>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @php($cards = [
                ['Total referral', $stats['total'] . ' tenant', 'text-slate-900'],
                ['Berlangganan', $stats['subscribed'] . ' tenant', 'text-indigo-600'],
                ['Komisi cair', $rupiah($stats['earned']), 'text-emerald-600'],
                ['Komisi pending', $rupiah($stats['pending']), 'text-amber-600'],
            ])
            @foreach ($cards as [$label, $val, $color])
                <div class="rounded-2xl border border-slate-200 p-5">
                    <div class="text-xs text-slate-500 mb-1">{{ $label }}</div>
                    <div class="text-xl font-extrabold {{ $color }}">{{ $val }}</div>
                </div>
            @endforeach
        </div>

        {{-- Referral list --}}
        <div class="rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 font-bold text-slate-900">Tenant yang memakai kodemu</div>
            @if ($referrals->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-left">
                            <tr><th class="px-6 py-3 font-semibold">Tenant</th><th class="px-6 py-3 font-semibold">Tanggal</th><th class="px-6 py-3 font-semibold">Status</th><th class="px-6 py-3 font-semibold text-right">Komisi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($referrals as $r)
                                <tr>
                                    <td class="px-6 py-4 font-semibold text-slate-800">{{ optional($r->tenant)->name ?? $r->tenant_name ?? '(tenant)' }}</td>
                                    <td class="px-6 py-4 text-slate-500">{{ optional($r->created_at)->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        @if ($r->status === 'subscribed')
                                            <span class="inline-block rounded-full bg-indigo-50 text-indigo-700 px-3 py-1 text-xs font-semibold">Berlangganan</span>
                                        @else
                                            <span class="inline-block rounded-full bg-slate-100 text-slate-600 px-3 py-1 text-xs font-semibold">Daftar</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold {{ $r->commission_status === 'paid' ? 'text-emerald-600' : 'text-slate-400' }}">
                                        {{ $rupiah($r->commission_amount) }}
                                        <span class="block text-xs font-normal {{ $r->commission_status === 'paid' ? 'text-emerald-500' : 'text-slate-400' }}">{{ $r->commission_status === 'paid' ? 'lunas' : 'pending' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-16 text-slate-400">
                    <div class="text-4xl mb-2">🔗</div>
                    <p class="font-semibold">Belum ada yang memakai kodemu.</p>
                    <p class="text-sm">Bagikan link referral di atas untuk mulai dapat komisi.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('copy-link')?.addEventListener('click', function () {
            var i = document.getElementById('ref-link');
            i.select(); i.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(i.value).then(() => { this.textContent = 'Tersalin ✓'; setTimeout(() => this.textContent = 'Salin Link', 1800); });
        });
    </script>
@endsection

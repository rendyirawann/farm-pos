@extends('affiliate.app')
@section('title', 'Komisi — Mooda Affiliate')

@php($rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'))

@section('content')
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Komisi</h1>
            <p class="text-slate-500">Ringkasan pendapatan komisi dari referralmu.</p>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @php($cards = [
                ['Komisi cair', $rupiah($stats['earned']), 'text-emerald-600'],
                ['Komisi pending', $rupiah($stats['pending']), 'text-amber-600'],
                ['Total referral', $stats['total'] . ' tenant', 'text-slate-900'],
                ['Berlangganan', $stats['subscribed'] . ' tenant', 'text-indigo-600'],
            ])
            @foreach ($cards as [$label, $val, $color])
                <div class="rounded-2xl border border-slate-200 p-5">
                    <div class="text-xs text-slate-500 mb-1">{{ $label }}</div>
                    <div class="text-xl font-extrabold {{ $color }}">{{ $val }}</div>
                </div>
            @endforeach
        </div>

        {{-- Rincian komisi per referral --}}
        <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white">
            <div class="px-6 py-4 border-b border-slate-100 font-bold text-slate-900">Rincian komisi</div>
            @php($komisi = $referrals->filter(fn ($r) => (float) $r->commission_amount > 0))
            @if ($komisi->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-left">
                            <tr><th class="px-6 py-3 font-semibold">Tenant</th><th class="px-6 py-3 font-semibold">Tanggal</th><th class="px-6 py-3 font-semibold">Status</th><th class="px-6 py-3 font-semibold text-right">Komisi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($komisi as $r)
                                <tr>
                                    <td class="px-6 py-4 font-semibold text-slate-800">{{ optional($r->tenant)->name ?? $r->tenant_name ?? '(tenant)' }}</td>
                                    <td class="px-6 py-4 text-slate-500">{{ optional($r->created_at)->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        @if ($r->commission_status === 'paid')
                                            <span class="inline-block rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-semibold">Lunas</span>
                                        @else
                                            <span class="inline-block rounded-full bg-amber-50 text-amber-700 px-3 py-1 text-xs font-semibold">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold {{ $r->commission_status === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}">{{ $rupiah($r->commission_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-16 text-slate-400">
                    <div class="text-4xl mb-2">💰</div>
                    <p class="font-semibold">Belum ada komisi.</p>
                    <p class="text-sm">Komisi muncul saat referralmu berlangganan.</p>
                    <a href="{{ route('affiliate.link') }}" class="inline-block mt-4 rounded-xl bg-indigo-600 text-white font-semibold px-5 py-2.5 text-sm hover:bg-indigo-700">Bagikan Link Referral</a>
                </div>
            @endif
        </div>
    </div>
@endsection

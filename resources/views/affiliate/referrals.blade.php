@extends('affiliate.app')
@section('title', 'Referral Saya — Mooda Affiliate')

@php($rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'))

@section('content')
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Referral Saya</h1>
            <p class="text-slate-500">Daftar bisnis yang mendaftar memakai kodemu.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="rounded-2xl border border-slate-200 p-5">
                <div class="text-xs text-slate-500 mb-1">Total referral</div>
                <div class="text-xl font-extrabold text-slate-900">{{ $stats['total'] }} tenant</div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-5">
                <div class="text-xs text-slate-500 mb-1">Sudah berlangganan</div>
                <div class="text-xl font-extrabold text-indigo-600">{{ $stats['subscribed'] }} tenant</div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white">
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
                    <p class="text-sm">Bagikan link referral untuk mulai dapat komisi.</p>
                    <a href="{{ route('affiliate.link') }}" class="inline-block mt-4 rounded-xl bg-indigo-600 text-white font-semibold px-5 py-2.5 text-sm hover:bg-indigo-700">Buka Link Referral</a>
                </div>
            @endif
        </div>
    </div>
@endsection

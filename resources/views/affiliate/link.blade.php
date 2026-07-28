@extends('affiliate.app')
@section('title', 'Link Referral — Mooda Affiliate')

@section('content')
    @php
        $url = $affiliate->referralUrl();
        $msg = rawurlencode("Yuk kelola bisnismu pakai Mooda POS! Daftar lewat link referral saya: " . $url);
    @endphp
    <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Link Referral</h1>
            <p class="text-slate-500">Bagikan link ini — komisi masuk untuk setiap bisnis yang bergabung.</p>
        </div>

        @php($isActive = $affiliate->status === 'active')

        @if (! $isActive)
            {{-- Terkunci sampai disetujui Superadmin --}}
            <div class="rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50 p-8 mb-6 text-center">
                <div class="text-4xl mb-2">🔒</div>
                <div class="font-bold text-amber-800">Link referral belum aktif</div>
                <p class="text-sm text-amber-700 mt-1 max-w-md mx-auto">Akunmu <b>sedang ditinjau Superadmin</b>. Kode <b>{{ $affiliate->code }}</b> baru bisa dibagikan setelah akunmu disetujui. Link yang dibagikan sekarang tidak akan tercatat.</p>
            </div>
        @else
        {{-- Kartu link --}}
        <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white p-6 sm:p-8 mb-6">
            <div class="text-sm text-indigo-100 mb-1">Kode referral kamu</div>
            <div class="text-3xl font-black tracking-wide mb-4">{{ $affiliate->code }}</div>
            <div class="text-sm text-indigo-100 mb-2">Link referral (bagikan ini):</div>
            <div class="flex flex-col sm:flex-row gap-2">
                <input id="ref-link" readonly value="{{ $url }}"
                    class="flex-1 rounded-xl bg-white/15 border border-white/25 px-4 py-2.5 text-white outline-none text-sm">
                <button id="copy-link" class="rounded-xl bg-white text-indigo-700 font-semibold px-5 py-2.5 hover:bg-indigo-50 transition text-sm">Salin Link</button>
            </div>
        </div>

        {{-- Tombol share --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
            <a href="https://wa.me/?text={{ $msg }}" target="_blank" rel="noopener"
               class="flex items-center justify-center gap-2 rounded-xl bg-emerald-500 text-white font-semibold py-3 hover:bg-emerald-600 transition text-sm">WhatsApp</a>
            <a href="https://t.me/share/url?url={{ rawurlencode($url) }}&text={{ rawurlencode('Kelola bisnismu pakai Mooda POS!') }}" target="_blank" rel="noopener"
               class="flex items-center justify-center gap-2 rounded-xl bg-sky-500 text-white font-semibold py-3 hover:bg-sky-600 transition text-sm">Telegram</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}" target="_blank" rel="noopener"
               class="flex items-center justify-center gap-2 rounded-xl bg-blue-600 text-white font-semibold py-3 hover:bg-blue-700 transition text-sm">Facebook</a>
            <button id="copy-link-2" class="flex items-center justify-center gap-2 rounded-xl bg-slate-800 text-white font-semibold py-3 hover:bg-slate-900 transition text-sm">Salin Link</button>
        </div>
        @endif

        {{-- Tips --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="font-bold text-slate-900 mb-3">Tips agar cepat dapat komisi</h2>
            <ul class="space-y-2.5 text-sm text-slate-600">
                <li class="flex gap-2"><span class="text-indigo-600 font-bold">1.</span> Bagikan ke pemilik usaha (cafe, resto, UMKM) di sekitarmu.</li>
                <li class="flex gap-2"><span class="text-indigo-600 font-bold">2.</span> Jelaskan manfaat Mooda: kasir, dapur, laporan — semua dalam satu aplikasi.</li>
                <li class="flex gap-2"><span class="text-indigo-600 font-bold">3.</span> Komisi diproses setelah bisnis yang kamu ajak berlangganan.</li>
            </ul>
        </div>
    </div>

    <script>
        function copyRefLink(btn) {
            var i = document.getElementById('ref-link');
            navigator.clipboard.writeText(i.value).then(function () {
                var t = btn.textContent; btn.textContent = 'Tersalin ✓';
                setTimeout(function () { btn.textContent = t; }, 1800);
            });
        }
        document.getElementById('copy-link')?.addEventListener('click', function () { copyRefLink(this); });
        document.getElementById('copy-link-2')?.addEventListener('click', function () { copyRefLink(this); });
    </script>
@endsection

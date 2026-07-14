<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subdomain AFFILIATE — affiliate.mooda.id
|--------------------------------------------------------------------------
| Dilayani oleh app yang sama via Octane (bukan stack terpisah).
| Sementara: halaman "segera hadir". Modul afiliasi menyusul
| (kode/link referral, tracking sign-up tenant, komisi, dashboard afiliator, payout).
*/

Route::get('/', fn () => view('subdomain.coming-soon', [
    'brand'     => 'Program Afiliasi Mooda',
    'tagline'   => 'Ajak pemilik usaha memakai Mooda, dapatkan komisi tiap langganan.',
    'icon'      => '🤝',
    'subdomain' => 'affiliate.mooda.id',
]))->name('affiliate.home');

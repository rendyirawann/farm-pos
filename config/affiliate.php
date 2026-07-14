<?php

return [
    /*
    | Modul AFFILIATE (program referral Mooda).
    | Komisi ONE-TIME: dibayar sekali saat tenant yang direferral pertama kali
    | berlangganan berbayar. Nominal bisa flat (Rp) atau persen dari nilai langganan.
    */

    // 'flat' = jumlah rupiah tetap; 'percent' = persen dari nilai langganan pertama.
    'commission_type'  => env('AFFILIATE_COMMISSION_TYPE', 'flat'),

    // Nilai komisi: kalau flat -> rupiah (mis. 50000); kalau percent -> angka persen (mis. 20).
    'commission_value' => (float) env('AFFILIATE_COMMISSION_VALUE', 50000),

    // Masa berlaku cookie referral (hari).
    'cookie_days'      => (int) env('AFFILIATE_COOKIE_DAYS', 30),

    // Nama cookie referral.
    'cookie_name'      => 'mooda_ref',
];

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tandai trial/langganan tenant yang kedaluwarsa setiap hari pukul 00:05.
// Jalankan scheduler via cron: * * * * * php artisan schedule:run
Schedule::command('subscriptions:expire')->dailyAt('00:05');

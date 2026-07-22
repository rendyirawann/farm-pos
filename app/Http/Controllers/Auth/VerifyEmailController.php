<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Aktivasi akun via link email. Setelah aktivasi: logout lalu arahkan ke
     * halaman login ("silakan login kembali"). Bila akun sudah aktif, tetap
     * diarahkan ke login (halaman aktivasi tak bisa dipakai lagi).
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $justVerified = false;

        if (! $request->user()->hasVerifiedEmail()) {
            if ($request->user()->markEmailAsVerified()) {
                // Memicu App\Listeners\GrantStarterOnVerified (bonus saldo Starter).
                event(new Verified($request->user()));
                $justVerified = true;
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Pakai query param (bukan flash) agar pesan selamat dari session invalidate.
        $flag = $justVerified ? 'activated' : 'active';
        return redirect()->route('login', [$flag => 1]);
    }
}

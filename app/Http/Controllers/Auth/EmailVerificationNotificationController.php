<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        // Sudah aktif -> tak perlu kirim ulang; arahkan ke login.
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('login', ['active' => 1]);
        }

        // Cooldown: kirim ulang hanya boleh setiap 2 menit.
        if ($request->user()->verificationResendCooldown(120) > 0) {
            return back()->with('status', 'cooldown');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}

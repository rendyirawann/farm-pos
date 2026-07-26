<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('login', ['active' => 1]);
        }

        // Sisa cooldown kirim-ulang (0 = boleh kirim) untuk countdown tombol.
        return view('auth.verify-email', [
            'cooldown' => $request->user()->verificationResendCooldown(120),
        ]);
    }
}

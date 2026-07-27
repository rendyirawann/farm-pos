<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Aktivasi akun via link email (signed URL, TANPA wajib login).
     *
     * Keamanan: URL sudah bertanda-tangan (middleware 'signed' = HMAC app key + expiry),
     * jadi hanya server yang bisa membuatnya. Kepemilikan dicek lewat hash sha1(email).
     * Ini membuat link bisa diklik dari device/browser mana pun (daftar di desktop,
     * buka email di HP) — sebelumnya gagal karena butuh sesi login di browser tsb.
     *
     * Setelah aktivasi: logout sesi apa pun lalu arahkan ke halaman login
     * dengan flag pesan (?activated=1 baru aktif / ?active=1 sudah aktif).
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        // User tidak ada / hash email tidak cocok -> tolak.
        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'Link aktivasi tidak valid.');
        }

        $justVerified = false;

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                // Memicu App\Listeners\GrantStarterOnVerified (bonus saldo Starter).
                event(new Verified($user));
                $justVerified = true;
            }
        }

        // Pastikan tidak ada sesi login yang menempel (mis. akun lain di browser ini).
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Pakai query param (bukan flash) agar pesan selamat dari session invalidate.
        $flag = $justVerified ? 'activated' : 'active';

        return redirect()->route('login', [$flag => 1]);
    }
}

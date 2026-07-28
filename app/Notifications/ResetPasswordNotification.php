<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email reset kata sandi versi branded Mooda (menggantikan template default Laravel).
 * URL reset tetap dibangun oleh parent (route password.reset).
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url    = $this->resetUrl($notifiable);
        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Reset Kata Sandi Mooda')
            ->view('emails.reset-password', [
                'url'   => $url,
                'user'  => $notifiable,
                'count' => $expire,
                'brand' => 'Mooda',
            ]);
    }
}

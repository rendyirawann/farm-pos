<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email verifikasi (link aktivasi) versi branded Mooda.
 * Memakai URL bertanda-tangan (signed) bawaan Laravel via verificationUrl().
 */
class VerifyAccountNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Aktivasi Akun Mooda Anda')
            ->view('emails.verify-account', [
                'url'  => $url,
                'user' => $notifiable,
            ]);
    }
}

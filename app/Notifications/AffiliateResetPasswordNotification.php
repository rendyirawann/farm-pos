<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email reset kata sandi untuk portal AFFILIATE (affiliate.mooda.id).
 * URL reset mengarah ke domain affiliate, memakai template branded yang sama.
 */
class AffiliateResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token, public string $email)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url    = 'https://affiliate.mooda.id/reset-password/' . $this->token . '?email=' . urlencode($this->email);
        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Reset Kata Sandi — Mooda Affiliate')
            ->view('emails.reset-password', [
                'url'   => $url,
                'user'  => $notifiable,
                'count' => $expire,
                'brand' => 'Mooda Affiliate',
            ]);
    }
}

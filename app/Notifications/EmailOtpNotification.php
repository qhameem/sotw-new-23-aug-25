<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $otp,
        protected int $expiresInMinutes = 15,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your sign-in code for '.config('app.name'))
            ->greeting('Continue to '.config('app.name'))
            ->line('Use this one-time code to securely sign in or create your account:')
            ->line($this->otp)
            ->line("This code expires in {$this->expiresInMinutes} minutes and can only be used once.")
            ->line('If you did not request this email, you can ignore it.');
    }

    public function otp(): string
    {
        return $this->otp;
    }
}

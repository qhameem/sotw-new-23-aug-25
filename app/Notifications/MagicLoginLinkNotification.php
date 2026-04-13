<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLoginLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $url,
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
            ->subject('Your sign-in link for '.config('app.name'))
            ->greeting('Sign in to '.config('app.name'))
            ->line('Use the button below to securely sign in or create your account.')
            ->action('Sign in with email', $this->url)
            ->line("This link expires in {$this->expiresInMinutes} minutes and can only be used once.")
            ->line('If you did not request this email, you can ignore it.');
    }

    public function url(): string
    {
        return $this->url;
    }
}

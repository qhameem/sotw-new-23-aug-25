<?php

use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Support\Facades\Notification;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('existing users can request a magic login link and sign in', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->post('/login', [
        'email' => $user->email,
        'intended' => url('/dashboard'),
    ])->assertSessionHas('status', 'magic-link-sent');

    $loginUrl = null;

    Notification::assertSentOnDemand(MagicLoginLinkNotification::class, function ($notification, $channels, $notifiable) use ($user, &$loginUrl) {
        $loginUrl = $notification->url();

        return $notifiable->routes['mail'] === $user->email;
    });

    $response = $this->get($loginUrl);

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect('/dashboard');
});

test('users can logout after magic-link authentication', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('magic link auth keeps sessions configured for 30 days', function () {
    expect(config('session.lifetime'))->toBe(43200);
    expect(config('auth.guards.web.remember'))->toBe(43200);
});

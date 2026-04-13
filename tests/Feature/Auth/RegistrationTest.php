<?php

use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can sign up with a magic link and complete their profile', function () {
    Notification::fake();

    $email = 'test@example.com';

    $this->post('/register', [
        'email' => $email,
    ])->assertSessionHas('status', 'magic-link-sent');

    $loginUrl = null;

    Notification::assertSentOnDemand(MagicLoginLinkNotification::class, function ($notification, $channels, $notifiable) use ($email, &$loginUrl) {
        $loginUrl = $notification->url();

        return $notifiable->routes['mail'] === $email;
    });

    $response = $this->get($loginUrl);

    $user = User::where('email', $email)->first();

    $this->assertAuthenticatedAs($user);
    expect($user)->not->toBeNull();
    expect($user->name)->toBeNull();
    expect($user->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('auth.complete-profile.show'));

    $this->actingAs($user)
        ->post(route('auth.complete-profile.store'), ['name' => 'Test User'])
        ->assertRedirect(route('home'));

    expect($user->fresh()->name)->toBe('Test User');
});

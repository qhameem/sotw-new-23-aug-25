<?php

use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can sign up with an email otp and complete their profile', function () {
    Notification::fake();

    $email = 'test@example.com';

    $this->post('/register', [
        'email' => $email,
    ])->assertSessionHas('status', 'otp-sent');

    $otp = null;

    Notification::assertSentOnDemand(EmailOtpNotification::class, function ($notification, $channels, $notifiable) use ($email, &$otp) {
        $otp = $notification->otp();

        return $notifiable->routes['mail'] === $email;
    });

    $response = $this->post(route('auth.email-otp.verify'), [
        'email' => $email,
        'otp' => $otp,
    ]);

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

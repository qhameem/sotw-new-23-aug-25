<?php

use App\Models\AuthMagicLink;
use App\Models\Ad;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('existing users can request an email otp and sign in', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->post('/login', [
        'email' => $user->email,
        'intended' => url('/dashboard'),
    ])->assertSessionHas('status', 'otp-sent');

    $otp = null;

    Notification::assertSentOnDemand(EmailOtpNotification::class, function ($notification, $channels, $notifiable) use ($user, &$otp) {
        $otp = $notification->otp();

        return $notifiable->routes['mail'] === $user->email;
    });

    $response = $this->post(route('auth.email-otp.verify'), [
        'email' => $user->email,
        'otp' => $otp,
    ]);

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect('/dashboard');
});

test('ad impression requests do not overwrite the intended url in session', function () {
    $ad = Ad::factory()->create();

    $this->withSession(['url.intended' => '/dashboard'])
        ->get(route('ads.impression', $ad), [
            'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
        ])
        ->assertOk();

    expect(session('url.intended'))->toBe('/dashboard');
});

test('google login start stores the requested intended url in session', function () {
    $driver = \Mockery::mock();
    $driver->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($driver);

    $response = $this->get(route('auth.google', ['intended' => url('/dashboard')]));

    expect(session('url.intended'))->toBe('/dashboard');
    $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

test('google login callback redirects authenticated users to their intended url', function () {
    $user = User::factory()->unverified()->create();

    $googleUser = \Mockery::mock();
    $googleUser->shouldReceive('getId')->once()->andReturn('google-user-123');
    $googleUser->shouldReceive('getEmail')->once()->andReturn($user->email);
    $googleUser->shouldReceive('getAvatar')->once()->andReturn('https://example.com/avatar.jpg');

    $driver = \Mockery::mock();
    $driver->shouldReceive('user')->once()->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($driver);

    $response = $this->withSession(['url.intended' => '/dashboard'])
        ->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->google_id)->toBe('google-user-123');
    expect($user->fresh()->google_avatar)->toBe('https://example.com/avatar.jpg');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect('/dashboard');
});

test('users can logout after email otp authentication', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('email otp auth keeps sessions configured for 30 days', function () {
    expect(config('session.lifetime'))->toBe(43200);
    expect(config('auth.guards.web.remember'))->toBe(43200);
});

test('email otp requests are throttled per ip across different email addresses', function () {
    Notification::fake();

    $ipAddress = '203.0.113.10';

    foreach (range(1, 10) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->post('/login', [
                'email' => "person{$attempt}@example.com",
            ])
            ->assertSessionHas('status', 'otp-sent');
    }

    $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
        ->post('/login', [
            'email' => 'person11@example.com',
        ])
        ->assertSessionHasErrors('email');

    Notification::assertSentOnDemandTimes(EmailOtpNotification::class, 10);
});

test('email otp requests ignore honeypot submissions', function () {
    Notification::fake();

    $email = 'botlike@example.com';

    $this->post('/login', [
        'email' => $email,
        'company_name' => 'Acme Inc',
    ])->assertSessionHas('status', 'otp-sent');

    Notification::assertNothingSent();
    expect(AuthMagicLink::where('email', $email)->exists())->toBeFalse();
});

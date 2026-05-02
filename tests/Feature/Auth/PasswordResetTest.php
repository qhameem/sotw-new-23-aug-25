<?php

test('forgot password routes redirect to the email-otp login flow', function () {
    $this->get('/forgot-password')
        ->assertRedirect(route('login'));

    $this->post('/forgot-password', ['email' => 'person@example.com'])
        ->assertRedirect(route('login'));
});

test('reset password routes redirect to the email-otp login flow', function () {
    $this->get('/reset-password/example-token')
        ->assertRedirect(route('login'));

    $this->post('/reset-password', [
        'token' => 'example-token',
        'email' => 'person@example.com',
    ])->assertRedirect(route('login'));
});

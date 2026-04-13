<?php

use App\Models\User;

test('confirm password screen redirects to profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertRedirect(route('profile.edit'));
});

test('confirm password post redirects without errors for passwordless flow', function () {
    $user = User::factory()->create(['password' => null]);

    $response = $this->actingAs($user)->post('/confirm-password');

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHasNoErrors();
});

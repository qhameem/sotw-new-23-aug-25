<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'public_handle' => 'test-user',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertSame('test-user', $user->public_handle);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'public_handle' => 'test-user',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('public handle must be unique and cannot use reserved words', function () {
    User::factory()->create(['public_handle' => 'existing-handle']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'public_handle' => 'admin',
        ])
        ->assertSessionHasErrors('public_handle')
        ->assertRedirect('/profile');

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'public_handle' => 'existing-handle',
        ])
        ->assertSessionHasErrors('public_handle')
        ->assertRedirect('/profile');
});

test('public handle input is normalized before saving', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'public_handle' => 'Test User Handle',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertSame('test-user-handle', $user->fresh()->public_handle);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

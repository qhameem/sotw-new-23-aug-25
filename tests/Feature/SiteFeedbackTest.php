<?php

use App\Mail\SiteFeedback;
use Illuminate\Support\Facades\Mail;

it('renders desktop and mobile feedback controls and the shared dialog', function () {
    $this->get(route('about'))->assertOk()
        ->assertSee('Share feedback')
        ->assertSee('feedback-message');
});

it('accepts guest feedback and sends it to the site contact', function () {
    Mail::fake();

    $this->postJson(route('feedback.store'), [
        'type' => 'bug',
        'message' => 'The category filter is not opening.',
        'email' => 'reader@example.com',
    ])->assertOk()->assertJson(['message' => 'Your feedback has been sent.']);

    Mail::assertSent(SiteFeedback::class, fn ($mail) => $mail->hasTo(config('agent_access.contact'))
        && $mail->contactEmail === 'reader@example.com'
        && $mail->feedbackMessage === 'The category filter is not opening.'
        && $mail->envelope()->replyTo[0]->address === 'reader@example.com'
    );
});

it('accepts anonymous feedback without an email', function () {
    Mail::fake();

    $this->postJson(route('feedback.store'), [
        'type' => 'suggestion', 'message' => 'Please add more category filters.',
    ])->assertOk();

    Mail::assertSent(SiteFeedback::class, fn ($mail) => $mail->contactEmail === null);
});

it('rejects invalid feedback and honeypot submissions', function () {
    Mail::fake();

    $this->postJson(route('feedback.store'), [
        'type' => 'invalid', 'message' => 'short', 'email' => 'invalid', 'website' => 'spam',
    ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'message', 'email', 'website']);

    Mail::assertNothingSent();
});

it('limits repeated feedback submissions', function () {
    Mail::fake();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('feedback.store'), [
            'type' => 'other', 'message' => 'Feedback message for rate limiting.',
        ])->assertOk();
    }

    $this->postJson(route('feedback.store'), [
        'type' => 'other', 'message' => 'One more feedback message.',
    ])->assertTooManyRequests();
});

it('saves authenticated user information from the session rather than submitted identity', function () {
    Mail::fake();
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)->postJson(route('feedback.store'), [
        'type' => 'suggestion', 'message' => 'Please improve the category navigation.',
        'user_id' => 999, 'user_name' => 'Forged identity',
        'email' => 'reply@example.com',
    ])->assertOk();

    $this->assertDatabaseHas('feedback', [
        'user_id' => $user->id, 'user_name' => $user->name,
        'user_email' => $user->email, 'contact_email' => 'reply@example.com',
        'type' => 'suggestion', 'message' => 'Please improve the category navigation.',
    ]);
});

it('keeps guest feedback when email delivery fails', function () {
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Mail unavailable'));

    $this->postJson(route('feedback.store'), [
        'type' => 'bug', 'message' => 'The search button does not respond.',
    ])->assertOk();

    $this->assertDatabaseHas('feedback', [
        'user_id' => null, 'user_name' => null, 'user_email' => null,
        'message' => 'The search button does not respond.',
    ]);
});

it('shows feedback and submitter details to admins', function () {
    Mail::fake();
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user)->postJson(route('feedback.store'), [
        'type' => 'other', 'message' => 'A useful feedback message for administrators.',
        'email' => 'contact@example.com',
    ])->assertOk();

    $admin = \App\Models\User::factory()->create();
    $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin'));

    $this->actingAs($admin)->get(route('admin.feedback.index'))->assertOk()
        ->assertSee('A useful feedback message for administrators.')
        ->assertSee($user->name)->assertSee($user->email)
        ->assertSee('contact@example.com')
        ->assertSee(route('admin.users.show', $user));
});

it('prevents guests and non-admins from reading feedback', function () {
    $this->get(route('admin.feedback.index'))->assertRedirect(route('login'));
    $this->actingAs(\App\Models\User::factory()->create())
        ->get(route('admin.feedback.index'))->assertForbidden();
});

it('preserves submitter details when their account is deleted', function () {
    Mail::fake();
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user)->postJson(route('feedback.store'), [
        'type' => 'other', 'message' => 'Please keep this feedback for review.',
    ])->assertOk();
    $user->delete();

    $this->assertDatabaseHas('feedback', [
        'user_id' => null, 'user_name' => $user->name, 'user_email' => $user->email,
    ]);
});

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

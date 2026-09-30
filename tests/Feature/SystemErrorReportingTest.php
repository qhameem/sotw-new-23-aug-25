<?php

use App\Http\Controllers\ProductController;
use App\Models\SystemErrorReport;
use App\Models\User;
use App\Notifications\SystemErrorReported;
use App\Services\SystemErrorReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole($role);
});

test('system errors are deduplicated, redacted, and notify admins once', function () {
    Notification::fake();
    $reporter = app(SystemErrorReporter::class);

    $reporter->report(
        'product-autofill.description',
        'AI Description generation failed',
        'Authorization: Bearer private-token',
        ['api_key' => 'private-key', 'status' => 429],
    );
    $reporter->report(
        'product-autofill.description',
        'AI Description generation failed',
        'Authorization: Bearer another-private-token',
        ['api_key' => 'another-private-key', 'status' => 429],
    );

    $report = SystemErrorReport::sole();

    expect($report->occurrence_count)->toBe(2)
        ->and($report->details)->toBe('Authorization: Bearer [REDACTED]')
        ->and($report->context['api_key'])->toBe('[REDACTED]')
        ->and($report->context['status'])->toBe(429);

    Notification::assertSentToTimes($this->admin, SystemErrorReported::class, 1);
});

test('non-admin AI failure message omits provider details', function () {
    Notification::fake();
    $controller = (new ReflectionClass(ProductController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(ProductController::class, 'buildAiAutofillNotice');
    $method->setAccessible(true);

    $message = $method->invoke($controller, [[
        'provider' => 'groq',
        'status' => 429,
        'body' => 'Quota exceeded for secret provider account.',
    ]], 'description', false);

    expect($message)->toBe('AI autofill is temporarily unavailable. Fallback content was added; you can continue editing.')
        ->and($message)->not->toContain('Groq', '429', 'Quota');
});

test('only admins can view and resolve system errors', function () {
    $report = SystemErrorReport::query()->create([
        'fingerprint' => hash('sha256', 'test'),
        'severity' => 'error',
        'source' => 'test',
        'summary' => 'Test failure',
        'occurrence_count' => 1,
        'first_occurred_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.errors.index'))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('admin.errors.index'))
        ->assertOk()
        ->assertSee('Test failure');

    $this->actingAs($this->admin)
        ->patch(route('admin.errors.resolve', $report))
        ->assertRedirect();

    expect($report->fresh()->resolved_at)->not->toBeNull();
});

<?php

namespace Tests\Feature;

use App\Models\ProductSubmissionDraft;
use App\Models\User;
use App\Services\ProductRegenerationLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductRegenerationLimiterTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_is_limited_to_three_regenerations_per_field_per_draft(): void
    {
        $user = User::factory()->create();
        $draft = ProductSubmissionDraft::create([
            'user_id' => $user->id,
            'link' => 'https://example.com',
            'payload' => ['link' => 'https://example.com'],
        ]);
        $limiter = app(ProductRegenerationLimiter::class);

        $this->assertSame(2, $limiter->consume($user, $draft->uuid, 'tagline')['remaining']);
        $this->assertSame(1, $limiter->consume($user, $draft->uuid, 'tagline')['remaining']);
        $this->assertSame(0, $limiter->consume($user, $draft->uuid, 'tagline')['remaining']);

        $this->expectException(ValidationException::class);
        $limiter->consume($user, $draft->uuid, 'tagline');
    }

    public function test_admin_regenerations_are_unlimited_and_not_recorded(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $draft = ProductSubmissionDraft::create([
            'user_id' => $admin->id,
            'link' => 'https://example.com',
            'payload' => ['link' => 'https://example.com'],
        ]);

        $result = app(ProductRegenerationLimiter::class)->consume($admin, $draft->uuid, 'description');

        $this->assertNull($result['remaining']);
        $this->assertSame([], $draft->fresh()->regeneration_counts ?? []);
    }
}

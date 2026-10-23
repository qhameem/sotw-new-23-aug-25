<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResponseCacheIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_keeps_admin_regular_user_and_guest_state_separate(): void
    {
        Product::factory()->create(['published_at' => now()->subDay()]);
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['name' => 'Private Admin Identity']);
        $admin->assignRole('admin');
        $regularUser = User::factory()->create(['name' => 'Private Member Identity']);

        $adminResponse = $this->actingAs($admin)->withSession(['_token' => str_repeat('a', 40)])->get('/');
        $adminResponse->assertOk()
            ->assertSee('data-is-authenticated="1"', false)
            ->assertSee('data-csrf-token="'.str_repeat('a', 40).'"', false)
            ->assertSee(route('admin.product-approvals.index'));

        $memberResponse = $this->actingAs($regularUser)->withSession(['_token' => str_repeat('b', 40)])->get('/');
        $memberResponse->assertOk()
            ->assertSee('data-csrf-token="'.str_repeat('b', 40).'"', false)
            ->assertDontSee(str_repeat('a', 40))
            ->assertDontSee(route('admin.product-approvals.index'))
            ->assertDontSee($admin->name);

        Auth::logout();
        $guestResponse = $this->get('/');
        $guestResponse->assertOk()
            ->assertSee('data-is-authenticated="0"', false)
            ->assertDontSee($regularUser->name)
            ->assertDontSee($admin->name)
            ->assertDontSee(route('admin.product-approvals.index'));

        foreach ([$adminResponse, $memberResponse, $guestResponse] as $response) {
            $this->assertTrue($response->headers->hasCacheControlDirective('private'));
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
            $this->assertFalse($response->headers->hasCacheControlDirective('public'));
            $response->assertHeader('X-LiteSpeed-Cache-Control', 'no-cache');
            $this->assertContains('Cookie', $response->getVary());
            $this->assertContains('Authorization', $response->getVary());
        }
    }

    public function test_json_login_redirects_and_errors_cannot_enter_a_shared_cache(): void
    {
        $responses = [
            $this->get('/login'),
            $this->get('/dashboard'),
            $this->getJson('/api/user'),
            $this->get('/this-page-does-not-exist'),
        ];

        foreach ($responses as $response) {
            $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
            $response->assertHeader('X-LiteSpeed-Cache-Control', 'no-cache');
        }
    }

    public function test_timing_listener_is_not_duplicated_across_requests(): void
    {
        Route::middleware('web')->get('/_performance/test', function () {
            DB::select('select 1');

            return response()->json(['ok' => true]);
        });
        $listenerCount = count(app('events')->getListeners(QueryExecuted::class));

        for ($run = 0; $run < 3; $run++) {
            $response = $this->get('/_performance/test')->assertOk();
            $this->assertMatchesRegularExpression('/db;dur=\d+\.\d;desc="1 queries"/', $response->headers->get('Server-Timing'));
        }

        $this->assertCount($listenerCount, app('events')->getListeners(QueryExecuted::class));
    }
}

<?php

use App\Models\SearchLog;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('tracks guest search activity with ip and location details', function () {
    $response = $this->post(route('api.search.log'), [
        'query' => 'AI note taker',
        'source' => 'global_search_modal',
    ], [
        'REMOTE_ADDR' => '198.51.100.20',
        'HTTP_CF_IPCOUNTRY' => 'US',
        'HTTP_CF_IPCITY' => 'New York',
    ]);

    $response->assertOk()
        ->assertJson([
            'tracked' => true,
        ]);

    $log = SearchLog::query()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->search_term)->toBe('AI note taker')
        ->and($log->user_id)->toBeNull()
        ->and($log->ip_address)->toBe('198.51.100.20')
        ->and($log->country_code)->toBe('US')
        ->and($log->country_name)->toBe('United States')
        ->and($log->city)->toBe('New York');
});

it('tracks authenticated search activity against the signed in user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('api.search.log'), [
        'query' => 'workflow automation',
        'source' => 'global_search_modal',
    ], [
        'REMOTE_ADDR' => '203.0.113.15',
        'HTTP_CF_IPCOUNTRY' => 'CA',
        'HTTP_CF_IPCITY' => 'Toronto',
    ]);

    $response->assertOk();

    $log = SearchLog::query()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->search_term)->toBe('workflow automation')
        ->and($log->country_code)->toBe('CA')
        ->and($log->city)->toBe('Toronto');
});

it('shows tracked searches on the admin search history page', function () {
    Role::firstOrCreate(['name' => 'admin']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $searchUser = User::factory()->create([
        'name' => 'Search Member',
        'email' => 'search-member@example.com',
    ]);

    SearchLog::query()->create([
        'user_id' => $searchUser->id,
        'search_term' => 'best ai crm',
        'source' => 'global_search_modal',
        'ip_address' => '198.51.100.40',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'city' => 'London',
        'user_agent' => 'Pest',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.search-history.index'));

    $response->assertOk()
        ->assertSee('Search History')
        ->assertSee('best ai crm')
        ->assertSee('Search Member')
        ->assertSee('London, United Kingdom');
});

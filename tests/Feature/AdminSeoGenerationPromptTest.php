<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $role = Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole($role);
});

test('admin can save and load category generation prompts', function () {
    Storage::disk('local')->put('settings.json', json_encode(['existing_setting' => true]));

    $this->actingAs($this->admin)
        ->postJson(route('api.seo.saveGenerationPrompts'), [
            'hub_description' => 'Use a concise editorial tone.',
            'meta_description' => 'Lead with buyer intent.',
        ])
        ->assertOk()
        ->assertJsonPath('data.hub_description', 'Use a concise editorial tone.')
        ->assertJsonPath('data.meta_description', 'Lead with buyer intent.');

    $this->actingAs($this->admin)
        ->getJson(route('api.seo.generationPrompts'))
        ->assertOk()
        ->assertExactJson([
            'hub_description' => 'Use a concise editorial tone.',
            'meta_description' => 'Lead with buyer intent.',
        ]);

    expect(json_decode(Storage::disk('local')->get('settings.json'), true)['existing_setting'])->toBeTrue();
});

test('non-admin cannot manage category generation prompts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.seo.generationPrompts'))
        ->assertForbidden();
});

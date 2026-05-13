<?php

namespace Tests\Feature;

use App\Models\Changelog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChangelogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_changelog_page_only_shows_new_entry_button_to_admins(): void
    {
        $entry = Changelog::create([
            'released_at' => '2026-05-13',
            'type' => 'added',
            'title' => 'Launch improvements',
        ]);

        $response = $this->get(route('changelog.index'));

        $response->assertOk();
        $response->assertDontSee('+ New Entry');
        $response->assertDontSee('new-changelog-entry');
        $response->assertDontSee('edit-changelog-entry-' . $entry->id);
        $response->assertDontSee('delete-changelog-entry-' . $entry->id);

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->get(route('changelog.index'));

        $response->assertOk();
        $response->assertSee('+ New Entry');
        $response->assertSee('Add changelog entry');
        $response->assertSee('new-changelog-entry');
        $response->assertSee('edit-changelog-entry-' . $entry->id);
        $response->assertSee('delete-changelog-entry-' . $entry->id);
        $response->assertSee($entry->title);
    }

    public function test_admin_can_create_a_changelog_entry_from_the_public_changelog_page(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->post(route('changelog.store'), [
            'released_at' => '2026-05-13',
            'type' => 'added',
            'title' => 'New editor workflow',
            'version' => 'v2.1.0',
            'description' => "First line\nSecond line",
        ]);

        $response->assertRedirect(route('changelog.index'));
        $response->assertSessionHas('success', 'Changelog entry created successfully.');

        $this->assertDatabaseHas('changelogs', [
            'type' => 'added',
            'title' => 'New editor workflow',
            'version' => 'v2.1.0',
            'description' => 'First line<br />' . "\n" . 'Second line',
        ]);

        $entry = Changelog::first();

        $this->assertNotNull($entry);
        $this->assertSame('New editor workflow', $entry->title);
        $this->assertSame('2026-05-13', $entry->released_at->toDateString());
    }

    public function test_admin_can_update_a_changelog_entry_from_the_public_changelog_page(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $entry = Changelog::create([
            'released_at' => '2026-05-13',
            'type' => 'added',
            'title' => 'Initial entry',
            'version' => 'v1.0.0',
            'description' => 'Old description',
        ]);

        $response = $this->actingAs($admin)->patch(route('changelog.update', $entry), [
            'released_at' => '2026-05-14',
            'type' => 'changed',
            'title' => 'Updated entry',
            'version' => 'v1.1.0',
            'description' => "Updated line one\nUpdated line two",
        ]);

        $response->assertRedirect(route('changelog.index'));
        $response->assertSessionHas('success', 'Changelog entry updated successfully.');

        $entry->refresh();

        $this->assertSame('Updated entry', $entry->title);
        $this->assertSame('changed', $entry->type);
        $this->assertSame('v1.1.0', $entry->version);
        $this->assertSame('2026-05-14', $entry->released_at->toDateString());
        $this->assertSame('Updated line one<br />' . "\n" . 'Updated line two', $entry->description);
    }

    public function test_admin_can_delete_a_changelog_entry_from_the_public_changelog_page(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $entry = Changelog::create([
            'released_at' => '2026-05-13',
            'type' => 'fixed',
            'title' => 'Entry to delete',
        ]);

        $response = $this->actingAs($admin)->delete(route('changelog.destroy', $entry));

        $response->assertRedirect(route('changelog.index'));
        $response->assertSessionHas('success', 'Changelog entry deleted successfully.');
        $this->assertDatabaseMissing('changelogs', [
            'id' => $entry->id,
        ]);
    }
}

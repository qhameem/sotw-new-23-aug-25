<?php

namespace Tests\Feature;

use App\Models\ProductDescriptionTemplate;
use App\Models\User;
use App\Support\ProductDescriptionTemplates;
use App\Services\DescriptionRewriterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductDescriptionTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_create_select_and_edit_description_templates(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.product-description-templates.index'))
            ->assertOk()
            ->assertSee(ProductDescriptionTemplates::DEFAULT_NAME);

        $this->actingAs($admin)->post(route('admin.settings.product-description-templates.store'), [
            'name' => 'Short overview',
            'instruction' => 'Write only a two-paragraph overview in a neutral style.',
        ])->assertRedirect(route('admin.settings.product-description-templates.index'));

        $shortTemplate = ProductDescriptionTemplate::query()->where('name', 'Short overview')->firstOrFail();

        $this->assertTrue($shortTemplate->is_active);
        $this->assertSame(2, ProductDescriptionTemplate::query()->count());
        $this->assertSame($shortTemplate->instruction, app(ProductDescriptionTemplates::class)->activeInstruction());

        $this->actingAs($admin)->post(route('admin.settings.product-description-templates.store'), [
            'template_id' => $shortTemplate->id,
            'name' => 'Short direct overview',
            'instruction' => 'Write one direct overview paragraph.',
        ])->assertRedirect(route('admin.settings.product-description-templates.index'));

        $this->assertDatabaseHas('product_description_templates', [
            'id' => $shortTemplate->id,
            'name' => 'Short direct overview',
            'instruction' => 'Write one direct overview paragraph.',
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_description_template_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.product-description-templates.index'))
            ->assertForbidden();
    }

    public function test_custom_instruction_allows_a_short_html_description(): void
    {
        $method = new \ReflectionMethod(DescriptionRewriterService::class, 'cleanHtmlResponse');
        $html = '<p>A concise product overview.</p>';

        $this->assertSame($html, $method->invoke(new DescriptionRewriterService(), $html, true));
        $this->assertNull($method->invoke(new DescriptionRewriterService(), $html, false));
    }

    private function createAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_file_documents_real_routes(): void
    {
        $this->get('/llms.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee(route('alternatives.index'), false)
            ->assertSee('/document.json', false)
            ->assertSee('schema_version 1', false);
    }

    public function test_documents_use_public_fields_without_changing_records(): void
    {
        $product = Product::factory()->create([
            'name' => 'Agent Product',
            'description' => '<h2>Features</h2><p>Useful <strong>software</strong>.</p><script>secretScript()</script>',
            'hosting_details' => [],
            'proposed_description' => 'unreviewed edit',
        ]);
        $before = $product->fresh()->getRawOriginal();
        $url = '/product/'.$product->slug.'/document.';
        $json = $this->get($url.'json')->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=UTF-8')
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertHeader('Link', '<'.route('products.show', $product->slug).'>; rel="canonical"')
            ->assertJsonPath('schema_version', 1)
            ->assertJsonPath('name', 'Agent Product')
            ->assertDontSee('private hosting password')->assertDontSee('unreviewed edit')
            ->assertDontSee('secretScript');
        $this->assertSame([
            'schema_version', 'name', 'slug', 'url', 'website', 'tagline',
            'description_markdown', 'categories', 'published_at', 'updated_at',
        ], array_keys($json->json()));
        $this->get($url.'md')->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('X-LiteSpeed-Cache-Control', 'no-cache')
            ->assertSee('# Agent Product', false)->assertSee('## Features', false)
            ->assertDontSee('<script', false);
        $this->assertSame($before, $product->fresh()->getRawOriginal());
        $product->update(['product_page_tagline' => 'Updated published tagline']);
        $this->get($url.'json')->assertJsonPath('tagline', 'Updated published tagline');
        $guestDocument = $this->get($url.'json')->getContent();
        $this->actingAs($product->user);
        $this->assertSame($guestDocument, $this->get($url.'json')->getContent());
        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertSee('type="text/markdown"', false)
            ->assertSee(route('products.document', ['slug' => $product->slug, 'format' => 'json']), false)
            ->assertSee('>llms.txt</a>', false);
    }

    public function test_private_products_are_hidden_even_from_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([['approved' => false], ['is_published' => false], ['generation_noindex' => true], ['generation_status' => 'draft']] as $attributes) {
            $product = Product::factory()->create($attributes);
            foreach (['md', 'json'] as $format) {
                $this->get('/product/'.$product->slug.'/document.'.$format)->assertNotFound();
            }
        }
        $this->get('/product/missing-product/document.json')->assertNotFound();
        $this->get('/product/missing-product/document.xml')->assertNotFound();
    }

    public function test_fact_format_and_unsafe_website_handling(): void
    {
        $product = Product::factory()->create([
            'description_format' => 'facts',
            'product_facts' => ['A published fact', 'Another published fact'],
            'description' => 'Obsolete description',
            'link' => 'javascript:alert(1)',
        ]);
        $this->get('/product/'.$product->slug.'/document.json')->assertOk()
            ->assertJsonPath('website', null)
            ->assertSee('A published fact')->assertDontSee('Obsolete description');
    }
}

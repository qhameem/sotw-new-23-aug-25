<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyProductRedirectTest extends TestCase
{
    public function test_verified_legacy_pages_redirect_permanently_before_product_binding(): void
    {
        foreach (config('legacy_redirects') as $source => $destination) {
            $this->get($source)
                ->assertStatus(301)
                ->assertRedirect($destination);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductMediaValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_and_update_validate_media_slots_without_throwing(): void
    {
        $owner = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);
        $this->actingAs($owner);

        foreach (['post' => route('products.store'), 'put' => route('products.update', $product)] as $method => $url) {
            foreach ([0, 1] as $slot) {
                foreach (['url', 'file', 'conflict', 'separate', 'empty'] as $scenario) {
                    // Missing tagline stops persistence after exercising media validation.
                    $payload = [];
                    if (in_array($scenario, ['url', 'conflict', 'separate'], true)) {
                        $payload['media_urls'][$slot] = 'https://example.com/screenshot.png';
                    }
                    if (in_array($scenario, ['file', 'conflict', 'separate'], true)) {
                        $fileSlot = $scenario === 'separate' ? 1 - $slot : $slot;
                        $payload['media'][$fileSlot] = UploadedFile::fake()->image('screenshot.png');
                    }
                    if ($scenario === 'empty') {
                        $payload['media_urls'][$slot] = '';
                    }

                    $response = $this->{$method.'Json'}($url, $payload);
                    $response->assertUnprocessable()->assertJsonValidationErrors('tagline');

                    if ($scenario === 'conflict') {
                        $response->assertJsonValidationErrors("media_urls.$slot");
                    } else {
                        $response->assertJsonMissingValidationErrors(['media_urls.0', 'media_urls.1', 'media.0', 'media.1']);
                    }
                }
            }
        }
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AnalyticsService;
use Mockery;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_a_successful_response_when_analytics_is_configured()
    {
        // Mock the AnalyticsService to avoid making a real API call
        $this->mock(AnalyticsService::class, function ($mock) {
            $mock->shouldReceive('getStatsForCurrentYear')
                ->andReturn(['sessions' => 100, 'screenPageViews' => 200]);
        });

        $response = $this->getJson('/api/analytics/total-sessions');

        $response->assertStatus(200)
            ->assertJson([
                'sessions' => 100,
                'screenPageViews' => 200,
            ]);
    }

    /** @test */
    public function it_returns_an_error_response_when_analytics_is_not_configured()
    {
        // Mock the AnalyticsService to simulate the unconfigured state
        $this->mock(AnalyticsService::class, function ($mock) {
            $mock->shouldReceive('getStatsForCurrentYear')
                ->andReturn(['error' => 'Google Analytics is not configured.']);
        });

        $response = $this->getJson('/api/analytics/total-sessions');

        $response->assertStatus(200)
            ->assertJson([
                'error' => 'Google Analytics is not configured.',
            ]);
    }
}
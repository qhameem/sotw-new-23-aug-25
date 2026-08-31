<?php

namespace Tests\Unit;

use App\Support\ExtractionTimings;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExtractionTimingsTest extends TestCase
{
    public function test_failed_work_is_timed_and_request_state_is_isolated(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => new class
        {
            public function hasRole(string $role): bool
            {
                return $role === 'admin';
            }
        });
        $timings = ExtractionTimings::forRequest($request);
        $this->assertSame($timings, ExtractionTimings::forRequest($request));
        $this->assertNotSame($timings, ExtractionTimings::forRequest(Request::create('/')));
        $this->assertSame('logo.png', $timings->measure('logo', fn () => 'logo.png'));
        try {
            $timings->measure('description', fn () => throw new RuntimeException('Provider unavailable'));
            $this->fail('The original error must propagate.');
        } catch (RuntimeException $error) {
            $this->assertSame('Provider unavailable', $error->getMessage());
        }
        $payload = $timings->payload($request);
        $this->assertArrayHasKey('description', $payload['phase_timings']);
        $this->assertSame($payload, $timings->payload($request));
        $this->assertSame([], $timings->payload(Request::create('/')));
    }
}

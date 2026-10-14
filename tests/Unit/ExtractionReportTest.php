<?php

namespace Tests\Unit;

use App\Support\ExtractionReport;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExtractionReportTest extends TestCase
{
    private function requestFor(?string $role): Request
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $role === null ? null : new class($role)
        {
            public function __construct(private string $role) {}

            public function hasRole(string $role): bool
            {
                return $this->role === $role;
            }
        });

        return $request;
    }

    public function test_admin_receives_ordered_stream_events_and_complete_report_after_failure(): void
    {
        $request = $this->requestFor('admin');
        $report = ExtractionReport::forRequest($request);
        $events = [];
        $report->listen(function ($entry) use (&$events) {
            $events[] = $entry;
        });
        $report->measure('metadata', fn () => 'html');
        try {
            $report->measure('provider', fn () => throw new RuntimeException('secret API key'));
            $this->fail('Exception must propagate.');
        } catch (RuntimeException) {
        }
        $this->assertSame([1, 2, 3, 4], array_column($events, 'id'));
        $this->assertSame('error', $events[3]['level']);
        $this->assertStringNotContainsString('secret API key', $events[3]['message']);
        $this->assertSame($events, $report->payload($request)['extraction_report']);
        $this->assertSame($report, ExtractionReport::forRequest($request));
        $this->assertNotSame($report, ExtractionReport::forRequest($this->requestFor('admin')));
    }

    public function test_non_admin_and_guest_receive_no_report_or_stream_events(): void
    {
        foreach (['user', null] as $role) {
            $request = $this->requestFor($role);
            $report = ExtractionReport::forRequest($request);
            $report->listen(fn () => $this->fail('Non-admin event leaked.'));
            $this->assertSame('html', $report->measure('metadata', fn () => 'html'));
            $this->assertSame([], $report->payload($request));
            $this->assertSame([], $report->payload($this->requestFor('admin'))['extraction_report']);
        }
    }
}

<?php

namespace Tests\Unit;

use App\Services\PricingEvidenceService;
use PHPUnit\Framework\TestCase;

class PricingEvidenceServiceTest extends TestCase
{
    public function test_monthly_paid_cards_and_free_mode_support_both_subscription_and_freemium(): void
    {
        $service = new PricingEvidenceService;
        $source = "Source: https://example.com/pricing\nFree Mode. No subscription for the free tier. Starter $9.99/month. Pro $29.99/month. Paid plans remove ads.";
        $evidence = $service->extract($source);
        $this->assertCount(2, $service->supportingQuotes('Subscription', $evidence));
        $this->assertCount(2, $service->supportingQuotes('Monthly Subscription', $evidence));
        $this->assertNotEmpty($service->supportingQuotes('Freemium', $evidence));
        $this->assertSame('$9.99/month', $evidence['monthly_subscription'][0]['quote']);
        $this->assertSame('https://example.com/pricing', $evidence['monthly_subscription'][0]['source_url']);
        $this->assertStringContainsString('No subscription for the free tier', $evidence['monthly_subscription'][0]['context']);
    }

    public function test_trials_zero_prices_and_checkout_do_not_establish_subscription_or_freemium(): void
    {
        $service = new PricingEvidenceService;
        $evidence = $service->extract('Free trial. $0/month. Self-serve checkout. Paid plans available.');
        $this->assertSame([], $service->supportingQuotes('Subscription', $evidence));
        $this->assertSame([], $service->supportingQuotes('Freemium', $evidence));
        $this->assertSame([], $service->supportingQuotes('Unknown category', $evidence));
    }

    public function test_yearly_billing_is_distinct_from_monthly_billing_and_utf8_context_remains_valid(): void
    {
        $service = new PricingEvidenceService;
        $source = str_repeat('界', 29).'Pro EUR 120 per year. Paid plans with free tier.';
        $evidence = $service->extract($source);
        $this->assertCount(1, $service->supportingQuotes('Yearly Subscription', $evidence));
        $this->assertSame([], $service->supportingQuotes('Monthly Subscription', $evidence));
        $this->assertNotFalse(json_encode($evidence));
    }
}

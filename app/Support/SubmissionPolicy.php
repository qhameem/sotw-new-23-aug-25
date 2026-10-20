<?php

namespace App\Support;

class SubmissionPolicy
{
    public static function sections(): array
    {
        return [
            'Account and product details' => 'Sign in or create an account to submit and manage a product. Submit a software product with a working website, accurate details, a logo, and relevant categories. Choose the closest category or suggest one in the form. Questions before registering? Email hello@softwareontheweb.com.',
            'Free Launch — $0 with a badge' => 'The public Free Launch option requires our badge and a link back to Software on the Web on a public page of your website. Verify that page in the form and select an available launch date. Keep the badge live after launch; failed checks can block publication or unpublish the listing. This option has no monetary fee, but it is not a badge-free listing.',
            'Premium Launch — '.PremiumLaunchPricing::display().' USD, one-time' => self::premiumSummary(),
            'Approval and publication' => 'Verified badge submissions and completed Premium Launch payments are automatically approved for scheduling; a listing does not mean an individual expert review or endorsement. Incomplete generated content may still require review. Available dates appear in the form. Scheduled launches publish at '.ProductPublishSchedule::getPublishTime().' UTC on the selected date.',
            'Listing retention' => 'Listings are intended to remain available long term, subject to our Terms of Use and applicable submission conditions. We may remove content, and badge listings must continue to pass verification.',
        ];
    }

    public static function premiumSummary(): string
    {
        return 'Premium Launch includes a scheduled listing and a dofollow link to your product, without a badge requirement. Payment does not guarantee an organic ranking, traffic, sales, or an editorial endorsement. For cancellation or refund questions, contact hello@softwareontheweb.com before paying.';
    }
}

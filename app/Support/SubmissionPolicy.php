<?php

namespace App\Support;

class SubmissionPolicy
{
    public static function sections(): array
    {
        return [
            'Account and product details' => 'Sign in or create an account to submit and manage a product. Submit a software product with a working website, accurate details, a logo, and relevant categories. Choose the closest category or suggest one in the form. Questions before registering? Email hello@softwareontheweb.com.',
            'Free Launch — $0 with a badge' => 'The public Free Launch option requires our badge and a link back to Software on the Web on a publicly accessible page of your website; homepage placement is encouraged. Verify that page in the form and select an available launch date. Keep the badge live after launch. If you remove it, the system automatically removes your listing from public view. Failed verification also blocks publication. This option has no monetary fee, but it is not a badge-free listing.',
            'Premium Launch — '.PremiumLaunchPricing::display().' USD, one-time' => self::premiumSummary(),
            'Approval and publication' => 'Our team handpicks and manually reviews every tool as part of weekly curation, including automatically approved submissions. Verified badges and completed Premium Launch payments allow automatic scheduling, but the team may withhold or remove unsuitable products. Available dates appear in the form. Scheduled launches publish at '.ProductPublishSchedule::getPublishTime().' UTC on the selected date.',
            'Listing retention' => 'Listings are permanent, subject to manual suitability review and our Terms of Use. Badge listings remain permanent only while the verified badge stays live; removing it automatically removes the listing from public view. Paid listings do not require a badge.',
        ];
    }

    public static function premiumSummary(): string
    {
        return 'Premium Launch includes a scheduled permanent listing and a dofollow link to your product, without a badge requirement, subject to manual suitability review and our Terms of Use. Payment does not guarantee an organic ranking, traffic, sales, or an editorial endorsement. Refunds are generally unavailable after publication or delivery of paid benefits. Exceptions cover duplicate or accidental charges, failed delivery, and rejection before delivery. Read the Refund & Cancellation Policy before paying.';
    }
}

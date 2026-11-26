<?php

namespace App\Services;

class StartupDirectoryArchiveCategories
{
    public const BY_NAME = [
        'Google Business Profile' => 'business_listings',
        'Bing Places' => 'business_listings',
        'Yelp' => 'business_listings',
        'Manta' => 'business_listings',
        'Hotfrog' => 'business_listings',
        'Brownbook' => 'business_listings',
        'EZLocal' => 'business_listings',
        'Show Me Local' => 'business_listings',
        'Indian Yellow Pages' => 'business_listings',
        'Pro Business Directory' => 'business_listings',
        'AlternativeTo' => 'product_alternatives',
        'Alternative.me' => 'product_alternatives',
        'EuroAlternative' => 'product_alternatives',
        'Startup Alternatives' => 'product_alternatives',
        'Alternative.tools' => 'product_alternatives',
        'Open Source Alternatives' => 'product_alternatives',
        'OpenAlternative' => 'product_alternatives',
        'OpenSourceAlternative.to' => 'product_alternatives',
        'Alternatives' => 'product_alternatives',
        'Find Your Newsletter' => 'newsletter_directories',
        'Inboxreads' => 'newsletter_directories',
        'Newsletter Stack' => 'newsletter_directories',
        'Newsletterest' => 'newsletter_directories',
        'Newsletterhunt' => 'newsletter_directories',
        'Newsletters' => 'newsletter_directories',
        'Pigeon Newsletters' => 'newsletter_directories',
        'Chrome Web Store' => 'app_stores',
        'Firefox Add-ons' => 'app_stores',
        'Notion Marketplace' => 'app_stores',
        'Slack App Directory' => 'app_stores',
        'Zapier App Directory' => 'app_stores',
        'Make App Directory' => 'app_stores',
        'n8n Integrations' => 'app_stores',
        'WordPress Plugin Directory' => 'app_stores',
        'Shopify App Store' => 'app_stores',
        'Stripe App Marketplace' => 'app_stores',
        'Appexchange Salesforce' => 'app_stores',
        'Pipedrive Marketplace' => 'app_stores',
        'Crunchbase' => 'startup_databases',
        'Tracxn' => 'startup_databases',
    ];
}

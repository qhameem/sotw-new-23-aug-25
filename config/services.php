<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'google' => [
        'api_key' => env('GOOGLE_API_KEY'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'builtwith' => [
        'api_key' => env('BUILTWITH_API_KEY'),
    ],

    'wappalyzer' => [
        'api_key' => env('WAPPALYZER_API_KEY'),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
    ],

    'screenshot' => [
        'providers' => array_values(array_filter(array_map(
            static fn (string $provider): string => trim(strtolower($provider)),
            explode(',', (string) env('SCREENSHOT_PROVIDERS', 'snaprender,screenshotbase,apiflash,screenshotone,microlink'))
        ))),
        'timeout' => (int) env('SCREENSHOT_TIMEOUT', 30),
        'width' => (int) env('SCREENSHOT_WIDTH', 1440),
        'height' => (int) env('SCREENSHOT_HEIGHT', 900),
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        'home' => env('PUPPETEER_HOME'),
        'cache_dir' => env('PUPPETEER_CACHE_DIR'),
        'apiflash' => [
            'base_url' => env('APIFLASH_BASE_URL', 'https://api.apiflash.com/v1/urltoimage'),
            'access_key' => env('APIFLASH_ACCESS_KEY'),
            'free_limit' => (int) env('APIFLASH_FREE_LIMIT', 100),
            'free_period' => env('APIFLASH_FREE_PERIOD', 'monthly'),
            'weight' => (int) env('APIFLASH_WEIGHT', (int) env('APIFLASH_FREE_LIMIT', 100)),
        ],
        'screenshotone' => [
            'base_url' => env('SCREENSHOTONE_BASE_URL', 'https://api.screenshotone.com/take'),
            'access_key' => env('SCREENSHOTONE_ACCESS_KEY'),
            'signing_key' => env('SCREENSHOTONE_SIGNING_KEY'),
            'free_limit' => (int) env('SCREENSHOTONE_FREE_LIMIT', 100),
            'free_period' => env('SCREENSHOTONE_FREE_PERIOD', 'monthly'),
            'weight' => (int) env('SCREENSHOTONE_WEIGHT', (int) env('SCREENSHOTONE_FREE_LIMIT', 100)),
        ],
        'snaprender' => [
            'base_url' => env('SNAPRENDER_BASE_URL', 'https://app.snap-render.com/v1/screenshot'),
            'api_key' => env('SNAPRENDER_API_KEY'),
            'free_limit' => (int) env('SNAPRENDER_FREE_LIMIT', 500),
            'free_period' => env('SNAPRENDER_FREE_PERIOD', 'monthly'),
            'weight' => (int) env('SNAPRENDER_WEIGHT', (int) env('SNAPRENDER_FREE_LIMIT', 500)),
        ],
        'microlink' => [
            'base_url' => env('MICROLINK_BASE_URL', 'https://api.microlink.io'),
            'api_key' => env('MICROLINK_API_KEY'),
            'free_limit' => (int) env('MICROLINK_FREE_LIMIT', 50),
            'free_period' => env('MICROLINK_FREE_PERIOD', 'daily'),
            'free_no_key' => filter_var(env('MICROLINK_FREE_NO_KEY', true), FILTER_VALIDATE_BOOL),
            'weight' => (int) env('MICROLINK_WEIGHT', (int) env('MICROLINK_FREE_LIMIT', 50)),
        ],
        'screenshotbase' => [
            'base_url' => env('SCREENSHOTBASE_BASE_URL', 'https://api.screenshotbase.com/v1'),
            'api_key' => env('SCREENSHOTBASE_API_KEY'),
            'free_limit' => (int) env('SCREENSHOTBASE_FREE_LIMIT', 300),
            'free_period' => env('SCREENSHOTBASE_FREE_PERIOD', 'monthly'),
            'weight' => (int) env('SCREENSHOTBASE_WEIGHT', (int) env('SCREENSHOTBASE_FREE_LIMIT', 300)),
        ],
    ],

];

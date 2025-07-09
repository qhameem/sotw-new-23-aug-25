<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy API Key
    |--------------------------------------------------------------------------
    |
    | The API key for your Lemon Squeezy store. This can be found in your
    | Lemon Squeezy dashboard under Settings > API.
    |
    */
    'api_key' => env('LEMONSQUEEZY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy Store ID
    |--------------------------------------------------------------------------
    |
    | The ID of your Lemon Squeezy store. This can be found in your Lemon
    | Squeezy dashboard. It is the first part of your store's URL.
    | e.g. https://my-store.lemonsqueezy.com -> my-store
    |
    */
    'store_id' => env('LEMONSQUEEZY_STORE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The webhook signing secret for your Lemon Squeezy store. This can be
    | found in your Lemon Squeezy dashboard under Settings > Webhooks.
    |
    */
    'webhook_secret' => env('LEMONSQUEEZY_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | The path to handle Lemon Squeezy webhooks.
    |
    */
    'path' => 'lemon-squeezy/webhook',
];
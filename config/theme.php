<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Site Theme Settings
    |--------------------------------------------------------------------------
    |
    | This file is for storing theme-related settings.
    | Default values can be overridden by .env variables.
    | For admin-configurable settings, these values might be further
    | overridden at runtime by AppServiceProvider reading from a JSON file.
    |
    */

    'font_url' => env('THEME_FONT_URL', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap'),

    'font_family' => env('THEME_FONT_FAMILY', 'Roboto'),

    'primary_color' => env('THEME_PRIMARY_COLOR', 'blue-500'), // Default to a Tailwind blue

    'logo_url' => null,
    'logo_alt_text' => null,
    'favicon_url' => null,
    'primary_button_text_color' => null, // Default, will be determined or overridden
];
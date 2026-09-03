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

    'font_url' => env('THEME_FONT_URL', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap'),

    'font_family' => env('THEME_FONT_FAMILY', 'Inter'),

    'font_color' => env('THEME_FONT_COLOR', '#111827'),
    'body_text_color' => env('THEME_BODY_TEXT_COLOR', '#4b5563'),

    'primary_color' => env('THEME_PRIMARY_COLOR', 'blue-500'), // Default to a Tailwind blue

    'logo_url' => null,
    'logo_alt_text' => null,
    'favicon_url' => null,
    'favicon_manifest_url' => null,
    'primary_button_text_color' => null, // Default, will be determined or overridden

    'navbar_bg_color' => env('THEME_NAVBAR_BG_COLOR', '#ffffff'),
    'body_bg_color'   => env('THEME_BODY_BG_COLOR', '#ffffff'),
    'product_hover_color' => env('THEME_PRODUCT_HOVER_COLOR', '#f9fafb'),

    'dark_navbar_bg_color' => env('THEME_DARK_NAVBAR_BG_COLOR', '#111827'),
    'dark_body_bg_color' => env('THEME_DARK_BODY_BG_COLOR', '#0b1120'),
    'dark_surface_color' => env('THEME_DARK_SURFACE_COLOR', '#111827'),
    'dark_muted_surface_color' => env('THEME_DARK_MUTED_SURFACE_COLOR', '#1e293b'),
    'dark_border_color' => env('THEME_DARK_BORDER_COLOR', '#334155'),
    'dark_font_color' => env('THEME_DARK_FONT_COLOR', '#f8fafc'),
    'dark_body_text_color' => env('THEME_DARK_BODY_TEXT_COLOR', '#cbd5e1'),
    'dark_product_hover_color' => env('THEME_DARK_PRODUCT_HOVER_COLOR', '#1e293b'),
];

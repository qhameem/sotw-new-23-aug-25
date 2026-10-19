<?php

return [
    'banned_words' => ['streamline', 'optimize', 'elevate', 'supercharge', 'transform', 'seamless', 'effortless', 'revolutionary', 'best-in-class', 'all-in-one', 'leverage', 'robust', 'innovative'],
    'fact_cache_minutes' => env('LISTING_FACT_CACHE_MINUTES', 1440),
    'structured_models' => [
        'groq' => ['openai/gpt-oss-120b', 'openai/gpt-oss-20b'],
        'cerebras' => [],
    ],
    'provider_order' => [
        'facts' => ['cloudflare', 'groq', 'gemini', 'openrouter', 'cerebras'],
        'classification' => ['cloudflare', 'groq', 'gemini', 'openrouter', 'cerebras'],
        'copy' => ['gemini', 'groq', 'cloudflare', 'openrouter', 'cerebras'],
        'seo' => ['gemini', 'groq', 'cloudflare', 'openrouter', 'cerebras'],
        'default' => ['gemini', 'groq', 'cloudflare', 'openrouter', 'cerebras'],
    ],
];

<?php

return [
    'log_paths' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AI_CRAWLER_LOG_PATHS', '/usr/local/lsws/logs/access.log'))
    ))),

    'bots' => [
        'OpenAI GPTBot' => ['GPTBot'],
        'OpenAI SearchBot' => ['OAI-SearchBot'],
        'ChatGPT User' => ['ChatGPT-User'],
        'Anthropic ClaudeBot' => ['ClaudeBot', 'Claude-Web'],
        'PerplexityBot' => ['PerplexityBot', 'Perplexity-User'],
        'ByteDance Bytespider' => ['Bytespider'],
        'Google Cloud Vertex AI' => ['Google-CloudVertexBot'],
        'Meta External Agent' => ['meta-externalagent'],
        'Applebot Extended' => ['Applebot-Extended'],
        'Amazonbot' => ['Amazonbot'],
        'YouBot' => ['YouBot'],
        'Diffbot' => ['Diffbot'],
        'CCBot' => ['CCBot'],
    ],

    'header_cache_seconds' => (int) env('HEADER_STATS_CACHE_SECONDS', 900),
];

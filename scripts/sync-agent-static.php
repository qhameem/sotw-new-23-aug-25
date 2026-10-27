<?php

// Development-only: transcribe static Blade source into Markdown templates, without rendering HTML pages.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$sources = [
    'get-the-badge' => 'site/badges/index',
    'about' => 'site/about', 'faq' => 'site/faq', 'legal' => 'site/legal',
    'refund-policy' => 'site/refund-policy', 'software-review' => 'site/software-review',
    'premium-spot-details' => 'premium-spot/details', 'todo' => 'todolists/partials/content-section',
];
$hashes = [];
foreach ($sources as $key => $source) {
    $path = 'resources/views/'.$source.'.blade.php';
    $html = file_get_contents(base_path($path));
    $hashes[$path] = hash('sha256', $html);
    if (str_contains($html, "@section('content')")) {
        $html = explode("@section('content')", $html, 2)[1];
        $html = explode('@endsection', $html, 2)[0];
    }
    $html = preg_replace('/@php\b.*?@endphp/s', '', $html);
    $html = preg_replace('/<p[^>]*id="copy-badge-code-feedback"[^>]*>.*?<\/p>/s', '', $html);
    $html = preg_replace('/^\s*@section.*$/m', '', $html);
    $html = preg_replace('/<h1\b[^>]*>.*?<\/h1>/s', '', $html);
    $html = preg_replace('/<button\b[^>]*>.*?<\/button>/s', '', $html);
    $html = preg_replace('/<x-submission-policy[^>]*\/>/', '', $html);
    $html = preg_replace('/\{\{--.*?--\}\}|<!--.*?-->/s', '', $html);
    $html = str_replace('{!! nl2br(e($defaultSnippet)) !!}', '{{ $defaultSnippet }}', $html);
    $tokens = [];
    $html = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', function ($m) use (&$tokens) {
        $token = 'AGENTEXPR'.count($tokens).'TOKEN';
        $tokens[$token] = $m[1];

        return $token;
    }, $html);
    $html = preg_replace('/^\s*@(?:include|if|endif|else|php|endphp|foreach|endforeach).*$/m', '', $html);
    $markdown = app(App\Services\ProductPageMarkdown::class)->convert($html, 'https://agent-source.invalid/'.($key === 'todo' ? 'tools/todo-list-app' : $key));
    foreach ($tokens as $token => $expr) {
        $markdown = str_replace('https://agent-source.invalid/'.$token, '{!! '.$expr.' !!}', $markdown);
        if (in_array($expr, ['$defaultSnippet', '$smallSnippet', '$largeSnippet'], true)) {
            $markdown = str_replace($token, '{!! '.$expr.' !!}', $markdown);
        }
        $markdown = str_replace($token, '{!! app(\\App\\Services\\Markdown\\Format::class)->escape('.$expr.') !!}', $markdown);
    }
    $markdown = str_replace('https://agent-source.invalid/', "{!! rtrim(config('app.url'), '/').'/' !!}", $markdown);
    $markdown = preg_replace('/^ +/m', '', $markdown);
    $markdown = preg_replace('/\n\s*\n\s*\n/', "\n\n", $markdown);
    file_put_contents(resource_path('markdown/'.$key.'.blade.php'), $markdown."\n");
}
file_put_contents(resource_path('markdown/sources.json'), json_encode($hashes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

$items = collect([
    ['Orbit Analytics', 'Privacy-first analytics for growing teams', 'scheduled', 'Verified', ['Analytics', 'Marketing', 'SaaS', 'Reporting', 'Data']],
    ['Frame Studio', 'Collaborative design, from idea to handoff', 'scheduled', 'Pending', ['Design', 'Collaboration', 'Prototyping', 'AI']],
    ['Relay Inbox', 'One inbox for every customer conversation', 'scheduled', 'Failed', ['Support', 'CRM', 'Productivity', 'Email']],
    ['Stack Notes', 'Turn scattered notes into searchable knowledge', 'scheduled', 'Verified', ['Productivity', 'Notes']],
    ['Northstar', 'Project planning that keeps teams aligned', 'published', 'Verified', ['Projects', 'Teams', 'Planning', 'Remote']],
    ['Clear Ledger', 'Simple bookkeeping for independent businesses', 'published', null, ['Finance', 'Accounting']],
])->map(function ($data, $index) {
    [$name, $tagline, $state, $badge, $categories] = $data;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><rect width="32" height="32" rx="8" fill="#4f46e5"/><text x="16" y="22" text-anchor="middle" fill="white" font-family="sans-serif" font-size="18">'.substr($name, 0, 1).'</text></svg>';
    $product = new Product([
        'name' => $name, 'tagline' => $tagline, 'slug' => Illuminate\Support\Str::slug($name),
        'link' => 'https://example.com/'.Illuminate\Support\Str::slug($name),
        'logo' => 'https://example.com/logo.svg', 'created_at' => '2026-09-29 10:00:00', 'approved' => true,
        'is_published' => $state === 'published',
        'published_at' => $state === 'published' ? '2026-10-01 07:00:00' : '2026-10-05 07:00:00',
        'submission_type' => $badge ? 'badge' : 'free', 'badge_verified' => $badge === 'Verified',
        'badge_consecutive_failures' => $badge === 'Failed' ? 1 : 0,
        'badge_placement_url' => 'https://example.com/badge',
    ]);
    $product->id = $index + 1;
    $product->created_at = '2026-09-29 10:00:00';
    $product->badge_verification_attempts_max_checked_at = $badge === 'Pending' ? null : '2026-10-01 06:30:00';
    $user = new User(['name' => ['Avery Chen', 'Maya Patel', 'Noah Williams', 'Sofia Garcia', 'Ethan Kim', 'Admin'][$index], 'email' => 'submitter'.$index.'@example.com']);
    $user->setRelation('roles', $index === 5 ? collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]) : collect());
    $product->setRelation('user', $user);
    $product->setRelation('categories', collect($categories)->map(fn ($name) => new Category(['name' => $name])));
    $product->previewLogo = 'data:image/svg+xml;base64,'.base64_encode($svg);
    return $product;
});
$approvedProducts = new LengthAwarePaginator($items, 6, 20, 1);
$html = view('admin.product_approvals.index', [
    'approvedProducts' => $approvedProducts, 'pendingProducts' => collect(),
    'search' => '', 'status' => null, 'perPage' => 20, 'sortBy' => 'published_at',
    'sortDirection' => 'asc', 'submitterType' => '', 'scheduledProductsCount' => 4,
    'shownProductsCount' => 2, 'failedBadgeProductsCount' => 1,
    'settings' => ['product_publish_time' => '07:00'],
    'errors' => new Illuminate\Support\ViewErrorBag,
])->render();
$manifest = json_decode(file_get_contents(__DIR__.'/../public/build/manifest.json'), true);
$css = file_get_contents(__DIR__.'/../public/build/'.$manifest['resources/css/app.css']['file']);
$js = file_get_contents(__DIR__.'/../resources/js/admin-product-approvals.js');
$html = preg_replace('/<link[^>]+(?:stylesheet|modulepreload)[^>]*>/', '', $html);
$html = preg_replace('/<script[^>]+type="module"[^>]*><\/script>/', '', $html);
$html = str_replace('</head>', '<style>:root{--font-family-sans:ui-sans-serif,system-ui;}'.$css.'</style></head>', $html);
$html = str_replace('</body>', '<script>'.$js.'</script><script>document.addEventListener("submit", e=>e.preventDefault());</script></body>', $html);
$logoIndex = 0;
$html = preg_replace_callback('/src="https:\/\/example.com\/logo.svg"/', function () use ($items, &$logoIndex) {
    return 'src="'.$items[$logoIndex++ % $items->count()]->previewLogo.'"';
}, $html);
$output = $argv[1] ?? __DIR__.'/../docs/previews/product-approvals.html';
file_put_contents($output, $html);
echo "Preview rendered: ".$items->count()." rows\n";

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
        'logo' => 'https://example.com/logo.svg', 'approved' => true,
        'is_published' => $state === 'published',
        'published_at' => $state === 'published' ? '2026-10-01 07:00:00' : '2026-10-05 07:00:00',
        'submission_type' => $badge ? 'badge' : 'free', 'badge_verified' => $badge === 'Verified',
        'badge_consecutive_failures' => $badge === 'Failed' ? 1 : 0,
        'badge_placement_url' => 'https://example.com/badge',
    ]);
    $product->id = $index + 1;
    $product->badge_verification_attempts_max_checked_at = $badge === 'Pending' ? null : '2026-10-01 06:30:00';
    $user = new User(['name' => ['Avery Chen', 'Maya Patel', 'Noah Williams', 'Sofia Garcia', 'Ethan Kim', 'Admin'][$index], 'email' => 'submitter'.$index.'@example.com']);
    $user->setRelation('roles', $index === 5 ? collect([new Role(['name' => 'admin', 'guard_name' => 'web'])]) : collect());
    $product->setRelation('user', $user);
    $product->setRelation('categories', collect($categories)->map(fn ($name) => new Category(['name' => $name])));
    $product->previewLogo = 'data:image/svg+xml;base64,'.base64_encode($svg);
    return $product;
});
$approvedProducts = new LengthAwarePaginator($items, 6, 20, 1);
$html = view('admin.product_approvals._approved_table', ['approvedProducts' => $approvedProducts, 'search' => '', 'status' => null, 'perPage' => 20, 'sortDirection' => 'asc'])->render();
foreach ($items as $item) {
    $html = preg_replace('/src="https:\/\/example.com\/logo.svg"/', 'src="'.$item->previewLogo.'"', $html, 1);
}
$manifest = json_decode(file_get_contents(__DIR__.'/../public/build/manifest.json'), true);
$css = file_get_contents(__DIR__.'/../public/build/'.$manifest['resources/css/app.css']['file']);
$js = file_get_contents(__DIR__.'/../resources/js/admin-product-approvals.js');
$preview = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Product Approvals — sample preview</title><style>:root{--font-family-sans:ui-sans-serif;}'.$css.'</style></head><body class="bg-slate-50 p-6 font-sans dark:bg-slate-950"><header class="mb-4 flex justify-between text-slate-900 dark:text-slate-100"><div><h1 class="text-xl font-semibold">Product Approvals</h1><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">No products pending.</p></div><button id="theme-toggle" class="rounded-lg border px-3 py-2 text-sm">Toggle dark mode</button></header>'.$html.'<p id="preview-message" class="mt-4 text-sm text-slate-600 dark:text-slate-300" role="status">Sample data. Form submissions are disabled in this preview.</p><script>'.$js.'</script><script>document.getElementById("theme-toggle").onclick=()=>document.documentElement.classList.toggle("dark"); document.addEventListener("submit", e=>{e.preventDefault();document.getElementById("preview-message").textContent="Sample action captured. Production page submits this action to Laravel."}); document.querySelectorAll("a").forEach(a=>a.addEventListener("click",e=>e.preventDefault()));</script></body></html>';
file_put_contents(__DIR__.'/../docs/previews/product-approvals.html', $preview);
echo "Preview rendered: 6 rows\n";

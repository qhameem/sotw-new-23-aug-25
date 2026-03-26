<?php
/**
 * TEMPORARY CACHE CLEAR SCRIPT
 * =============================
 * Visit this URL in a browser to clear OPcache and compiled Blade views.
 * DELETE THIS FILE IMMEDIATELY AFTER USE.
 *
 * URL: https://softwareontheweb.com/clear-views-abc123.php
 */

// Basic security — only allow from server's own IP or localhost
// Comment out if running from a different IP
// $allowed = ['127.0.0.1', '::1'];
// if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed)) {
//     http_response_code(403); die('Forbidden');
// }

$results = [];

// 1. Clear OPcache
if (function_exists('opcache_reset')) {
    $reset = opcache_reset();
    $results[] = 'OPcache reset: ' . ($reset ? 'SUCCESS' : 'FAILED');
} else {
    $results[] = 'OPcache: not available';
}

// 2. Delete compiled Blade views
$viewsDir = __DIR__ . '/../storage/framework/views';
$deleted = 0;
$failed  = 0;

if (is_dir($viewsDir)) {
    foreach (glob($viewsDir . '/*.php') as $file) {
        if (unlink($file)) {
            $deleted++;
        } else {
            $failed++;
        }
    }
}

$results[] = "Compiled views deleted: $deleted";
if ($failed) {
    $results[] = "Failed to delete: $failed";
}

// 3. Report remaining files
$remaining = count(glob($viewsDir . '/*.php') ?: []);
$results[] = "Remaining compiled view files: $remaining";

// Done
header('Content-Type: text/plain');
echo implode("\n", $results) . "\n";
echo "\n✅ Done at " . date('Y-m-d H:i:s') . "\n";
echo "⚠️  DELETE THIS FILE NOW: /www/wwwroot/softwareontheweb.com/public/clear-views-abc123.php\n";

<?php

use App\Models\Ad;

test('legacy storage-prefixed image content still resolves to a renderable url', function () {
    $ad = new Ad([
        'type' => 'image_banner',
        'content' => '/storage/logos/legacy-sponsor.png',
    ]);

    expect(Ad::normalizeStoragePath('/storage/logos/legacy-sponsor.png'))->toBe('logos/legacy-sponsor.png');
    expect($ad->image_url)->toContain('/storage/logos/legacy-sponsor.png');
});

test('full storage urls are normalized to relative storage paths', function () {
    $url = 'https://software.test/storage/ads/banner.png';

    expect(Ad::normalizeStoragePath($url))->toBe('ads/banner.png');
});

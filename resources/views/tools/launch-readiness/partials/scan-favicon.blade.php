@php
    $favicon = collect($iconScan->audit_payload['categories'] ?? [])
        ->flatMap(fn (array $category) => $category['checks'] ?? [])
        ->firstWhere('key', 'favicon');
    $iconUrl = ($favicon['status'] ?? null) === 'pass' ? ($favicon['meta']['preview_url'] ?? '') : '';
    $hasIcon = parse_url($iconUrl, PHP_URL_SCHEME) === 'https';
@endphp

<span x-data="{ iconLoaded: false }" class="relative flex h-full w-full items-center justify-center" aria-hidden="true">
    <span x-show="!iconLoaded">@svg('carbon-globe', 'h-3.5 w-3.5')</span>
    @if($hasIcon)
        <img src="{{ $iconUrl }}" alt="" referrerpolicy="no-referrer" x-cloak
            x-show="iconLoaded" x-init="iconLoaded = $el.complete && $el.naturalWidth > 0"
            x-on:load="iconLoaded = true" x-on:error="iconLoaded = false"
            class="absolute inset-0 h-full w-full object-contain">
    @endif
</span>

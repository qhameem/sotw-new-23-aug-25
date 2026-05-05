@php
    $sidebarSnippets = \App\Models\CodeSnippet::where('location', 'sidebar')->get();
    $page = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp
<div class="space-y-6">
    <div class="sidebar-snippets-container w-full overflow-x-auto">
        @foreach ($sidebarSnippets as $snippet)
            @if ($snippet->shouldRenderFor(request()))
                {!! html_entity_decode($snippet->code) !!}
            @endif
        @endforeach
    </div>
    <div>
        <h3 class="text-xs text-gray-500 mb-2">Submitted by</h3>
        @if($product->user->hasRole('admin'))
            <div class="site-body-text text-gray-800 text-sm font-medium">
                Software on the Web team
            </div>
        @else
            <div class="flex items-center gap-2">
                <img src="{{ $product->user->avatar() }}" alt="{{ $product->user->name }}"
                    class="size-6 rounded-full border border-gray-100">
                <div class="site-body-text text-gray-800 text-sm font-medium">
                    {{ $product->user->name }}
                </div>
            </div>
        @endif
    </div>

    @if(($canClaimProduct ?? false) && Auth::check())
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-xs text-gray-500 mb-2">Ownership</h3>

            @if(($currentUserClaim?->status ?? null) === \App\Models\ProductClaim::STATUS_PENDING)
                <p class="text-sm text-gray-700">
                    Your claim is pending admin review.
                </p>
                <a href="{{ route('products.claim.create', $product) }}" class="inline-flex items-center text-sm font-medium text-primary-600 hover:underline mt-2">
                    Manage claim
                </a>
            @elseif(Auth::user()->hasVerifiedEmail())
                <p class="text-sm text-gray-700">
                    If you own this product, you can submit a claim and the admin can assign it to you after review.
                </p>
                <a href="{{ route('products.claim.create', $product) }}" class="inline-flex items-center text-sm font-medium text-primary-600 hover:underline mt-2">
                    Claim this product
                </a>
            @else
                <p class="text-sm text-gray-700">
                    Verify your email first to submit a product claim.
                </p>
            @endif
        </div>
    @endif

    @if($bestForCategories->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">This product is best for</h3>
            <div class="flex flex-wrap gap-1.5">
                @foreach($bestForCategories as $category)
                    <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                        class="text-xs text-gray-700 hover:text-gray-900 font-medium">
                        {{ $category->name }}@if(!$loop->last)<span class="text-gray-300 ml-1.5">•</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($pricingCategory)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Pricing Model</h3>
            <a href="{{ route('pseo.pricing', $pricingCategory->slug) }}" class="text-xs text-gray-700 font-medium hover:text-primary-600 hover:underline">
                {{ $pricingCategory->name }}
            </a>
        </div>
    @endif

    @if($product->pricing_page_url)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Pricing Page</h3>
            <a href="{{ $product->pricing_page_url }}" target="_blank" rel="nofollow noopener" 
               class="flex items-center gap-2 text-xs text-gray-700 hover:text-gray-900 font-medium group truncate text-[11px]">
                <svg class="size-3.5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                <span>View Pricing</span>
            </a>
        </div>
    @endif

    @if($product->price > 0)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Price</h3>
            <p class="site-body-text text-xs text-gray-700 font-medium">
                {{ $product->currency }} {{ number_format($product->price, 2) }}
            </p>
        </div>
    @endif

    @if($product->techStacks->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Built with</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($product->techStacks as $techStack)
                    <a href="{{ route('pseo.builtWith', $techStack->slug) }}"
                       class="inline-flex items-center text-xs text-gray-700 font-medium bg-gray-50 px-2 py-0.5 rounded border border-gray-100 hover:bg-gray-100 hover:border-gray-200 transition-colors">
                        {{ $techStack->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- pSEO: Alternatives + Compare links --}}
    <div class="space-y-6 pt-1">
        <a href="{{ route('pseo.alternatives', $product->slug) }}"
           class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-primary-600 transition-colors">
            <svg class="size-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Find alternatives to {{ $product->name }}
        </a>

        @if(isset($similarProducts) && $similarProducts->isNotEmpty())
            <div>
                <p class="text-xs text-gray-400 font-medium tracking-wide mb-1.5">Compare with</p>
                @foreach($similarProducts->take(2) as $similar)
                    <a href="{{ route('pseo.compare', ['params' => $product->slug . '-vs-' . $similar->slug]) }}"
                       class="block text-xs text-gray-500 hover:text-primary-600 transition-colors mb-1">
                        {{ $product->name }} vs {{ $similar->name }} →
                    </a>
                    @if(!empty($similar->match_summary))
                        <p class="site-body-text text-[11px] text-gray-400 mb-2">{{ $similar->match_summary }}</p>
                    @endif
                @endforeach
            </div>
        @endif
    </div>


    @php
        $makerLinks = is_array($product->maker_links) ? $product->maker_links : json_decode($product->maker_links, true) ?? [];
        $socialLinks = [];
        $extraLinks = [];
        $xHandle = \App\Models\Product::normalizeXAccount($product->x_account);
        $xProfileUrl = \App\Models\Product::xProfileUrl($product->x_account);
        $normalizeHost = function (?string $host): string {
            $host = strtolower((string) $host);
            $host = preg_replace('/^(www|m)\./', '', $host);

            return $host ?? '';
        };

        $socialPlatforms = [
            [
                'key' => 'x',
                'matches' => ['x.com', 'twitter.com'],
                'label' => 'X',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            ],
            [
                'key' => 'linkedin',
                'matches' => ['linkedin.com'],
                'label' => 'LinkedIn',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.58c0-1.33-.03-3.05-1.86-3.05-1.86 0-2.15 1.45-2.15 2.95v5.68H9.32V9h3.42v1.56h.05c.48-.9 1.64-1.86 3.38-1.86 3.61 0 4.28 2.38 4.28 5.48v6.27zM5.34 7.43a2.07 2.07 0 110-4.14 2.07 2.07 0 010 4.14zM7.12 20.45H3.56V9h3.56v11.45zM22.23 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.46c.98 0 1.77-.77 1.77-1.72V1.72C24 .77 23.21 0 22.23 0z"/></svg>',
            ],
            [
                'key' => 'facebook',
                'matches' => ['facebook.com', 'fb.com'],
                'label' => 'Facebook',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.03 4.39 11.03 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.03 1.79-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.88v2.27h3.32l-.53 3.49h-2.79V24C19.61 23.1 24 18.1 24 12.07z"/></svg>',
            ],
            [
                'key' => 'instagram',
                'matches' => ['instagram.com'],
                'label' => 'Instagram',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M7.75 2h8.5A5.75 5.75 0 0122 7.75v8.5A5.75 5.75 0 0116.25 22h-8.5A5.75 5.75 0 012 16.25v-8.5A5.75 5.75 0 017.75 2zm0 1.8A3.95 3.95 0 003.8 7.75v8.5a3.95 3.95 0 003.95 3.95h8.5a3.95 3.95 0 003.95-3.95v-8.5a3.95 3.95 0 00-3.95-3.95h-8.5zm8.93 1.35a1.22 1.22 0 110 2.44 1.22 1.22 0 010-2.44zM12 6.86A5.14 5.14 0 1112 17.14 5.14 5.14 0 0112 6.86zm0 1.8A3.34 3.34 0 1012 15.34 3.34 3.34 0 0012 8.66z"/></svg>',
            ],
            [
                'key' => 'threads',
                'matches' => ['threads.net'],
                'label' => 'Threads',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M15.95 10.78c-.1-.05-.22-.1-.33-.15-.06-1.14-.37-2.04-.95-2.73-.88-1.05-2.26-1.58-4.09-1.58-3.53 0-5.77 2.08-5.77 5.3 0 3.13 2.14 5.24 5.33 5.24 1.66 0 2.98-.46 3.93-1.37.85-.8 1.34-1.92 1.47-3.31.53.31.95.71 1.26 1.19.45.68.59 1.54.38 2.37-.22.85-.8 1.59-1.63 2.07-.95.55-2.28.84-3.84.84-3.4 0-6.16-2.1-6.16-5.92 0-3.98 2.95-6.54 7.53-6.54 2.36 0 4.22.73 5.5 2.15 1.09 1.21 1.68 2.92 1.76 5.1l-.02.02c1.33.8 2.2 2.14 2.2 3.73 0 2.5-2 4.67-5.08 4.67-2.59 0-4.25-1.36-4.25-3.46 0-2.14 1.74-3.74 4.1-3.74.66 0 1.3.12 1.89.35-.16-.88-.5-1.58-1.03-2.08-.58-.55-1.41-.83-2.48-.83-2.08 0-3.35 1.15-3.35 2.99 0 1.75 1.17 2.89 2.96 2.89.97 0 1.7-.26 2.16-.78.42-.47.63-1.14.63-1.97v-.02zm-.2 4.21c-.42-.25-.93-.39-1.47-.39-1.3 0-2.23.84-2.23 1.98 0 1.03.79 1.71 2.02 1.71 1.16 0 2.01-.73 2.01-1.75 0-.58-.11-1.1-.33-1.55z"/></svg>',
            ],
            [
                'key' => 'youtube',
                'matches' => ['youtube.com', 'youtu.be'],
                'label' => 'YouTube',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3.01 3.01 0 00-2.12-2.13C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.38.57A3.01 3.01 0 00.5 6.2C0 8.07 0 12 0 12s0 3.93.5 5.8a3.01 3.01 0 002.12 2.13C4.5 20.5 12 20.5 12 20.5s7.5 0 9.38-.57a3.01 3.01 0 002.12-2.13C24 15.93 24 12 24 12s0-3.93-.5-5.8zM9.6 15.4V8.6L15.8 12l-6.2 3.4z"/></svg>',
            ],
            [
                'key' => 'pinterest',
                'matches' => ['pinterest.com', 'pin.it'],
                'label' => 'Pinterest',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 0a12 12 0 00-4.38 23.17c-.06-.98-.12-2.49.02-3.56.13-.97.84-6.19.84-6.19s-.22-.44-.22-1.08c0-1.01.59-1.77 1.31-1.77.62 0 .92.46.92 1.02 0 .62-.39 1.54-.59 2.4-.17.72.36 1.31 1.07 1.31 1.28 0 2.27-1.35 2.27-3.29 0-1.72-1.24-2.92-3-2.92-2.04 0-3.24 1.53-3.24 3.12 0 .62.24 1.28.54 1.64a.22.22 0 01.05.21c-.06.23-.2.72-.22.82-.03.13-.1.16-.24.1-.9-.42-1.46-1.72-1.46-2.77 0-2.25 1.64-4.32 4.73-4.32 2.49 0 4.42 1.77 4.42 4.13 0 2.47-1.56 4.46-3.72 4.46-.73 0-1.41-.38-1.64-.83l-.45 1.72c-.16.62-.6 1.39-.9 1.86A12 12 0 1012 0z"/></svg>',
            ],
            [
                'key' => 'tiktok',
                'matches' => ['tiktok.com'],
                'label' => 'TikTok',
                'icon' => '<svg class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.6 2c.27 2.2 1.5 3.52 3.4 3.64v2.55a5.9 5.9 0 01-3.37-1.07v7.02c0 3.52-2.13 5.86-5.55 5.86-3.32 0-5.58-2.43-5.58-5.56 0-3.32 2.43-5.71 5.9-5.71.39 0 .76.04 1.11.13v2.72a3.18 3.18 0 00-1.05-.18c-1.69 0-2.96 1.23-2.96 2.97 0 1.64 1.14 2.92 2.78 2.92 1.95 0 2.75-1.37 2.75-3.4V2h2.57z"/></svg>',
            ],
        ];

        $findSocialPlatform = function (string $link) use ($normalizeHost, $socialPlatforms) {
            $host = $normalizeHost(parse_url($link, PHP_URL_HOST));

            foreach ($socialPlatforms as $platform) {
                foreach ($platform['matches'] as $match) {
                    if ($host === $match || Str::endsWith($host, '.' . $match)) {
                        return $platform;
                    }
                }
            }

            return null;
        };

        $extractSocialHandle = function (string $link, string $platformKey): ?string {
            $path = trim((string) parse_url($link, PHP_URL_PATH), '/');

            if ($path === '') {
                return null;
            }

            $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));

            if (empty($segments)) {
                return null;
            }

            $handle = match ($platformKey) {
                'linkedin' => in_array(strtolower($segments[0]), ['in', 'company', 'school', 'showcase'], true) ? ($segments[1] ?? $segments[0]) : $segments[0],
                'youtube' => in_array(strtolower($segments[0]), ['channel', 'c', 'user', '@'], true) ? ($segments[1] ?? $segments[0]) : $segments[0],
                default => $segments[0],
            };

            $handle = urldecode($handle);
            $handle = preg_replace('/^@+/', '', $handle);
            $handle = preg_replace('/\?.*$/', '', $handle);

            return $handle !== '' ? '@' . $handle : null;
        };

        $normalizedXProfileUrl = $xProfileUrl ? \App\Models\Product::normalizeLink($xProfileUrl) : null;

        foreach($makerLinks as $link) {
            $platform = $findSocialPlatform($link);

            if ($platform) {
                $normalizedLink = \App\Models\Product::normalizeLink($link);

                if ($platform['key'] === 'x' && $normalizedXProfileUrl && $normalizedLink === $normalizedXProfileUrl) {
                    continue;
                }

                $socialLinks[] = [
                    'link' => $link,
                    'label' => $extractSocialHandle($link, $platform['key']) ?? $platform['label'],
                    'icon' => $platform['icon'],
                ];
            } else {
                $extraLinks[] = $link;
            }
        }
    @endphp

    @if($xProfileUrl || !empty($socialLinks) || !empty($extraLinks))
        <div class="space-y-6">
            @if($xProfileUrl || !empty($socialLinks))
                <div>
                    <h3 class="text-xs text-gray-500 mb-2">Social Profiles</h3>
                    <div class="space-y-2">
                        @if($xProfileUrl)
                            <a href="{{ $xProfileUrl }}" target="_blank" rel="nofollow noopener" 
                               class="flex items-center gap-2 text-xs text-gray-700 hover:text-gray-900 hover:underline font-medium group text-[11px] underline-offset-2">
                                <svg class="size-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                <span>@ {{ $xHandle }}</span>
                            </a>
                        @endif

                        @foreach($socialLinks as $socialLink)
                            <a href="{{ $socialLink['link'] }}" target="_blank" rel="nofollow noopener" 
                               class="flex items-center gap-2 text-xs text-gray-700 hover:text-gray-900 hover:underline font-medium group truncate text-[11px] underline-offset-2">
                                {!! $socialLink['icon'] !!}
                                <span>{{ $socialLink['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($extraLinks))
                <div>
                    <h3 class="text-xs text-gray-500 mb-2">Extra Resources</h3>
                    <div class="space-y-2">
                        @foreach($extraLinks as $link)
                            @php 
                                $host = parse_url($link, PHP_URL_HOST);
                                $displayLink = $host ? str_replace('www.', '', $host) : 'Link';
                                
                                // Specific label improvements
                                if (Str::contains($host, 'github.com')) $displayLink = 'GitHub Repository';
                                elseif (Str::contains($host, 'docs.')) $displayLink = 'Documentation';
                                elseif (Str::contains($host, 'help.')) $displayLink = 'Help Center';
                            @endphp
                            <a href="{{ $link }}" target="_blank" rel="nofollow noopener" 
                               class="flex items-center gap-2 text-xs text-gray-700 hover:text-gray-900 hover:underline font-medium group truncate text-[11px] underline-offset-2">
                                <svg class="size-3.5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                <span>{{ $displayLink }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

</div>

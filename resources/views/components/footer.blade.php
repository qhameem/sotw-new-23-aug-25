@php
    $footerBadgeEmbedCodes = [];

    if (!request()->routeIs('admin.*') && Illuminate\Support\Facades\Storage::disk('local')->exists('settings.json')) {
        $settings = json_decode(Illuminate\Support\Facades\Storage::disk('local')->get('settings.json'), true);
        $footerBadgeEmbedCodes = $settings['footer_badge_embed_codes'] ?? [];
        $footerBadgeEmbedDofollow = $settings['footer_badge_embed_dofollow'] ?? [];
    }

    if (is_string($footerBadgeEmbedCodes)) {
        $footerBadgeEmbedCodes = [$footerBadgeEmbedCodes];
    }

    $footerBadgeEmbedCodes = collect(is_array($footerBadgeEmbedCodes) ? $footerBadgeEmbedCodes : [])
        ->map(fn ($code) => trim((string) $code))
        ->filter()
        ->values()
        ->all();
    $footerBadgeEmbedDofollow = is_array($footerBadgeEmbedDofollow ?? null) ? $footerBadgeEmbedDofollow : [];
@endphp

<footer class="w-full p-4 border-t md:flex md:items-center md:justify-center md:p-6">
    <div class="w-full text-xs text-gray-500 dark:text-gray-400 text-center">
        @unless(request()->routeIs('admin.*', 'newsletter.*', 'profile.*', 'dashboard', 'stripe.*', 'todolists.*', 'tools.*'))
            <div class="mx-auto mb-5 max-w-md text-left md:hidden">
                @include('partials._newsletter-signup-form', ['placement' => 'mobile_footer'])
            </div>
        @endunless
        <a href="{{ route('alternatives.index') }}" class="hover:underline">Software alternatives</a> •
        <a href="{{ route('agent.index') }}" class="hover:underline">llms.txt</a> •
        <a href="{{ route('about') }}" class="hover:underline">About</a> •
        <a href="{{ route('faq') }}" class="hover:underline">FAQ</a> •
        <a href="{{ route('newsletter.index') }}" class="hover:underline">Newsletter</a> •
        <a href="{{ route('legal') }}" class="hover:underline">Privacy Policy</a> •
        <a href="{{ route('refund-policy') }}" class="hover:underline">Refund Policy</a> •
        <a href="{{ route('changelog.index') }}" class="hover:underline">Changelog</a> •
        <a href="{{ route('badges.index') }}" class="hover:underline">Badge</a> •
        <a href="{{ config('agent_access.socials.0') }}" target="_blank" rel="{{ \App\Support\OutboundLink::rel(config('agent_access.socials.0'), 'system_view') }}" class="hover:underline">X.com</a> •
        Contact us: <a href="mailto:{{ config('agent_access.contact') }}" target="_blank" class="hover:underline">{{ config('agent_access.contact') }}</a>
        <div class="h-2"></div>
        @if (!empty($footerBadgeEmbedCodes))
            <div class="mb-3 flex flex-wrap items-center justify-center gap-3">
                @foreach ($footerBadgeEmbedCodes as $index => $footerBadgeEmbedCode)
                    <div class="flex items-center justify-center">
                        {!! \App\Support\OutboundLink::sanitizeHtml($footerBadgeEmbedCode, 'footer_embed', (bool) ($footerBadgeEmbedDofollow[$index] ?? false)) !!}
                    </div>
                @endforeach
            </div>
        @endif
        <span class="text-gray-400">{{ now('UTC')->format('d/m/Y H:i') }} UTC</span> © {{ date('Y') }} {{ config('app.name', 'Software on the Web') }}
    </div>
</footer>

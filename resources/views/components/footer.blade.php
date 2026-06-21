@php
    $footerBadgeEmbedCodes = [];

    if (!request()->routeIs('admin.*') && Illuminate\Support\Facades\Storage::disk('local')->exists('settings.json')) {
        $settings = json_decode(Illuminate\Support\Facades\Storage::disk('local')->get('settings.json'), true);
        $footerBadgeEmbedCodes = $settings['footer_badge_embed_codes'] ?? [];
    }

    if (is_string($footerBadgeEmbedCodes)) {
        $footerBadgeEmbedCodes = [$footerBadgeEmbedCodes];
    }

    $footerBadgeEmbedCodes = collect(is_array($footerBadgeEmbedCodes) ? $footerBadgeEmbedCodes : [])
        ->map(fn ($code) => trim((string) $code))
        ->filter()
        ->values()
        ->all();
@endphp

<footer class="w-full p-4 border-t md:flex md:items-center md:justify-center md:p-6">
    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        <a href="{{ route('about') }}" class="hover:underline">About</a> •
        <a href="{{ route('faq') }}" class="hover:underline">FAQ</a> •
        <a href="{{ route('legal') }}" class="hover:underline">Privacy Policy</a> •
        <a href="{{ route('changelog.index') }}" class="hover:underline">Changelog</a> •
        <a href="{{ route('badges.index') }}" class="hover:underline">Badge</a> •
        <a href="https://x.com/software_on_web" target="_blank" rel="{{ \App\Support\OutboundLink::rel('https://x.com/software_on_web', 'system_view') }}" class="hover:underline">X.com</a> •
        Contact us: <a href="mailto:hello@softwareontheweb.com" target="_blank" class="hover:underline">hello@softwareontheweb.com</a>
        <div class="h-2"></div>
        @if (!empty($footerBadgeEmbedCodes))
            <div class="mb-3 flex flex-wrap items-center justify-center gap-3">
                @foreach ($footerBadgeEmbedCodes as $footerBadgeEmbedCode)
                    <div class="flex items-center justify-center">
                        {!! \App\Support\OutboundLink::sanitizeHtml($footerBadgeEmbedCode, 'footer_embed') !!}
                    </div>
                @endforeach
            </div>
        @endif
        <span class="text-gray-400">{{ now('UTC')->format('d/m/Y H:i') }} UTC</span> © {{ date('Y') }} {{ config('app.name', 'Software on the Web') }}
    </div>
</footer>

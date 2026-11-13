@if(!empty($toolFooterBadges))
    <section aria-label="Site badges" x-data="{ badgeDisplayHeight: 32 }">
        <style>
            .lr-footer-badge-static a { display: inline-flex; align-items: center; height: 100%; }
            .lr-footer-badge-static img { display: block; height: var(--badge-height) !important; width: auto !important; max-width: none !important; max-height: none !important; }
        </style>
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-4 pb-5 sm:px-6">
            @foreach($toolFooterBadges as $badge)
                @php($inlineBadge = app(\App\Support\LaunchReadinessBadges::class)->inlineHtml($badge['code']))
                @if($inlineBadge !== null)
                    <div class="lr-footer-badge-static flex-shrink-0" :style="{ height: badgeDisplayHeight + 'px', '--badge-height': badgeDisplayHeight + 'px' }">
                        {!! $inlineBadge !!}
                    </div>
                @else
                <div class="relative flex-shrink-0 overflow-hidden"
                    x-data="{ badgeWidth: 150, badgeHeight: 150 }"
                    x-init="$nextTick(() => $refs.frame.contentWindow.postMessage({ type: 'launch-readiness-badge-measure' }, '*'))"
                    @message.window="if ($event.source === $refs.frame.contentWindow && $event.data?.type === 'launch-readiness-badge-size' && Number.isFinite($event.data.width) && Number.isFinite($event.data.height) && $event.data.width > 0 && $event.data.height > 0 && $event.data.width <= 100000 && $event.data.height <= 100000) { badgeWidth = $event.data.width; badgeHeight = $event.data.height; }"
                    :style="{ width: (badgeWidth * badgeDisplayHeight / badgeHeight) + 'px', height: badgeDisplayHeight + 'px' }">
                    <iframe x-ref="frame" title="{{ $badge['name'] }}" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"
                        @load="$el.contentWindow.postMessage({ type: 'launch-readiness-badge-measure' }, '*')"
                        referrerpolicy="no-referrer" class="absolute left-0 top-0 border-0" style="transform-origin: top left;"
                        :style="{ width: badgeWidth + 'px', height: badgeHeight + 'px', transform: 'scale(' + (badgeDisplayHeight / badgeHeight) + ')' }"
                        srcdoc="{{ view('tools.launch-readiness.partials.badge-document', ['code' => $badge['code']])->render() }}"></iframe>
                </div>
                @endif
            @endforeach
        </div>
    </section>
@endif

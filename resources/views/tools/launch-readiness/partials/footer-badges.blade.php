@if(!empty($toolFooterBadges))
    <section aria-label="Site badges" class="flex-shrink-0 border-t border-slate-200/80 bg-white"
        x-data="{
            footerHeight: 57,
            get stripHeight() { return Math.max(40, Math.round(this.footerHeight * 0.8)); },
            get badgeDisplayHeight() { return this.stripHeight - 17; },
            observer: null,
            init() {
                const footer = this.$el.previousElementSibling;
                this.observer = new ResizeObserver(() => { this.footerHeight = footer.getBoundingClientRect().height; });
                this.observer.observe(footer);
                this.footerHeight = footer.getBoundingClientRect().height;
            },
            destroy() { this.observer?.disconnect(); }
        }"
        :style="{ height: stripHeight + 'px' }">
        <style>
            .lr-footer-badge-static a { display: inline-flex; align-items: center; height: 100%; }
            .lr-footer-badge-static img { display: block; height: var(--badge-height) !important; width: auto !important; max-width: none !important; max-height: none !important; }
        </style>
        <div class="mx-auto flex h-full max-w-6xl items-center gap-6 overflow-x-auto px-4 py-2 sm:px-6">
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

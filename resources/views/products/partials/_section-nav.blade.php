@if(!empty($sectionNavItems))
    @php
        $firstSectionId = $sectionNavItems[0]['id'] ?? null;
        $sectionIds = collect($sectionNavItems)->pluck('id')->values();
    @endphp

    <div class="mb-8"
        x-data="{
            activeId: window.location.hash ? window.location.hash.replace('#', '') : '{{ $firstSectionId }}',
            sectionIds: @js($sectionIds),
            isPinned: false,
            navHeight: 0,
            navWidth: 0,
            navLeft: 0,
            topOffset: 75,
            updateTopOffset() {
                this.topOffset = window.innerWidth >= 768 ? 59.2 : 75;
            },
            updatePinnedState() {
                this.updateTopOffset();

                const wrapper = this.$refs.wrapper;
                const nav = this.$refs.nav;

                if (!wrapper || !nav) {
                    return;
                }

                const rect = wrapper.getBoundingClientRect();
                this.navHeight = nav.offsetHeight;
                this.navWidth = rect.width;
                this.navLeft = rect.left;
                this.isPinned = rect.top <= this.topOffset;
            },
            syncFromHash() {
                const hash = window.location.hash.replace('#', '');
                if (hash && this.sectionIds.includes(hash)) {
                    this.activeId = hash;
                }
            },
            observeSections() {
                const observer = new IntersectionObserver((entries) => {
                    const visibleEntries = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);

                    if (visibleEntries.length > 0) {
                        this.activeId = visibleEntries[0].target.id;
                    }
                }, {
                    rootMargin: '-140px 0px -55% 0px',
                    threshold: [0.2, 0.4, 0.7],
                });

                this.sectionIds.forEach((id) => {
                    const section = document.getElementById(id);
                    if (section) {
                        observer.observe(section);
                    }
                });
            },
        }"
        x-init="
            syncFromHash();
            window.addEventListener('hashchange', () => syncFromHash());
            $nextTick(() => {
                observeSections();
                updatePinnedState();
                window.addEventListener('scroll', () => updatePinnedState(), { passive: true });
                window.addEventListener('resize', () => updatePinnedState());
            });
        ">
        <div x-ref="wrapper" :style="isPinned ? `height: ${navHeight}px` : ''">
            <nav x-ref="nav"
                class="overflow-x-auto border-b border-gray-200 bg-white/95 backdrop-blur"
                :class="isPinned ? 'fixed z-30 shadow-sm' : 'relative'"
                :style="isPinned ? `top: ${topOffset}px; left: ${navLeft}px; width: ${navWidth}px;` : ''">
                <div class="flex min-w-full items-stretch">
                    @foreach($sectionNavItems as $item)
                        <a href="#{{ $item['id'] }}"
                            @click="activeId = '{{ $item['id'] }}'"
                            x-bind:aria-current="activeId === '{{ $item['id'] }}' ? 'page' : null"
                            class="relative inline-flex flex-1 items-center justify-center px-4 py-4 text-center text-sm font-semibold transition-colors duration-200"
                            :class="activeId === '{{ $item['id'] }}'
                                ? 'text-primary-600'
                                : 'text-gray-500 hover:text-gray-900'">
                            <span>{{ $item['label'] }}</span>
                            <span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full transition-colors duration-200"
                                :class="activeId === '{{ $item['id'] }}' ? 'bg-primary-500' : 'bg-transparent'"></span>
                        </a>
                    @endforeach
                </div>
            </nav>
        </div>
    </div>
@endif

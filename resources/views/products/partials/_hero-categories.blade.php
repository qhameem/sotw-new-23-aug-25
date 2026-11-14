@php
    $generalCategories = $product->categories->filter(function ($cat) {
        return !$cat->types->contains('name', 'Pricing')
            && !$cat->types->contains('name', 'Best for')
            && !$cat->types->contains('name', 'Use Case')
            && !$cat->types->contains('name', 'Use Cases')
            && !$cat->types->contains('name', 'Platform');
    });
@endphp

@if($generalCategories->isNotEmpty())
    <div class="relative min-w-8 flex-1 text-xs"
        x-data="{
            open: false,
            observer: null,
            init() {
                this.observer = new ResizeObserver(() => this.fit());
                this.observer.observe(this.$el);
                document.fonts.ready.then(() => { if (this.$el.isConnected) this.fit() });
            },
            destroy() {
                this.observer?.disconnect();
            },
            fit() {
                const { tags, more, icon } = this.$refs;
                const links = Array.from(tags.children);
                for (const link of links) link.hidden = false;
                icon.style.display = '';
                more.hidden = true;
                if (tags.scrollWidth <= tags.clientWidth) {
                    this.open = false;
                    return;
                }
                more.hidden = false;
                for (const link of links.reverse()) {
                    if (tags.scrollWidth <= tags.clientWidth) break;
                    link.hidden = true;
                }
                if (links.every(link => link.hidden)) icon.style.display = 'none';
            }
        }"
        @click.outside="open = false"
        @keydown.escape.stop="if (open) { open = false; $refs.more.focus() }">
        <div class="flex items-center gap-2">
            <svg x-ref="icon" class="h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M17 10H19C21 10 22 9 22 7V5C22 3 21 2 19 2H17C15 2 14 3 14 5V7C14 9 15 10 17 10Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M5 22H7C9 22 10 21 10 19V17C10 15 9 14 7 14H5C3 14 2 15 2 17V19C2 21 3 22 5 22Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M6 10C8.20914 10 10 8.20914 10 6C10 3.79086 8.20914 2 6 2C3.79086 2 2 3.79086 2 6C2 8.20914 3.79086 10 6 10Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M18 22C20.2091 22 22 20.2091 22 18C22 15.7909 20.2091 14 18 14C15.7909 14 14 15.7909 14 18C14 20.2091 15.7909 22 18 22Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div x-ref="tags" class="flex min-w-0 flex-1 gap-3 overflow-hidden whitespace-nowrap py-1">
                @foreach($generalCategories as $category)
                    <a href="{{ $category->publicUrl() }}" wire:navigate.hover
                        class="text-[0.65rem] font-normal leading-5 text-inherit underline decoration-gray-300 underline-offset-4 transition-colors hover:text-gray-800 hover:decoration-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 md:text-xs">{{ $category->name }}</a>
                @endforeach
            </div>
            <button x-ref="more" type="button" hidden class="shrink-0 rounded px-1 font-semibold hover:text-gray-800"
                aria-label="Show all categories" :aria-expanded="open" @click="open = !open">…</button>
        </div>
        <div x-show="open" x-cloak class="absolute right-0 top-full z-20 mt-2 flex w-max max-w-[min(16rem,70vw)] flex-col gap-2 rounded-md border border-gray-200 bg-white p-3 shadow-lg">
            @foreach($generalCategories as $category)
                <a href="{{ $category->publicUrl() }}" wire:navigate.hover
                    class="text-[0.65rem] font-normal leading-5 text-inherit underline decoration-gray-300 underline-offset-4 transition-colors hover:text-gray-800 hover:decoration-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 md:text-xs">{{ $category->name }}</a>
            @endforeach
        </div>
    </div>
@endif

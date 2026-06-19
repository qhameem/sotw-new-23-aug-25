@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'scrollable' => true,
    'viewportPadding' => 'default',
    'hideScrollbar' => false,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];

$viewportPadding = [
    'default' => 'px-4 py-20 sm:px-0 sm:py-24',
    'compact' => 'px-4 py-6 sm:px-0 sm:py-10',
][$viewportPadding] ?? 'px-4 py-20 sm:px-0 sm:py-24';
@endphp

<style>
    [data-hide-scrollbar="true"] {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    [data-hide-scrollbar="true"]::-webkit-scrollbar {
        display: none;
    }
</style>

<div
    x-data="{
        show: @js($show),
        intendedUrl: '',
        lockBodyScroll() {
            const body = document.body
            const root = document.documentElement
            const openCount = Number(body.dataset.modalOpenCount || 0)

            if (openCount === 0) {
                body.dataset.modalOriginalPaddingRight = body.style.paddingRight || ''
                const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth

                root.style.setProperty('--modal-scrollbar-compensation', `${Math.max(scrollbarWidth, 0)}px`)

                if (scrollbarWidth > 0) {
                    body.style.paddingRight = `${scrollbarWidth}px`
                }

                body.classList.add('overflow-y-hidden')
            }

            body.dataset.modalOpenCount = String(openCount + 1)
        },
        unlockBodyScroll() {
            const body = document.body
            const root = document.documentElement
            const openCount = Number(body.dataset.modalOpenCount || 0)

            if (openCount <= 1) {
                body.classList.remove('overflow-y-hidden')
                body.style.paddingRight = body.dataset.modalOriginalPaddingRight || ''
                root.style.setProperty('--modal-scrollbar-compensation', '0px')
                delete body.dataset.modalOpenCount
                delete body.dataset.modalOriginalPaddingRight
                return
            }

            body.dataset.modalOpenCount = String(openCount - 1)
        },
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            lockBodyScroll();
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable()?.focus(), 100)' : '' }}
        } else {
            unlockBodyScroll();
        }
    })"
    x-on:open-modal.window="if ($event.detail.name == '{{ $name }}') { show = true; if ($event.detail.url) { intendedUrl = $event.detail.url } }"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    data-hide-scrollbar="{{ $hideScrollbar ? 'true' : 'false' }}"
    class="fixed inset-0 z-[60] overflow-y-auto {{ $viewportPadding }} {{ $scrollable ? '' : 'flex items-center justify-center' }} {{ $hideScrollbar ? '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden' : '' }}"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

    <div
        x-show="show"
        data-hide-scrollbar="{{ $hideScrollbar ? 'true' : 'false' }}"
        class="relative w-full rounded-lg bg-white shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto {{ $scrollable ? 'mb-6 max-h-[calc(100vh-7rem)] overflow-y-auto sm:max-h-[calc(100vh-8rem)]' : 'overflow-hidden' }} {{ $hideScrollbar ? '[scrollbar-width:none] [&::-webkit-scrollbar]:hidden' : '' }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>

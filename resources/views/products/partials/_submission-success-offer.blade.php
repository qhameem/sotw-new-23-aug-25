@php
    $inlinePreview = $inlinePreview ?? false;
    $offer = $inlinePreview ? [] : collect($modal)->map(fn ($value) => (string) $value)->all();
@endphp

<div x-data="{
    @unless($inlinePreview) draft: @js($offer), @endunless
    hex(value) {
        return /^#[0-9a-f]{6}$/i.test(value || '') ? value : null
    },
    get accent() {
        return this.hex(this.draft.offer_accent) || 'var(--color-primary-500)'
    },
    get buttonText() {
        const color = this.hex(this.draft.offer_accent)
        if (!color) return 'var(--color-primary-button-text)'
        const luminance = [0.2126, 0.7152, 0.0722].reduce((total, weight, index) => {
            const value = parseInt(color.slice(1 + index * 2, 3 + index * 2), 16) / 255
            const channel = value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
            return total + weight * channel
        }, 0)
        return luminance > 0.179 ? '#000000' : '#ffffff'
    },
    get headline() {
        const title = this.draft.title || 'Modal title'
        const highlight = (this.draft.offer_highlight || '').trim()
        const start = highlight ? title.indexOf(highlight) : -1
        return start < 0 ? [title, '', ''] : [title.slice(0, start), highlight, title.slice(start + highlight.length)]
    },
    get benefits() {
        return (this.draft.offer_benefits || '').split(/\r?\n/).map(line => line.trim()).filter(Boolean)
    },
    today: @js(today()->toDateString()),
    get promoVisible() {
        return (this.draft.offer_promo || this.draft.offer_code) &&
            (!this.draft.offer_expiry || this.draft.offer_expiry >= this.today)
    }
}" class="space-y-5">
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl p-1.5"
                :style="{ backgroundColor: hex(draft.offer_logo_background) }"
                x-show="/^https?:\/\//i.test(draft.ad_image)">
                <img :src="/^https?:\/\//i.test(draft.ad_image) ? draft.ad_image : null" alt="" class="h-full w-full rounded-lg object-contain">
            </div>
            <div class="min-w-0">
                <p x-text="draft.ad_title || 'Sponsor name'" class="break-words font-semibold"></p>
                <p x-show="draft.offer_disclosure" x-text="draft.offer_disclosure" class="text-sm text-gray-600"></p>
            </div>
        </div>
        <button type="button" aria-label="Close" @if($inlinePreview) disabled @else autofocus @click="$el.closest('dialog').close()" @endif
            class="rounded-md px-2 text-xl leading-8 text-gray-600 hover:bg-gray-100">×</button>
    </div>

    <h2 @unless($inlinePreview) id="submission-modal-title" @endunless
        class="break-words text-2xl font-bold leading-tight tracking-tight sm:text-3xl"><span x-text="headline[0]"></span><span :style="{ color: accent }" x-text="headline[1]"></span><span x-text="headline[2]"></span></h2>
    <p x-show="draft.body" x-text="draft.body" class="whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700"></p>

    <ul x-show="benefits.length" class="space-y-2 text-gray-800">
        <template x-for="(benefit, index) in benefits" :key="index">
            <li class="flex items-start gap-3">
                <span x-show="['check', 'dot'].includes(draft.offer_bullet)" x-text="draft.offer_bullet === 'check' ? '✓' : '•'"
                    class="shrink-0 font-semibold text-green-800" aria-hidden="true"></span>
                <span x-show="draft.offer_bullet === 'circled-check'" class="flex h-6 shrink-0 items-center text-green-800" aria-hidden="true">
                    <x-simpleline-check class="h-4 w-4" />
                </span>
                <span x-text="benefit" class="min-w-0 break-words"></span>
            </li>
        </template>
    </ul>

    <div x-show="promoVisible" class="w-fit max-w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        <span x-text="draft.offer_promo" class="break-words"></span>
        <strong x-show="draft.offer_code" x-text="'Code ' + draft.offer_code" class="break-all"></strong>
    </div>

    <div>
        <a @if($inlinePreview) aria-disabled="true" @else href="{{ $modal['ad_url'] }}" target="_blank" rel="noopener noreferrer sponsored" @endif
            :class="draft.offer_button_width === 'full' ? 'w-full' : ''" :style="{ backgroundColor: accent, color: buttonText }"
            x-text="draft.ad_link_label || 'Primary button'" class="inline-flex justify-center rounded-xl px-5 py-3 text-center font-semibold"></a>

        <div x-show="draft.offer_price || draft.offer_dismiss || draft.ad_secondary_label"
            class="mt-3 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2 text-sm text-gray-600">
            <span x-show="draft.offer_price" x-text="draft.offer_price" class="min-w-0 break-words"></span>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                <a x-show="draft.ad_secondary_label && draft.ad_secondary_url" x-text="draft.ad_secondary_label"
                    @if($inlinePreview) aria-disabled="true" @elseif(filled($modal['ad_secondary_url'] ?? null))
                        href="{{ $modal['ad_secondary_url'] }}" target="_blank" rel="noopener noreferrer sponsored"
                    @endif class="underline"></a>
                <button type="button" x-show="draft.offer_dismiss" x-text="draft.offer_dismiss"
                    @if($inlinePreview) disabled @else @click="$el.closest('dialog').close()" @endif class="underline"></button>
            </div>
        </div>
    </div>

    <p x-show="draft.offer_footer" x-text="draft.offer_footer"
        class="whitespace-pre-wrap break-words border-t border-gray-200 pt-4 text-sm text-gray-600"></p>
</div>

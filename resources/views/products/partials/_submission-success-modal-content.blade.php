@php($inlinePreview = $inlinePreview ?? false)

<div class="flex items-start justify-between gap-4">
    <h2 @if($inlinePreview) x-text="draft.title || 'Modal title'" @else id="submission-modal-title" @endif
        class="min-w-0 break-words text-xl font-bold">{{ $inlinePreview ? 'Modal title' : $modal['title'] }}</h2>
    @unless($inlinePreview)
        <form method="dialog">
    @endunless
    <button type="{{ $inlinePreview ? 'button' : 'submit' }}" @if($inlinePreview) disabled @else autofocus @endif
        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold hover:bg-gray-50">
        Close
    </button>
    @unless($inlinePreview)
        </form>
    @endunless
</div>
@if($inlinePreview || filled($modal['body']))
    <p @if($inlinePreview) x-show="draft.body.trim()" x-text="draft.body" x-cloak @endif
        class="mt-3 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700">{{ $inlinePreview ? '' : $modal['body'] }}</p>
@endif

@if($inlinePreview || $modal['ad_type'] !== 'none')
    <div @if($inlinePreview) x-show="draft.ad_type !== 'none'" x-cloak @endif class="mt-5 rounded-lg border border-gray-200 p-4">
        @if($inlinePreview || $modal['ad_type'] === 'banner')
            <a @if($inlinePreview) x-show="draft.ad_type === 'banner'" aria-disabled="true"
                @else href="{{ $modal['ad_url'] }}" target="_blank" rel="noopener noreferrer sponsored" @endif>
                <img @if($inlinePreview)
                    x-show="/^https?:\/\//i.test(draft.ad_image)" :src="/^https?:\/\//i.test(draft.ad_image) ? draft.ad_image : null" :alt="draft.ad_title"
                    @else src="{{ $modal['ad_image'] }}" alt="{{ $modal['ad_title'] }}" @endif
                    loading="lazy" class="mx-auto max-h-64 max-w-full rounded object-contain">
            </a>
        @endif
        @if($inlinePreview || $modal['ad_type'] === 'card')
            <div @if($inlinePreview) x-show="draft.ad_type === 'card'" @endif class="flex items-start gap-3">
                <img @if($inlinePreview)
                    x-show="/^https?:\/\//i.test(draft.ad_image)" :src="/^https?:\/\//i.test(draft.ad_image) ? draft.ad_image : null"
                    @else src="{{ $modal['ad_image'] }}" @endif
                    alt="" loading="lazy" class="h-12 w-12 shrink-0 rounded-lg object-contain">
                <div class="min-w-0 space-y-2">
                    <h3 @if($inlinePreview) x-text="draft.ad_title || 'Ad title'" @endif
                        class="break-words font-semibold">{{ $inlinePreview ? '' : $modal['ad_title'] }}</h3>
                    @if($inlinePreview || filled($modal['ad_body'] ?? null))
                        <p @if($inlinePreview) x-show="draft.ad_body.trim()" x-text="draft.ad_body" @endif
                            class="whitespace-pre-wrap break-words text-sm text-gray-700">{{ $inlinePreview ? '' : $modal['ad_body'] }}</p>
                    @endif
                    <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium text-primary-700">
                        <a @if($inlinePreview) x-text="draft.ad_link_label || 'Destination link'" aria-disabled="true"
                            @else href="{{ $modal['ad_url'] }}" target="_blank" rel="noopener noreferrer sponsored" @endif
                            class="underline">{{ $inlinePreview ? '' : $modal['ad_link_label'] }}</a>
                        @if($inlinePreview || filled($modal['ad_secondary_url'] ?? null))
                            <a @if($inlinePreview) x-show="draft.ad_secondary_label.trim() && draft.ad_secondary_url.trim()" x-text="draft.ad_secondary_label" aria-disabled="true"
                                @else href="{{ $modal['ad_secondary_url'] }}" target="_blank" rel="noopener noreferrer sponsored" @endif
                                class="underline">{{ $inlinePreview ? '' : $modal['ad_secondary_label'] }}</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif

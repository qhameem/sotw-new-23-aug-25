@php
    $modalValue = fn (string $key) => old('submission_modal_'.$key, $submissionSuccessModal[$key] ?? '');
    $previewValues = collect(['title', 'body', 'ad_type', 'ad_title', 'ad_image', 'ad_body', 'ad_link_label', 'ad_secondary_label', 'ad_secondary_url',
        'offer_disclosure', 'offer_highlight', 'offer_benefits', 'offer_bullet', 'offer_promo', 'offer_code', 'offer_expiry', 'offer_price', 'offer_footer', 'offer_dismiss', 'offer_accent', 'offer_width', 'offer_button_width', 'offer_logo_background'])
        ->mapWithKeys(fn ($key) => [$key => (string) $modalValue($key)])->all();
    $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm';
@endphp

<div class="mt-5 grid items-start gap-5 xl:grid-cols-2" x-data="{ draft: @js($previewValues) }">
    <form method="POST" action="{{ route('admin.settings.submission-success-modal.update') }}"
        class="min-w-0 space-y-4 rounded-lg border border-gray-200 p-4">
        @csrf
        <div>
            <h4 class="font-semibold text-gray-900">Confirmation Modal</h4>
            <p class="mt-1 text-sm text-gray-600">
                @if($submissionSuccessModal['enabled'])
                    Enabled for submitters. Opens after {{ $submissionSuccessModal['delay'] }} seconds.
                @elseif(filled($submissionSuccessModal['title']))
                    Draft saved. Available in the admin previews.
                @else
                    No modal saved.
                @endif
            </p>
        </div>

        <input type="hidden" name="submission_modal_enabled" value="0">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <input type="checkbox" name="submission_modal_enabled" value="1"
                class="rounded border-gray-300 text-primary-600" @checked($modalValue('enabled'))>
            Show modal to submitters
        </label>

        <label class="block text-sm font-medium text-gray-700">
            Delay after page load (seconds)
            <input type="number" name="submission_modal_delay" value="{{ $modalValue('delay') }}"
                min="0" max="3600" step="1" required class="{{ $fieldClass }}">
        </label>
        @error('submission_modal_delay') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium text-gray-700">
            Modal title
            <input name="submission_modal_title" x-model="draft.title" value="{{ $modalValue('title') }}" maxlength="150" class="{{ $fieldClass }}">
        </label>
        @error('submission_modal_title') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium text-gray-700">
            Modal message
            <textarea name="submission_modal_body" x-model="draft.body" rows="4" maxlength="5000" class="{{ $fieldClass }}">{{ $modalValue('body') }}</textarea>
        </label>
        @error('submission_modal_body') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium text-gray-700">
            Advertisement
            <select name="submission_modal_ad_type" x-model="draft.ad_type" class="{{ $fieldClass }}">
                @foreach(['none' => 'None', 'banner' => 'Image banner', 'card' => 'Ad card', 'offer' => 'Sponsored Offer'] as $value => $label)
                    <option value="{{ $value }}" @selected($modalValue('ad_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        @error('submission_modal_ad_type') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <div x-show="draft.ad_type !== 'none'" x-cloak class="space-y-4">
            @foreach([
                'ad_title' => ['Ad title (required)', 'text', 150],
                'ad_image' => ['Image URL (required)', 'url', 2048],
                'ad_url' => ['Destination URL (required)', 'url', 2048],
            ] as $key => [$label, $type, $length])
                <label class="block text-sm font-medium text-gray-700">
                    {{ $label }}
                    <input type="{{ $type }}" name="submission_modal_{{ $key }}"
                        @if(array_key_exists($key, $previewValues)) x-model="draft.{{ $key }}" @endif value="{{ $modalValue($key) }}"
                        maxlength="{{ $length }}" :required="draft.ad_type !== 'none'" :disabled="draft.ad_type === 'none'" class="{{ $fieldClass }}">
                </label>
                @error('submission_modal_'.$key) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </div>

        <div x-show="draft.ad_type === 'card'" x-cloak class="space-y-4">
            <label class="block text-sm font-medium text-gray-700">
                Ad body
                <textarea name="submission_modal_ad_body" x-model="draft.ad_body" rows="3" maxlength="1000"
                    :disabled="draft.ad_type !== 'card'" class="{{ $fieldClass }}">{{ $modalValue('ad_body') }}</textarea>
            </label>
            @error('submission_modal_ad_body') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        </div>

        <div x-show="['card', 'offer'].includes(draft.ad_type)" x-cloak class="space-y-4">
            @foreach([
                'ad_link_label' => ['Destination link label (required)', 'text', 80],
                'ad_secondary_label' => ['Second link label (optional)', 'text', 80],
                'ad_secondary_url' => ['Second link URL (optional)', 'url', 2048],
            ] as $key => [$label, $type, $length])
                <label class="block text-sm font-medium text-gray-700">
                    {{ $label }}
                    <input type="{{ $type }}" name="submission_modal_{{ $key }}"
                        @if(array_key_exists($key, $previewValues)) x-model="draft.{{ $key }}" @endif value="{{ $modalValue($key) }}"
                        maxlength="{{ $length }}" :required="['card', 'offer'].includes(draft.ad_type) && @js($key === 'ad_link_label')" :disabled="!['card', 'offer'].includes(draft.ad_type)" class="{{ $fieldClass }}">
                </label>
                @error('submission_modal_'.$key) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </div>

        @include('admin.settings.partials.submission-success-offer-fields')

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-semibold text-[var(--color-primary-button-text)] hover:bg-primary-600">
                Save Modal
            </button>
            <button type="submit" name="submission_modal_action" value="preview"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                Save and Preview
            </button>
        </div>
    </form>
    <section aria-label="Live modal preview" class="min-w-0 xl:sticky xl:top-6">
        <h4 class="mb-3 font-semibold text-gray-900">Live Preview</h4>
        <div class="rounded-2xl bg-gray-100 p-3 sm:p-4">
            <div  :class="draft.ad_type === 'offer' && draft.offer_width === 'wide' ? 'max-w-2xl' : 'max-w-lg'"
                class="mx-auto rounded-2xl border border-gray-200 bg-white p-5 text-gray-900 shadow-xl sm:p-6">
                <div x-show="draft.ad_type !== 'offer'" x-cloak>
                    @include('products.partials._submission-success-modal-content', ['inlinePreview' => true])
                </div>
                <div x-show="draft.ad_type === 'offer'" x-cloak>
                    @include('products.partials._submission-success-offer', ['inlinePreview' => true])
                </div>
            </div>
        </div>
    </section>
</div>

<fieldset x-show="draft.ad_type === 'offer'" :disabled="draft.ad_type !== 'offer'" x-cloak
    class="min-w-0 space-y-4 rounded-lg border border-gray-200 p-4">
    <legend class="px-1 text-sm font-semibold text-gray-900">Sponsored Offer</legend>

    @foreach([
        'offer_disclosure' => ['Sponsor disclosure', 'text', 100],
        'offer_highlight' => ['Phrase to highlight in the modal title', 'text', 150],
        'offer_benefits' => ['Benefits (one per line, in display order)', 'textarea', 1500],
        'offer_bullet' => ['Benefit icon', ['check' => 'Checkmark', 'circled-check' => 'Circled checkmark', 'dot' => 'Dot', 'none' => 'None'], null],
        'offer_promo' => ['Promotion text', 'text', 160],
        'offer_code' => ['Coupon code', 'text', 50],
        'offer_expiry' => ['Promotion expiry date (site time)', 'date', null],
        'offer_price' => ['Pricing note', 'text', 150],
        'offer_footer' => ['Footer note or affiliate disclosure', 'textarea', 500],
        'offer_dismiss' => ['Dismiss link label', 'text', 50],
        'offer_accent' => ['Accent color (hex, blank uses theme)', 'text', 7],
        'offer_width' => ['Modal width', ['compact' => 'Compact', 'wide' => 'Wide'], null],
        'offer_button_width' => ['Primary button width', ['full' => 'Full width', 'auto' => 'Fit label'], null],
        'offer_logo_background' => ['Logo background color (hex, optional)', 'text', 7],
    ] as $key => [$label, $type, $length])
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if(is_array($type))
                <select name="submission_modal_{{ $key }}" x-model="draft.{{ $key }}" class="{{ $fieldClass }}">
                    @foreach($type as $value => $option)
                        <option value="{{ $value }}" @selected($modalValue($key) === $value)>{{ $option }}</option>
                    @endforeach
                </select>
            @elseif($type === 'textarea')
                <textarea name="submission_modal_{{ $key }}" x-model="draft.{{ $key }}" rows="3"
                    maxlength="{{ $length }}" class="{{ $fieldClass }}">{{ $modalValue($key) }}</textarea>
            @else
                <input type="{{ $type }}" name="submission_modal_{{ $key }}" x-model="draft.{{ $key }}"
                    value="{{ $modalValue($key) }}" @if(isset($length)) maxlength="{{ $length }}" @endif
                    @if(in_array($key, ['offer_accent', 'offer_logo_background'], true)) pattern="#[0-9a-fA-F]{6}" placeholder="#4f46e5" @endif
                    class="{{ $fieldClass }}">
            @endif
        </label>
        @error('submission_modal_'.$key) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    @endforeach
</fieldset>

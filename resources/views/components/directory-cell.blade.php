@props(['editable', 'field', 'value' => null, 'label'])

@if($editable)
    <button type="button" class="directory-edit-cell"
        data-directory-field="{{ $field }}" data-value="{{ $value }}" aria-label="Edit {{ $label }}">
        <span class="directory-cell-value">{{ $slot }}</span>
        <svg class="directory-pencil" aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="m14 5 5 5M4 20l4.5-1 11-11a2 2 0 0 0-4-4l-11 11L4 20Z" />
        </svg>
    </button>
@else
    {{ $slot }}
@endif

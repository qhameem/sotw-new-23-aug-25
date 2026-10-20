@props(['label', 'index', 'editable' => false])
@if($editable)
    @php($featureErrors = $errors->getBag('homeHeroFeature'.$index))
    <div x-data="{ editingFeature: @js($featureErrors->any()) }">
        <button type="button" x-show="!editingFeature" @click="editingFeature = true; $nextTick(() => $refs.label.focus())" :aria-expanded="editingFeature" aria-controls="hero-feature-editor-{{ $index }}" title="Edit feature label" style="color: #666666 !important;" class="rounded text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500">
            {{ $label }}
            <span class="sr-only">Edit feature label</span>
        </button>
        <form id="hero-feature-editor-{{ $index }}" action="{{ route('admin.home-hero.features.update', ['feature' => $index]) }}" method="POST" x-show="editingFeature" x-cloak @keydown.escape.prevent="editingFeature = false; $el.reset()" class="space-y-2 rounded-lg border border-gray-200 p-3">
            @csrf
            @method('PATCH')
            <label for="hero-feature-label-{{ $index }}" class="sr-only">Feature label {{ $index + 1 }}</label>
            <input id="hero-feature-label-{{ $index }}" x-ref="label" name="label" value="{{ $featureErrors->any() ? old('label', $label) : $label }}" required maxlength="150" class="w-full rounded-lg border-gray-300 text-sm">
            @foreach($featureErrors->all() as $error)
                <p role="alert" class="text-sm text-red-600">{{ $error }}</p>
            @endforeach
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-[var(--color-primary-button-text)]">Save</button>
                <button type="button" @click="editingFeature = false; $el.form.reset()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">Cancel</button>
            </div>
        </form>
        @if(session('home_hero_feature_saved') === $index)
            <x-admin-save-message class="sr-only">Feature label saved.</x-admin-save-message>
        @endif
    </div>
@else
    <span style="color: #666666 !important;">{{ $label }}</span>
@endif

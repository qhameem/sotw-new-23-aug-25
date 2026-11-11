<details class="my-3 text-sm" @if($errors->any() && old('field') === $field) open @endif>
    <summary class="cursor-pointer font-medium">Edit {{ $label }}</summary>
    <form method="POST" action="{{ route('admin.pages.directory-content', $page) }}" class="mt-2 space-y-2">
        @csrf
        @method('PATCH')
        <input type="hidden" name="field" value="{{ $field }}">
        @if(in_array($field, ['title', 'directory_heading'], true))
            <input aria-label="{{ ucfirst($label) }}" name="value" value="{{ old('field') === $field ? old('value') : $currentValue }}" class="w-full rounded border" maxlength="255" required>
        @else
            <textarea aria-label="{{ ucfirst($label) }}" name="value" class="w-full rounded border" rows="8" maxlength="{{ $field === 'body' ? 100000 : 2000 }}">{{ old('field') === $field ? old('value') : $currentValue }}</textarea>
        @endif
        <p class="text-gray-500">Use [year] for the current year. @if($field === 'body') Page content supports Markdown. @endif</p>
        <button class="rounded border px-3 py-1.5" type="submit">Save {{ $label }}</button>
    </form>
</details>

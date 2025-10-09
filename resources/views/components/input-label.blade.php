@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs text-gray-700']) }}>
    {{ $value ?? $slot }}
</label>

@props(['dayOfYear', 'fullDate'])

<div class="px-4 py-2 bg-gradient-to-t from-white to-stone-50">
    <h2 class="text-lg font-medium font-noto-serif text-gray-800">Day {{ $dayOfYear }}</h2>
    <p class="text-xs text-gray-500">{{ $fullDate }}</p>
</div>
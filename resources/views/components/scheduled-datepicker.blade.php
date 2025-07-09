@props(['name' => 'published_at', 'value' => ''])

@php
    $minDate = \Carbon\Carbon::now('UTC')->hour >= 7 ? \Carbon\Carbon::tomorrow('UTC')->toDateString() : \Carbon\Carbon::today('UTC')->toDateString();
@endphp

<input type="date" 
       name="{{ $name }}" 
       value="{{ old($name, $value) }}" 
       min="{{ $minDate }}"
       {{ $attributes->merge(['class' => 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50']) }}>
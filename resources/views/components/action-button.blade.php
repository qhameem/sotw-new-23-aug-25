@props(['href' => '#'])

<a {{ $attributes->merge(['href' => $href, 'class' => 'bg-white hover:bg-rose-50 text-primary-500 border border-primary-500 text-sm font-semibold py-1 px-3 rounded-md transition duration-300 shadow inline-flex items-center justify-center gap-2']) }}>
    {{ $slot }} &rarr;
</a>
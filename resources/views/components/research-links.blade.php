@if($links->isNotEmpty())
    <section {{ $attributes->class(['my-8 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6']) }} aria-label="{{ $heading }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">{{ $heading }}</h2>
            <a href="{{ route('alternatives.index') }}" class="text-sm font-medium text-primary-700 hover:underline">Browse all alternatives</a>
        </div>
        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach($links as $link)
                <li><a href="{{ $link['url'] }}" class="block rounded-lg border border-gray-100 px-4 py-3 text-sm font-medium text-gray-800 hover:border-primary-300 hover:text-primary-700">{{ $link['label'] }}</a></li>
            @endforeach
        </ul>
    </section>
@endif

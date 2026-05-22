@if(!empty($sectionNavItems))
    <nav class="sticky top-[4.5rem] z-20 mb-8 overflow-x-auto rounded-2xl border border-gray-200 bg-white/95 px-3 py-3 backdrop-blur">
        <div class="flex min-w-max gap-2">
            @foreach($sectionNavItems as $item)
                <a href="#{{ $item['id'] }}"
                    class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:border-gray-300 hover:bg-white hover:text-gray-900">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endif

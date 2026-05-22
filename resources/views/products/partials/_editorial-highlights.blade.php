@php
    $highlightCards = collect([
        ['title' => 'Key Features', 'items' => array_slice($productEditorial['key_features'] ?? [], 0, 4)],
        ['title' => 'Best For', 'items' => array_slice($productEditorial['ideal_for'] ?? [], 0, 4)],
        ['title' => 'Top Use Cases', 'items' => array_slice($productEditorial['top_use_cases'] ?? [], 0, 4)],
        ['title' => 'Integrations', 'items' => array_slice($productEditorial['integrations'] ?? [], 0, 4)],
        ['title' => 'Pros', 'items' => array_slice($productEditorial['pros'] ?? [], 0, 4)],
        ['title' => 'Limitations', 'items' => array_slice($productEditorial['limitations'] ?? [], 0, 4)],
    ])->filter(fn($card) => !empty($card['items']))->values();
@endphp

@if($highlightCards->isNotEmpty())
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($highlightCards as $card)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">{{ $card['title'] }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-600">
                    @foreach($card['items'] as $item)
                        <li class="flex gap-2">
                            <span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary-500"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>
@endif

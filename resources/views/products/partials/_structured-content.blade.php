@if($product->media->isNotEmpty())
    @include('products.partials._document-media', ['mode' => 'cover'])
@endif

@if($usesProductFacts || $pageSections['about_html'] || filled($product->summary))
    <x-products.detail-section id="about" :title="'About '.$product->name">
        @if($usesProductFacts)
            <ul class="list-disc space-y-2 pl-5">
                @foreach($product->product_facts as $fact)<li>{{ $fact }}</li>@endforeach
            </ul>
        @else
            <div class="product-prose">
                @foreach($pageSections['about_html'] as $block)
                    {!! \App\Support\OutboundLink::sanitizeHtml($block, 'product_description') !!}
                @endforeach
                @if(filled($product->summary) && !str_contains(strip_tags(implode(' ', $pageSections['about_html'])), $product->summary))
                    <p>{{ $product->summary }}</p>
                @endif
            </div>
        @endif
    </x-products.detail-section>
@endif

@if($product->media->count() > 1 || ($product->video_url && $product->getEmbedUrl()))
    <x-products.detail-section id="gallery" title="Gallery" tone="violet">
        @include('products.partials._document-media', ['mode' => 'gallery'])
    </x-products.detail-section>
@endif

@php
    $overviewRows = $pageSections['overview'];
    if (!collect($overviewRows)->contains('key', 'audience') && $bestForCategories->isNotEmpty()) {
        array_unshift($overviewRows, ['key' => 'audience', 'title' => 'Who is it for?', 'text' => $bestForCategories->pluck('name')->implode(', ')]);
    }
@endphp
@if($overviewRows)
    <x-products.detail-section id="overview" title="Overview" tone="sky">
        <div class="product-rows">
            @foreach($overviewRows as $row)
                <div class="product-overview-row">
                    <span class="product-row-icon" aria-hidden="true">{{ ['audience' => '◎', 'problem' => '?', 'solution' => '↗', 'unique' => '✦'][$row['key']] }}</span>
                    <div><h3>{{ $row['title'] }}</h3><p>{{ $row['text'] }}</p></div>
                </div>
            @endforeach
        </div>
    </x-products.detail-section>
@endif

@if($pageSections['features'])
    <x-products.detail-section id="features" title="Features" tone="sky">
        <ul class="list-disc space-y-2 pl-5">@foreach($pageSections['features'] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
    </x-products.detail-section>
@endif

@php
    $useCases = $useCaseCategories->map(fn ($category) => ['text' => $category->name, 'url' => $category->publicUrl()]);
    foreach ($pageSections['use_cases'] as $useCase) {
        if (!$useCases->contains(fn ($item) => mb_strtolower($item['text']) === mb_strtolower($useCase))) {
            $useCases->push(['text' => $useCase, 'url' => null]);
        }
    }
@endphp
@if($useCases->isNotEmpty())
    <x-products.detail-section id="use-cases" title="Use cases" tone="rose">
        <ol class="product-rows">
            @foreach($useCases as $useCase)
                <li class="flex items-center gap-3"><span class="product-number" aria-hidden="true">{{ $loop->iteration }}</span>
                    @if($useCase['url'])<a href="{{ $useCase['url'] }}" class="hover:underline">{{ $useCase['text'] }}</a>@else<span>{{ $useCase['text'] }}</span>@endif
                </li>
            @endforeach
        </ol>
    </x-products.detail-section>
@endif

@unless($product->user->hasRole('admin'))
    <x-products.detail-section id="submitted-by" title="Submitted by" tone="violet">
        <div class="flex items-center gap-3">
            <img src="{{ $product->user->avatar() }}" alt="" width="36" height="36" class="h-9 w-9 rounded-lg" loading="lazy">
            <span class="font-medium">{{ $product->user->name }}</span>
        </div>
    </x-products.detail-section>
@endunless

@include('products.partials._structured-details')

@if(filled($pageSections['details_html']) || (isset($isAdminView) && $isAdminView))
    <x-products.detail-section id="editorial-details" title="More details">
        <div class="product-prose product-detail-description">
            @if(isset($isAdminView) && $isAdminView)
                <div x-show="!editingDescription" @click="editingDescription = true">{!! \App\Support\OutboundLink::sanitizeHtml($product->description ?? '', 'product_description') !!}</div>
                <textarea x-show="editingDescription" x-model="description" @keydown.escape="editingDescription = false" class="form-input w-full" rows="12" aria-label="Product description"></textarea>
                <button type="button" x-show="editingDescription" @click="updateProduct(); editingDescription = false" class="mt-2 text-sm font-semibold">Save description</button>
            @else
                {!! \App\Support\OutboundLink::sanitizeHtml($pageSections['details_html'], 'product_description') !!}
            @endif
        </div>
    </x-products.detail-section>
@endif
@if(filled($product->not_for_text))
    <x-products.detail-section id="limitations" title="Limitations"><p>{{ $product->not_for_text }}</p></x-products.detail-section>
@endif

@if($alternativeProducts->isNotEmpty())
    <x-products.detail-section id="alternatives" title="Alternatives" tone="cyan">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pseo.alternatives', $product->slug) }}" class="product-chip">{{ $product->name }} alternatives</a>
            @foreach($alternativeProducts as $alternative)
                <a href="{{ route('products.show', $alternative->slug) }}" class="product-chip">{{ $alternative->name }}</a>
            @endforeach
        </div>
    </x-products.detail-section>
@endif

@php($comparisonLinks = app(\App\Services\ProductResearchService::class)->forProducts([$product], 6, false))
@if($comparisonLinks->isNotEmpty())
    <x-products.detail-section id="comparisons" title="Compare" tone="cyan">
        <div class="flex flex-wrap gap-2">@foreach($comparisonLinks as $link)<a href="{{ $link['url'] }}" class="product-chip">{{ $link['label'] }}</a>@endforeach</div>
    </x-products.detail-section>
@endif

@if($hasResourcesSection)
    <x-products.detail-section id="resources" title="Resources">@include('products.partials._resource-links', ['product' => $product])</x-products.detail-section>
@endif

@if($productFaqItems->isNotEmpty())
    <x-products.detail-section id="faq" :title="'FAQ about '.$product->name" tone="sky">
        <div class="product-rows product-faq">
            @foreach($productFaqItems as $faqItem)
                <details><summary>{{ $faqItem['question'] }}</summary><p>{{ $faqItem['answer'] }}</p></details>
            @endforeach
        </div>
    </x-products.detail-section>
@endif

@if($alternativeProducts->isNotEmpty())
    <x-products.detail-section id="similar-products" title="Similar products" tone="violet">
        <div class="product-rows">
            @foreach($alternativeProducts as $alternative)
                @php($alternativeLogo = \App\Support\ProductLogo::url($alternative))
                <a href="{{ route('products.show', $alternative->slug) }}" class="product-similar-row">
                    @if($alternativeLogo)<img src="{{ $alternativeLogo }}" alt="" class="h-10 w-10 shrink-0 rounded-lg border border-gray-100 object-contain" loading="lazy" width="40" height="40">
                    @else<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 font-semibold" aria-hidden="true">{{ \App\Support\ProductLogo::initial($alternative) }}</span>@endif
                    <span class="min-w-0"><span class="block text-sm font-semibold">{{ $alternative->name }}</span><span class="mt-0.5 block text-xs text-gray-500">{{ $alternative->product_page_tagline ?: $alternative->tagline }}</span></span>
                    <span class="ml-auto shrink-0 text-gray-400" aria-hidden="true">↗</span>
                </a>
            @endforeach
        </div>
    </x-products.detail-section>
@endif

@php
    use App\Support\ProductLogo;

    $mainContentMaxWidth = 'max-w-none';
    $containerMaxWidth = 'max-w-none';
    $hideSidebar = true;
    $mainPadding = 'px-0';
    $headerPadding = 'px-0';
@endphp

@extends('layouts.app')

@section('title', $collection->name . ' by ' . ($collection->user->name ?? 'Member') . ' | Collections')
@section('meta_description', trim($collection->name . ' is a public collection curated by ' . ($collection->user->name ?? 'a member') . '. Browse saved products and notes in one place.'))
@section('robots', $collection->isPublic() ? 'index, follow, max-image-preview:large' : 'noindex, nofollow')
@section('canonical')
    <link rel="canonical" href="{{ route('collections.show', $collection->publicRouteParameters()) }}" />
@endsection

@section('content')
    <div class="mx-auto w-full max-w-[1500px] px-4 py-8 sm:px-6 lg:px-10 xl:px-12">
        <div class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-[0_24px_80px_-48px_rgba(15,23,42,0.35)]">
            <div class="bg-white px-6 py-8 sm:px-8 lg:px-10 xl:px-12">
                <div class="flex flex-col gap-8 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-4xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-gray-400">Collection</p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <h1 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl xl:text-[2.25rem] xl:leading-[1.08]">
                                {{ $collection->name }}
                            </h1>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $collection->visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($collection->visibility) }}
                            </span>
                        </div>
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                            Curated {{ trans_choice('{1} 1 tool|[2,*] :count tools', $collection->items->count(), ['count' => $collection->items->count()]) }} by {{ $collection->user->name ?? 'Member' }}. Updated {{ $collection->updated_at->diffForHumans() }}.
                        </p>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-3"
                        x-data="{
                            copied: false,
                            copyTimeout: null,
                            async copyCollectionUrl() {
                                const url = @js(route('collections.show', $collection->publicRouteParameters()));

                                try {
                                    if (navigator.clipboard && window.isSecureContext) {
                                        await navigator.clipboard.writeText(url);
                                    } else {
                                        const textarea = document.createElement('textarea');
                                        textarea.value = url;
                                        textarea.setAttribute('readonly', '');
                                        textarea.style.position = 'absolute';
                                        textarea.style.left = '-9999px';
                                        document.body.appendChild(textarea);
                                        textarea.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(textarea);
                                    }

                                    this.copied = true;
                                    clearTimeout(this.copyTimeout);
                                    this.copyTimeout = setTimeout(() => this.copied = false, 2000);
                                } catch (error) {
                                    console.error('Failed to copy collection URL:', error);
                                }
                            }
                        }"
                    >
                        @if($isOwner)
                            <a href="{{ route('collections.index') }}" class="inline-flex items-center justify-center rounded-full bg-gray-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800">
                                Manage Collections
                            </a>
                        @endif

                        <button
                            type="button"
                            @click="copyCollectionUrl()"
                            class="inline-flex w-[14rem] items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 text-[1.15rem] font-medium text-slate-600 transition-colors duration-300 hover:border-gray-400 hover:text-slate-800"
                            :class="copied ? 'text-emerald-600' : 'text-slate-600'"
                            aria-live="polite"
                        >
                            <span class="mx-auto inline-flex items-center justify-center gap-2.5">
                                <span
                                    class="inline-flex items-center justify-center transition-all duration-300 ease-out"
                                    :class="copied ? 'scale-105' : 'scale-100'"
                                >
                                    <svg class="h-[1.35rem] w-[1.35rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 16V4"></path>
                                        <path d="m7 9 5-5 5 5"></path>
                                        <path d="M20 15v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4"></path>
                                    </svg>
                                </span>
                                <span class="relative inline-flex items-center justify-center text-center leading-none">
                                    <span class="invisible font-semibold">Link copied</span>
                                    <span
                                        x-show="!copied"
                                        x-transition:enter="transition ease-out duration-350"
                                        x-transition:enter-start="opacity-0 translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-250"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-1"
                                        class="absolute inset-0 inline-flex items-center justify-center whitespace-nowrap"
                                    >
                                        Share
                                    </span>
                                    <span
                                        x-cloak
                                        x-show="copied"
                                        x-transition:enter="transition ease-out duration-350"
                                        x-transition:enter-start="opacity-0 translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-300"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-1"
                                        class="absolute inset-0 inline-flex items-center justify-center whitespace-nowrap font-semibold text-emerald-600"
                                    >
                                        Link copied
                                    </span>
                                </span>
                            </span>
                        </button>
                    </div>
                </div>

            </div>

            @if(in_array(session('status'), ['collection-item-updated', 'collection-item-removed'], true))
                <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 sm:mx-8 lg:mx-10 xl:mx-12">
                    {{ session('status') === 'collection-item-updated' ? 'Saved note updated.' : 'Product removed from this collection.' }}
                </div>
            @endif

            <div class="px-6 py-6 sm:px-8 lg:px-10 xl:px-12">
                <div class="overflow-hidden rounded-[1.75rem] border border-gray-200 bg-white shadow-sm">
                    @forelse($collection->items as $item)
                        @php
                            $savedProduct = $item->product;
                            $productLogo = $savedProduct ? ProductLogo::storedUrl($savedProduct) : null;
                            $productTagline = $savedProduct?->product_page_tagline ?: $savedProduct?->tagline;
                            $useCaseCategories = $savedProduct?->categories
                                ?->filter(fn ($category) => $category->types->contains(fn ($type) => in_array($type->name, ['Use Case', 'Use Cases'], true)))
                                ->take(3);
                        @endphp

                        @continue(!$savedProduct)

                        <article class="@if(!$loop->last) border-b border-gray-100 @endif">
                            <div class="px-5 py-5 sm:px-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                        @if($productLogo)
                                            <img src="{{ $productLogo }}" alt="{{ $savedProduct->name }} logo" class="h-12 w-12 rounded-xl object-contain">
                                        @else
                                            <span class="text-lg font-semibold text-gray-500">{{ ProductLogo::initial($savedProduct) }}</span>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <a href="{{ route('products.show', $savedProduct->slug) }}" class="block truncate text-xl font-semibold tracking-tight text-gray-900 transition hover:text-primary-600">
                                                    {{ $savedProduct->name }}
                                                </a>

                                                @if(filled($productTagline))
                                                    <p class="mt-2 text-sm leading-6 text-gray-600">
                                                        {{ $productTagline }}
                                                    </p>
                                                @endif

                                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                                    @foreach($useCaseCategories ?? [] as $useCaseCategory)
                                                        <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                                            {{ $useCaseCategory->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="flex shrink-0 items-center gap-2 pt-1 text-gray-400">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <rect x="2" y="10" width="3" height="8" rx="1"></rect>
                                                    <rect x="8.5" y="6" width="3" height="12" rx="1"></rect>
                                                    <rect x="15" y="2" width="3" height="16" rx="1"></rect>
                                                </svg>
                                                <span class="text-xl font-medium text-gray-500">{{ number_format((int) $savedProduct->votes_count) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($isOwner || filled($item->comment))
                                    <div class="mt-5 border-t border-gray-100 pt-4">
                                        @if($isOwner)
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                                <form method="POST" action="{{ route('collections.items.update', [$collection, $savedProduct]) }}" class="flex-1 space-y-3">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div>
                                                        <label for="comment-{{ $item->id }}" class="text-sm font-medium text-gray-700">Note</label>
                                                        <textarea id="comment-{{ $item->id }}" name="comment" rows="3" class="mt-2 block w-full rounded-2xl border-gray-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Why did you save this?">{{ $item->comment }}</textarea>
                                                    </div>

                                                    <x-primary-button>{{ __('Save Note') }}</x-primary-button>
                                                </form>

                                                <form method="POST" action="{{ route('collections.items.destroy', [$collection, $savedProduct]) }}" onsubmit="return confirm('Remove this product from the collection?');" class="shrink-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-full border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif(filled($item->comment))
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Saved Note</p>
                                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $item->comment }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <p class="text-2xl font-semibold tracking-tight text-gray-900">No tools saved yet</p>
                            <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-gray-600">
                                This collection is empty for now. Add products to start building a shareable, easy-to-scan tools list.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

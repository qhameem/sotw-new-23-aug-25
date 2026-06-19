@extends('layouts.app', [
    'mainContentMaxWidth' => 'max-w-none',
    'containerMaxWidth' => 'max-w-none',
    'hideSidebar' => true,
    'headerPadding' => 'px-4 sm:px-6 lg:px-10 xl:px-12',
    'mainPadding' => 'px-0',
])

@section('title', 'My Products | Software on the Web')

@section('hide_desktop_page_header')
    1
@endsection

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 pb-10 sm:px-6 lg:px-10 xl:px-12">
        <div class="rounded-lg bg-slate-50 p-4 sm:p-6 lg:p-8">
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900 md:text-4xl">My Products</h1>
                    <p class="text-sm text-slate-600">Compact cards by default. Click a card to expand. Click editable sections to update them.</p>
                </div>

                <div class="flex items-center gap-2 self-start rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <label for="per_page" class="text-sm font-medium text-slate-600">Show</label>
                    <select
                        id="per_page"
                        name="per_page"
                        onchange="window.location.href = this.value;"
                        class="rounded border-slate-200 text-sm shadow-none focus:border-sky-300 focus:ring focus:ring-sky-100"
                    >
                        @foreach ($allowedPerPages as $option)
                            <option value="{{ route('products.my', ['per_page' => $option]) }}" {{ $perPage == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($products->isEmpty())
                <div class="rounded-lg border border-dashed border-slate-300 bg-white/80 px-6 py-16 text-center shadow-sm">
                    <p class="text-xl font-semibold text-slate-900">You haven't submitted any products yet.</p>
                    <a href="{{ route('products.create') }}" class="mt-3 inline-flex rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">
                        Submit your first product
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($products as $product)
                        @php
                            $fallbackLogo = $product->link
                                ? 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link)
                                : null;
                        @endphp

                        <article
                            x-data="productEditor({
                                product: {{ json_encode(array_merge($product->only(['id', 'name', 'tagline', 'product_page_tagline', 'description', 'link', 'x_account', 'sell_product', 'asking_price', 'maker_links', 'video_url', 'slug', 'approved']), ['logo_url' => $product->logo_url, 'fallback_logo_url' => $fallbackLogo, 'logo_initial' => \App\Support\ProductLogo::initial($product), 'x_handle' => \App\Models\Product::normalizeXAccount($product->x_account), 'x_profile_url' => \App\Models\Product::xProfileUrl($product->x_account)])) }},
                                categories: {{ $product->categories->pluck('id')->toJson() }},
                                tech_stacks: {{ $product->techStacks->pluck('id')->toJson() }},
                                media: {{ $product->media->map->only(['id', 'path', 'type', 'alt_text'])->toJson() }}
                            })"
                            @description-updated.window="if ($event.detail.id === product.id) tempValue = $event.detail.content"
                            class="relative overflow-hidden rounded-lg border border-white/80 bg-white/90 shadow-sm backdrop-blur"
                            itemscope
                            itemtype="https://schema.org/Product"
                        >
                            <div x-show="loading" class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-sm">
                                <svg class="h-8 w-8 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <button
                                type="button"
                                @click="toggleExpanded()"
                                class="w-full px-4 py-4 text-left sm:px-6 sm:py-5"
                                :aria-expanded="expanded.toString()"
                            >
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm md:h-12 md:w-12">
                                            <template x-if="displayLogo()">
                                                <img :src="displayLogo()" alt="" class="h-full w-full object-cover" itemprop="image">
                                            </template>
                                            <template x-if="!displayLogo()">
                                                <span class="text-sm font-semibold text-slate-500" x-text="product.logo_initial"></span>
                                            </template>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h2 class="truncate text-lg font-semibold tracking-tight text-slate-950 sm:text-xl" itemprop="name" x-text="product.name"></h2>
                                                <span
                                                    class="inline-flex h-6 w-fit items-center justify-center gap-1 rounded-2xl border px-[11px] text-sm font-medium"
                                                    :class="product.approved
                                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                        : 'border-amber-200 bg-amber-50 text-amber-700'"
                                                    x-text="product.approved ? 'Approved' : 'Pending'"
                                                ></span>
                                            </div>

                                            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600" itemprop="tagline" x-text="displayTagline()"></p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 text-sm font-medium text-slate-500">
                                        <span x-text="expanded ? 'Hide details' : 'Show details'"></span>
                                        <svg class="h-5 w-5 transition-transform duration-200" :class="expanded ? 'rotate-180 text-slate-700' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                        </svg>
                                    </div>
                                </div>
                            </button>

                            <div x-show="expanded" x-transition.opacity.duration.200ms class="border-t border-slate-200/80 px-4 pb-4 pt-4 sm:px-6 sm:pb-6">
                                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <p class="text-xs font-medium uppercase tracking-[0.18em] text-slate-400">Editable sections open on click</p>

                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($product->approved)
                                            <a
                                                href="{{ route('products.show', ['product' => $product->slug]) }}"
                                                class="inline-flex items-center rounded border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                                @click.stop
                                            >
                                                View product page
                                            </a>
                                        @endif

                                        <a
                                            :href="product.link"
                                            target="_blank"
                                            rel="noopener nofollow"
                                            class="inline-flex items-center rounded bg-slate-900 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-slate-700"
                                            @click.stop
                                        >
                                            Visit website
                                        </a>
                                    </div>
                                </div>

                                <div class="grid gap-4 xl:grid-cols-[280px,minmax(0,1fr)]">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 shadow-sm">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Logo</p>
                                        <div class="mt-4 flex flex-col items-center rounded-lg border border-dashed border-slate-300 bg-white p-4 text-center">
                                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 shadow-sm">
                                                <template x-if="displayLogo()">
                                                    <img :src="displayLogo()" alt="" class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!displayLogo()">
                                                    <span class="text-2xl font-semibold text-slate-500" x-text="product.logo_initial"></span>
                                                </template>
                                            </div>
                                            <label class="mt-4 inline-flex cursor-pointer rounded border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm font-medium text-sky-700 transition hover:border-sky-300 hover:bg-sky-100">
                                                Replace logo
                                                <input type="file" class="hidden" @change="uploadLogo">
                                            </label>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 shadow-sm">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Product name</p>
                                            <p class="mt-3 text-lg font-semibold text-slate-950" x-text="product.name"></p>
                                            <p class="mt-1 text-sm text-slate-400">Locked</p>
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 shadow-sm">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Software on the Web URL</p>
                                            <p class="mt-3 break-all text-sm font-medium text-slate-700">
                                                <span class="text-slate-400">softwareontheweb.com/</span>
                                                <span x-text="product.slug"></span>
                                            </p>
                                            <p class="mt-1 text-sm text-slate-400">Locked</p>
                                        </div>

                                        <div
                                            class="md:col-span-2"
                                            :class="fieldCardClasses('product_page_tagline', true)"
                                            @click="editingField !== 'product_page_tagline' && startEdit('product_page_tagline', product.product_page_tagline || product.tagline || '')"
                                        >
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Tagline</p>

                                            <div x-show="editingField !== 'product_page_tagline'" class="mt-3">
                                                <p class="text-sm leading-6 text-slate-700" x-text="displayTagline()"></p>
                                            </div>

                                            <div x-show="editingField === 'product_page_tagline'" class="mt-3 flex flex-col gap-3" @click.stop>
                                                <input
                                                    type="text"
                                                    x-model="tempValue"
                                                    class="w-full rounded border-slate-300 text-sm shadow-none focus:border-sky-300 focus:ring focus:ring-sky-100"
                                                    placeholder="Enter tagline"
                                                >
                                                <div class="flex justify-end gap-2">
                                                    <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                    <button @click="save('product_page_tagline')" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div
                                        class="lg:col-span-2"
                                        :class="fieldCardClasses('description', true)"
                                        @click="editingField !== 'description' && startEdit('description', product.description)"
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Description</p>

                                        <div x-show="editingField !== 'description'" class="prose prose-sm mt-3 max-w-none text-sm text-slate-600" itemprop="description" x-html="product.description"></div>

                                        <div
                                            x-show="editingField === 'description'"
                                            class="mt-3"
                                            @click.stop
                                            x-init="$watch('editingField', value => {
                                                if (value === 'description') {
                                                    $nextTick(() => {
                                                        const editorId = 'editor-' + product.id;
                                                        if (window.Quill) {
                                                            const quill = new Quill('#' + editorId, {
                                                                theme: 'snow',
                                                                modules: {
                                                                    toolbar: [
                                                                        [{ header: [2, 3, false] }],
                                                                        ['bold', 'italic', 'underline'],
                                                                        [{ list: 'ordered' }, { list: 'bullet' }],
                                                                        ['link', 'clean']
                                                                    ]
                                                                }
                                                            });
                                                            quill.root.innerHTML = tempValue;
                                                            quill.on('text-change', () => {
                                                                $dispatch('description-updated', { id: product.id, content: quill.root.innerHTML });
                                                            });
                                                        }
                                                    });
                                                }
                                            })"
                                        >
                                            <div :id="'editor-' + product.id" class="bg-white" style="height: 220px;"></div>
                                            <div class="mt-3 flex justify-end gap-2">
                                                <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                <button @click="save('description')" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        :class="fieldCardClasses('video_url', true)"
                                        @click="editingField !== 'video_url' && startEdit('video_url', product.video_url)"
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Video URL</p>

                                        <div x-show="editingField !== 'video_url'" class="mt-3 text-sm">
                                            <template x-if="product.video_url">
                                                <a :href="product.video_url" target="_blank" rel="noopener nofollow" class="break-all text-sky-700 hover:underline" @click.stop x-text="product.video_url"></a>
                                            </template>
                                            <template x-if="!product.video_url">
                                                <span class="text-slate-400">None</span>
                                            </template>
                                        </div>

                                        <div x-show="editingField === 'video_url'" class="mt-3 flex flex-col gap-3" @click.stop>
                                            <input
                                                type="url"
                                                x-model="tempValue"
                                                class="w-full rounded border-slate-300 text-sm shadow-none focus:border-sky-300 focus:ring focus:ring-sky-100"
                                                placeholder="https://youtube.com/..."
                                            >
                                            <div class="flex justify-end gap-2">
                                                <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                <button @click="save('video_url')" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 shadow-sm">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Website</p>
                                        <a :href="product.link" target="_blank" rel="noopener nofollow" class="mt-3 block break-all text-sm text-sky-700 hover:underline" @click.stop x-text="product.link"></a>
                                        <p class="mt-1 text-sm text-slate-400">Locked</p>
                                    </div>

                                    <div
                                        :class="fieldCardClasses('maker_links', true)"
                                        @click="editingField !== 'maker_links' && startEdit('maker_links', product.maker_links || [])"
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Maker links</p>

                                        <div x-show="editingField !== 'maker_links'" class="mt-3 flex flex-wrap gap-2">
                                            <template x-if="!product.maker_links || product.maker_links.length === 0">
                                                <span class="text-sm text-slate-400">None</span>
                                            </template>
                                            <template x-for="link in product.maker_links" :key="link">
                                                <a :href="link" target="_blank" rel="noopener nofollow" class="inline-flex h-6 w-fit items-center justify-center gap-1 break-all rounded-2xl border border-sky-100 bg-sky-50 px-[11px] text-sm text-sky-700 hover:underline" @click.stop x-text="link"></a>
                                            </template>
                                        </div>

                                        <div x-show="editingField === 'maker_links'" class="mt-3 space-y-2" @click.stop>
                                            <template x-for="(link, index) in tempValue" :key="index">
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        type="url"
                                                        x-model="tempValue[index]"
                                                        class="w-full rounded border-slate-300 text-sm shadow-none focus:border-sky-300 focus:ring focus:ring-sky-100"
                                                        placeholder="https://..."
                                                    >
                                                    <button @click="tempValue.splice(index, 1)" class="rounded bg-rose-50 p-2 text-rose-600">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>

                                            <div class="flex items-center justify-between pt-1">
                                                <button @click="tempValue.push('')" class="text-sm font-medium text-sky-700">Add link</button>
                                                <div class="flex gap-2">
                                                    <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                    <button @click="save('maker_links')" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        :class="fieldCardClasses('x_account', true)"
                                        @click="editingField !== 'x_account' && startEdit('x_account', product.x_handle || product.x_account)"
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">X account</p>

                                        <div x-show="editingField !== 'x_account'" class="mt-3 text-sm text-slate-700">
                                            <a x-show="product.x_profile_url" :href="product.x_profile_url" target="_blank" rel="noopener nofollow" class="text-sky-700 hover:underline" @click.stop>@<span x-text="product.x_handle"></span></a>
                                            <span x-show="!product.x_profile_url" class="text-slate-400">None</span>
                                        </div>

                                        <div x-show="editingField === 'x_account'" class="mt-3 flex flex-col gap-3" @click.stop>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400">@</span>
                                                <input
                                                    type="text"
                                                    x-model="tempValue"
                                                    class="w-full rounded border-slate-300 py-2 pl-8 pr-3 text-sm shadow-none focus:border-sky-300 focus:ring focus:ring-sky-100"
                                                    placeholder="username"
                                                >
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                <button @click="save('x_account')" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        :class="fieldCardClasses('selling', true)"
                                        @click="editingField !== 'selling' && (editingField = 'selling', tempValue = { sell_product: product.sell_product, asking_price: product.asking_price })"
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Selling info</p>

                                        <div x-show="editingField !== 'selling'" class="mt-3 text-sm text-slate-700">
                                            <span x-text="product.sell_product ? 'For sale' : 'Not for sale'"></span>
                                            <template x-if="product.sell_product && product.asking_price">
                                                <span x-text="' - Asking Price: $' + formatPrice(product.asking_price)"></span>
                                            </template>
                                        </div>

                                        <div x-show="editingField === 'selling'" class="mt-3 space-y-3" @click.stop>
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" x-model="tempValue.sell_product" class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                                <span>Allow for sale</span>
                                            </label>

                                            <div x-show="tempValue.sell_product" class="flex items-center gap-2">
                                                <span class="text-sm text-slate-500">$</span>
                                                <input
                                                    type="number"
                                                    x-model="tempValue.asking_price"
                                                    step="0.01"
                                                    class="w-full rounded border-slate-300 text-sm shadow-none focus:border-sky-300 focus:ring focus:ring-sky-100"
                                                    placeholder="Asking price"
                                                >
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                <button @click="saveSelling()" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        :class="fieldCardClasses('tech_stacks', true)"
                                        @click="editingField !== 'tech_stacks' && startEdit('tech_stacks', techStacks)"
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Tech stacks</p>

                                        <div x-show="editingField !== 'tech_stacks'" class="mt-3 flex flex-wrap gap-2">
                                            <template x-if="techStacks.length === 0">
                                                <span class="text-sm text-slate-400">None</span>
                                            </template>
                                            <template x-for="techId in techStacks" :key="techId">
                                                <span class="inline-flex h-6 w-fit items-center justify-center gap-1 rounded-2xl border border-slate-200 bg-slate-100 px-[11px] text-sm text-slate-700" x-text="getTechName(techId)"></span>
                                            </template>
                                        </div>

                                        <div x-show="editingField === 'tech_stacks'" class="mt-3" @click.stop>
                                            <div class="flex max-h-44 flex-wrap gap-2 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 p-3">
                                                @foreach ($allTechStacks as $tech)
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 transition hover:border-sky-200 hover:bg-sky-50">
                                                        <input type="checkbox" :value="{{ $tech->id }}" x-model="tempValue" class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                                        <span>{{ $tech->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="mt-3 flex justify-end gap-2">
                                                <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                <button @click="save('tech_stacks')" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="lg:col-span-2"
                                        :class="fieldCardClasses('categories', true)"
                                        @click="editingField !== 'categories' && startEdit('categories', categories)"
                                    >
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Categories</p>

                                        <div x-show="editingField !== 'categories'" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Category</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <template x-for="catId in getSoftwareCats()" :key="catId">
                                                        <span class="inline-flex h-6 w-fit items-center justify-center gap-1 rounded-2xl border border-slate-200 bg-slate-100 px-[11px] text-sm text-slate-700" x-text="getCategoryName(catId)"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div>
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Pricing</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <template x-for="catId in getPricingCats()" :key="catId">
                                                        <span class="inline-flex h-6 w-fit items-center justify-center gap-1 rounded-2xl border border-slate-200 bg-slate-100 px-[11px] text-sm text-slate-700" x-text="getCategoryName(catId)"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div>
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Best for</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <template x-for="catId in getBestForCats()" :key="catId">
                                                        <span class="inline-flex h-6 w-fit items-center justify-center gap-1 rounded-2xl border border-slate-200 bg-slate-100 px-[11px] text-sm text-slate-700" x-text="getCategoryName(catId)"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div>
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Platform</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <template x-for="catId in getPlatformCats()" :key="catId">
                                                        <span class="inline-flex h-6 w-fit items-center justify-center gap-1 rounded-2xl border border-slate-200 bg-slate-100 px-[11px] text-sm text-slate-700" x-text="getCategoryName(catId)"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <div x-show="editingField === 'categories'" class="mt-4 space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4" @click.stop>
                                            @foreach (['Software Categories' => 'Software', 'Pricing' => 'Pricing', 'Best for' => 'Best for', 'Platform' => 'Platform'] as $title => $typeName)
                                                <div>
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $title }}</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @foreach ($allCategories as $cat)
                                                            @if (($typeName === 'Software' && ($cat->types->contains('name', 'Software') || $cat->types->contains('name', 'Software Categories') || $cat->types->contains('name', 'Category') || $cat->types->isEmpty())) || ($cat->types->contains('name', $typeName)))
                                                                <label class="flex cursor-pointer items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 transition hover:border-sky-200 hover:bg-sky-50">
                                                                    <input type="checkbox" :value="{{ $cat->id }}" x-model="tempValue" class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                                                    <span>{{ $cat->name }}</span>
                                                                </label>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach

                                            <div class="flex justify-end gap-2 border-t border-slate-200 pt-3">
                                                <button @click="cancelEdit()" class="rounded bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Cancel</button>
                                                <button @click="save('categories')" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-medium text-white">Save</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lg:col-span-2 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Media gallery</p>
                                        <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                                            <template x-for="item in media" :key="item.id">
                                                <div class="group/media relative aspect-square overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                                    <template x-if="item.type === 'video'">
                                                        <div class="flex h-full w-full items-center justify-center bg-slate-900">
                                                            <svg class="h-8 w-8 text-white/50" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.333-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/>
                                                            </svg>
                                                        </div>
                                                    </template>
                                                    <template x-if="item.type !== 'video'">
                                                        <img :src="item.path.startsWith('http') ? item.path : '/storage/' + item.path" :alt="item.alt_text" class="h-full w-full object-cover">
                                                    </template>
                                                    <button @click="removeMedia(item.id)" class="absolute right-2 top-2 rounded bg-rose-600 p-1 text-white opacity-0 transition group-hover/media:opacity-100">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>

                                            <label class="group/add relative flex aspect-square cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 transition hover:border-sky-300 hover:bg-sky-50">
                                                <svg class="h-6 w-6 text-slate-300 transition group-hover/add:text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                <span class="mt-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 transition group-hover/add:text-sky-600">Add</span>
                                                <input type="file" class="hidden" @change="addMedia">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function productEditor(config) {
            return {
                product: config.product,
                categories: config.categories,
                techStacks: config.tech_stacks,
                media: config.media || [],
                expanded: false,
                editingField: null,
                tempValue: null,
                loading: false,

                allCategories: @js($allCategories->mapWithKeys(fn($c) => [$c->id => ['name' => $c->name, 'types' => $c->types->pluck('name')]])),
                allTechStacks: @js($allTechStacks->pluck('name', 'id')),

                toggleExpanded() {
                    this.expanded = !this.expanded;
                },

                displayLogo() {
                    return this.product.logo_url || this.product.fallback_logo_url;
                },

                displayTagline() {
                    const tagline = `${this.product.product_page_tagline || this.product.tagline || ''}`.trim();
                    return tagline || 'Not set';
                },

                fieldCardClasses(field, editable = false) {
                    let classes = 'rounded-lg border p-4 shadow-sm transition ';
                    classes += this.editingField === field
                        ? 'border-sky-300 bg-sky-50/70 shadow-md '
                        : 'border-slate-200 bg-white ';

                    if (editable) {
                        classes += 'cursor-pointer hover:border-sky-200 hover:shadow-md ';
                    }

                    return classes;
                },

                startEdit(field, value) {
                    this.expanded = true;
                    this.editingField = field;
                    this.tempValue = Array.isArray(value) || (value && typeof value === 'object')
                        ? JSON.parse(JSON.stringify(value))
                        : value;
                },

                generateSlug(text) {
                    if (!text) return '';

                    if (text.includes('://')) {
                        try {
                            const url = new URL(text);
                            text = url.hostname.replace('www.', '').split('.')[0];
                        } catch (e) {
                        }
                    }

                    return text
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                },

                normalizeXHandle(value) {
                    if (!value) return '';
                    const input = String(value).trim();
                    const match = input.match(/(?:https?:\/\/)?(?:www\.)?(?:x\.com|twitter\.com)\/@?([A-Za-z0-9_]{1,15})/i);
                    if (match && match[1]) return match[1];
                    return input.replace(/^@+/, '');
                },

                buildXProfileUrl(value) {
                    const handle = this.normalizeXHandle(value);
                    return /^[A-Za-z0-9_]{1,15}$/.test(handle) ? `https://x.com/${handle}` : null;
                },

                formatPrice(value) {
                    const amount = parseFloat(value);
                    return Number.isFinite(amount) ? amount.toLocaleString() : value;
                },

                cancelEdit() {
                    this.editingField = null;
                    this.tempValue = null;
                },

                getCategoryName(id) {
                    return this.allCategories[id]?.name || 'Unknown';
                },

                getTechName(id) {
                    return this.allTechStacks[id] || 'Unknown';
                },

                getSoftwareCats() {
                    return this.categories.filter(id => {
                        const types = this.allCategories[id]?.types || [];
                        return types.includes('Software') || types.includes('Software Categories') || types.includes('Category') || types.length === 0;
                    });
                },

                getPricingCats() {
                    return this.categories.filter(id => (this.allCategories[id]?.types || []).includes('Pricing'));
                },

                getBestForCats() {
                    return this.categories.filter(id => (this.allCategories[id]?.types || []).includes('Best for'));
                },

                getPlatformCats() {
                    return this.categories.filter(id => (this.allCategories[id]?.types || []).includes('Platform'));
                },

                async save(field) {
                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/inline-update`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ field, value: this.tempValue })
                        });

                        const data = await response.json();
                        if (data.success) {
                            if (field === 'categories') {
                                this.categories = JSON.parse(JSON.stringify(this.tempValue));
                            } else if (field === 'tech_stacks') {
                                this.techStacks = JSON.parse(JSON.stringify(this.tempValue));
                            } else if (field === 'maker_links') {
                                this.product.maker_links = JSON.parse(JSON.stringify(this.tempValue));
                            } else if (field === 'x_account') {
                                let savedValue = this.tempValue;
                                if (data && data.product && typeof data.product.x_account !== 'undefined') {
                                    savedValue = data.product.x_account;
                                }
                                const normalizedHandle = this.normalizeXHandle(savedValue);
                                this.product.x_account = savedValue;
                                this.product.x_handle = normalizedHandle;
                                this.product.x_profile_url = this.buildXProfileUrl(normalizedHandle);
                            } else {
                                this.product[field] = this.tempValue;
                                if (field === 'name' || field === 'link') {
                                    this.product.slug = data.product.slug;
                                }
                            }

                            this.editingField = null;
                            if (data.message.includes('review')) {
                                alert(data.message);
                                location.reload();
                            }
                        } else {
                            alert(data.message || 'Update failed');
                        }
                    } catch (error) {
                        alert('Something went wrong. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },

                async saveSelling() {
                    this.loading = true;
                    try {
                        await fetch(`/products/${this.product.id}/inline-update`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                field: 'sell_product',
                                value: this.tempValue.sell_product
                            })
                        });

                        await fetch(`/products/${this.product.id}/inline-update`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                field: 'asking_price',
                                value: this.tempValue.asking_price
                            })
                        });

                        this.product.sell_product = this.tempValue.sell_product;
                        this.product.asking_price = this.tempValue.asking_price;
                        this.editingField = null;
                    } catch (error) {
                        alert('Update failed');
                    } finally {
                        this.loading = false;
                    }
                },

                async uploadLogo(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('logo', file);

                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/inline-update-logo`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.product.logo_url = data.logo_url;
                            if (data.message.includes('review')) alert(data.message);
                        } else {
                            alert(data.message || 'Upload failed');
                        }
                    } catch (error) {
                        alert('Error uploading logo');
                    } finally {
                        this.loading = false;
                    }
                },

                async addMedia(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('media', file);

                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/add-media`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.media.push(data.media);
                        } else {
                            alert(data.message || 'Media upload failed');
                        }
                    } catch (error) {
                        alert('Error uploading media');
                    } finally {
                        this.loading = false;
                    }
                },

                async removeMedia(mediaId) {
                    if (!confirm('Are you sure you want to remove this media?')) return;

                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/remove-media/${mediaId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.media = this.media.filter(m => m.id !== mediaId);
                        } else {
                            alert(data.message || 'Removal failed');
                        }
                    } catch (error) {
                        alert('Error removing media');
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
@push('styles')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-toolbar.ql-snow {
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .ql-container.ql-snow {
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
            border-color: #cbd5e1;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
@endpush
@endsection

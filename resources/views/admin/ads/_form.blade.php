@php
    $isEdit = isset($ad) && $ad;
    $selectedTemplate = old('template', $selectedTemplate ?? 'custom');
    $presetTypeByTemplate = [
        'custom' => old('type', $ad->type ?? ''),
        'sponsor' => old('type', $ad->type ?? 'image_banner'),
        'sidebar_banner' => old('type', $ad->type ?? 'image_banner'),
        'inline_listing' => old('type', $ad->type ?? 'image_banner'),
        'product_listing_card' => old('type', $ad->type ?? 'product_listing_card'),
    ];
    $templateZoneSlugs = [
        'custom' => [],
        'sponsor' => ['sponsors'],
        'sidebar_banner' => ['sidebar-top'],
        'inline_listing' => ['below-product-listing'],
        'product_listing_card' => ['sidebar-top'],
    ];
    $selectedZoneIds = old('ad_zones', $ad?->adZones->pluck('id')->all() ?? []);

    if (! $isEdit && $selectedZoneIds === [] && isset($templateZoneSlugs[$selectedTemplate])) {
        $selectedZoneIds = $adZones
            ->whereIn('slug', $templateZoneSlugs[$selectedTemplate])
            ->pluck('id')
            ->all();
    }

    $selectedCountries = old('target_countries', $ad->target_countries ?? []);
    $selectedCategoryIds = old('target_category_ids', $ad->target_category_ids ?? []);
    $selectedDevices = old('device_types', $ad->device_types ?? []);
    $targetRoutes = old('target_routes', isset($ad) && is_array($ad->target_routes) ? implode(', ', $ad->target_routes) : '');
@endphp

@push('styles')
<style>
    .ad-editor-shell { border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; box-shadow: 0 1px 3px rgb(15 23 42 / .08); }
    .ad-editor-section { border: 1px solid #e2e8f0; border-radius: .875rem; padding: 1.25rem; background: #fff; }
    .ad-editor-section-title { color: #0f172a; font-size: 1rem; font-weight: 700; }
    .ad-editor-section-help { margin-top: .25rem; color: #64748b; font-size: .8125rem; }
    .ad-choice { display: block; cursor: pointer; border: 1px solid #cbd5e1; border-radius: .75rem; padding: .875rem; transition: .15s ease; }
    .ad-choice:hover { border-color: #60a5fa; background: #eff6ff; }
    .ad-choice.is-selected { border-color: var(--color-primary-500, #2563eb); background: #eff6ff; box-shadow: 0 0 0 2px rgb(37 99 235 / .12); }
    .ad-choice.is-disabled { cursor: not-allowed; opacity: .48; background: #f8fafc; }
    .ad-preview-stage { min-height: 22rem; border-radius: .875rem; background: #e2e8f0; padding: 1rem; overflow: auto; }
    .ad-preview-device { margin-inline: auto; min-height: 19rem; border: 1px solid #cbd5e1; border-radius: .75rem; background: #f8fafc; padding: .875rem; transition: max-width .2s ease; }
    .ad-preview-slot { overflow: hidden; border: 1px dashed #94a3b8; border-radius: .625rem; background: #fff; padding: .75rem; }
    .ad-editor-actions { position: sticky; bottom: 0; z-index: 20; margin: 2rem -2rem -2rem; border-top: 1px solid #e2e8f0; border-radius: 0 0 1rem 1rem; background: rgb(255 255 255 / .96); padding: 1rem 2rem; backdrop-filter: blur(10px); }
    @media (max-width: 640px) { .ad-editor-actions { margin-inline: -1rem; padding-inline: 1rem; } }
</style>
@endpush

@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">Please fix the highlighted fields:</strong>
        <ul class="mt-2 list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    action="{{ $isEdit ? route('admin.ads.update', $ad) : route('admin.ads.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="ad-editor-shell px-4 pt-5 pb-8 mb-4 sm:px-8 sm:pt-6"
    x-data="adAdminForm({
        initialType: @js($presetTypeByTemplate[$selectedTemplate] ?? ''),
        initialImage: @js($ad->image_url ?? null),
        currentContent: @js($ad?->content),
        templateDefaults: @js([
            'custom' => ['type' => '', 'zones' => []],
            'sponsor' => ['type' => 'image_banner', 'zones' => $adZones->where('slug', 'sponsors')->pluck('id')->values()->all()],
            'sidebar_banner' => ['type' => 'image_banner', 'zones' => $adZones->where('slug', 'sidebar-top')->pluck('id')->values()->all()],
            'inline_listing' => ['type' => 'image_banner', 'zones' => $adZones->where('slug', 'below-product-listing')->pluck('id')->values()->all()],
            'product_listing_card' => ['type' => 'product_listing_card', 'zones' => $adZones->where('slug', 'sidebar-top')->pluck('id')->values()->all()],
        ]),
        isEdit: @js($isEdit),
        initialName: @js(old('internal_name', $ad->internal_name ?? '')),
        initialTagline: @js(old('tagline', $ad->tagline ?? '')),
        initialText: @js(old('content_text', isset($ad) && $ad->type === 'text_link' ? $ad->content : '')),
        initialHtml: @js(old('content_html', isset($ad) && $ad->type === 'html_snippet' ? $ad->content : '')),
        initialZones: @js(array_map('strval', $selectedZoneIds)),
        zones: @js($adZones->map(fn ($zone) => [
            'id' => (string) $zone->id,
            'name' => $zone->name,
            'location' => $zone->render_location ?: $zone->description,
            'placement' => $zone->placement_type,
        ])->values()),
        products: @js($products->map(fn ($product) => [
            'id' => (string) $product->id,
            'name' => $product->name,
            'tagline' => $product->tagline,
            'targetUrl' => $product->link,
            'logo' => $product->logo_url,
        ])->values()),
        initialProductId: @js((string) old('product_id', '')),
    })"
    x-init="init()"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-5">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $isEdit ? 'Edit Ad' : 'Create Ad' }}</h2>
                <p class="text-sm text-gray-500 mt-1">Choose a format, add its content, select placements, then confirm the preview.</p>
            </div>

            <section class="ad-editor-section">
                <h3 class="ad-editor-section-title">1. Starting point</h3>
                <p class="ad-editor-section-help">Use a preset or import a product to fill common fields.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="template">Template</label>
                    <select
                        id="template"
                        name="template"
                        class="shadow border rounded w-full py-2 px-3 text-gray-700"
                        x-model="template"
                        @change="applyTemplate($event.target.value)"
                    >
                        <option value="custom">Custom Ad</option>
                        <option value="sponsor">Sponsor</option>
                        <option value="sidebar_banner">Sidebar Banner</option>
                        <option value="inline_listing">Inline Listing Ad</option>
                        <option value="product_listing_card">Product Listing Card</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Templates prefill the most common zone and ad-type choices.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="product_id">Import Existing Product</label>
                    <div class="relative" @click.outside="productSelectOpen = false">
                        <input type="hidden" id="product_id" name="product_id" :value="productId">
                        <input
                            type="search"
                            x-model="productSearch"
                            @focus="productSelectOpen = true"
                            @input="productSelectOpen = true; productId = ''"
                            @keydown.escape="productSelectOpen = false"
                            @keydown.enter.prevent="selectFirstFilteredProduct()"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700"
                            placeholder="Search products..."
                            autocomplete="off"
                            role="combobox"
                            aria-controls="product-search-options"
                            :aria-expanded="productSelectOpen"
                        >
                        <div
                            x-show="productSelectOpen"
                            x-cloak
                            id="product-search-options"
                            class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded border border-gray-200 bg-white shadow-lg"
                            role="listbox"
                        >
                            <button type="button" class="block w-full px-3 py-2 text-left text-sm text-gray-600 hover:bg-blue-50" @click="clearProduct()">Manual ad</button>
                            <template x-for="product in filteredProducts()" :key="product.id">
                                <button type="button" class="block w-full px-3 py-2 text-left text-sm text-gray-800 hover:bg-blue-50" @click="selectProduct(product)" role="option" :aria-selected="productId === product.id" x-text="product.name"></button>
                            </template>
                            <p x-show="filteredProducts().length === 0" class="px-3 py-2 text-sm text-gray-500">No products found.</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Useful for sponsor ads copied from an approved product.</p>
                </div>
            </div>
            </section>

            <section class="ad-editor-section">
                <h3 class="ad-editor-section-title">2. Identity and format</h3>
                <p class="ad-editor-section-help">The internal name is for admins. Visitors only see content you add below.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="internal_name">Internal Name</label>
                    <input id="internal_name" name="internal_name" type="text" x-model="internalName" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('internal_name') border-red-500 @enderror" placeholder="Homepage sponsor, launch week banner, etc.">
                    @error('internal_name') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="tagline">Tagline</label>
                    <input id="tagline" name="tagline" type="text" x-model="tagline" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('tagline') border-red-500 @enderror" placeholder="Optional short description">
                    @error('tagline') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
            </div>

                <div class="mt-5">
                    <span class="block text-sm font-bold text-gray-700 mb-2">Ad format</span>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach([
                            'image_banner' => ['Image banner', 'A responsive uploaded graphic'],
                            'product_listing_card' => ['Product card', 'Logo, name, and tagline'],
                            'text_link' => ['Text link', 'A compact clickable link'],
                            'html_snippet' => ['HTML / JavaScript', 'Custom markup, embed, or script'],
                        ] as $typeValue => [$typeLabel, $typeHelp])
                            <label class="ad-choice" :class="{ 'is-selected': adType === '{{ $typeValue }}' }">
                                <input class="sr-only" type="radio" name="type" value="{{ $typeValue }}" x-model="adType">
                                <span class="block text-sm font-semibold text-slate-900">{{ $typeLabel }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $typeHelp }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('type') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="ad-editor-section">
                <h3 class="ad-editor-section-title">3. Ad content</h3>
                <p class="ad-editor-section-help" x-text="contentHelp()"></p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div x-show="['image_banner', 'product_listing_card'].includes(adType)" x-cloak>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="content_image">Image</label>
                    <input id="content_image" name="content_image" type="file" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('content_image') border-red-500 @enderror" @change="updateImagePreview($event)">
                    <p class="text-xs text-gray-500 mt-1" x-text="adType === 'product_listing_card' ? 'Upload the product logo or import an existing product.' : 'Upload a new file or import a product logo.'"></p>
                    @error('content_image') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>

            <div x-show="adType === 'text_link'" x-cloak>
                <label class="block text-sm font-bold text-gray-700 mb-2" for="content_text">Link Text</label>
                <input id="content_text" name="content_text" type="text" x-model="contentText" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('content_text') border-red-500 @enderror">
                @error('content_text') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>

            <div x-show="adType === 'html_snippet'" x-cloak>
                <label class="block text-sm font-bold text-gray-700 mb-2" for="content_html">HTML Snippet</label>
                <textarea id="content_html" name="content_html" rows="10" x-model="contentHtml" class="shadow border rounded w-full py-2 px-3 font-mono text-sm text-gray-700 @error('content_html') border-red-500 @enderror" placeholder="<div>...</div>&#10;<script>...</script>"></textarea>
                <p class="mt-2 text-xs text-amber-700">Trusted code only. Scripts run on live pages. Preview scripts run inside an isolated frame.</p>
                @error('content_html') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>

            <div x-show="['image_banner', 'product_listing_card', 'text_link'].includes(adType)" x-cloak>
                <label class="block text-sm font-bold text-gray-700 mb-2" for="target_url">Target URL</label>
                <input id="target_url" name="target_url" type="url" value="{{ old('target_url', $ad->target_url ?? '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('target_url') border-red-500 @enderror" placeholder="https://example.com">
                @error('target_url') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                <label class="mt-3 flex items-center text-sm font-bold text-gray-700">
                    <input type="checkbox" name="open_in_new_tab" value="1" class="mr-2" @checked(old('open_in_new_tab', $ad->open_in_new_tab ?? true))>
                    Open in new tab
                </label>
            </div>
                </div>
            </section>

            <section class="ad-editor-section">
                <h3 class="ad-editor-section-title">4. Placements</h3>
                <p class="ad-editor-section-help">Select every location where this ad may appear. HTML / JavaScript works in all standard placements.</p>
                <div id="ad_zones" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($adZones as $zone)
                        @php($zoneTypes = $zone->supported_ad_types ?: \App\Models\AdZone::SUPPORTED_AD_TYPES)
                        <label
                            class="ad-choice"
                            :class="{ 'is-selected': selectedZones.includes('{{ $zone->id }}'), 'is-disabled': !zoneSupports(@js($zoneTypes)) }"
                        >
                            <span class="flex items-start gap-3">
                                <input type="checkbox" name="ad_zones[]" value="{{ $zone->id }}" x-model="selectedZones" :disabled="!zoneSupports(@js($zoneTypes))" class="mt-1">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ $zone->name }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $zone->render_location ?: $zone->description }}</span>
                                    <span class="mt-2 block text-[11px] font-medium text-slate-400">{{ ucfirst(str_replace('_', ' ', $zone->placement_type)) }} · {{ ucfirst($zone->device_scope) }} · Up to {{ $zone->max_ads }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('ad_zones') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </section>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="start_date">Start Date</label>
                    <input id="start_date" name="start_date" type="datetime-local" value="{{ old('start_date', $ad?->start_date?->format('Y-m-d\TH:i')) }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="end_date">End Date</label>
                    <input id="end_date" name="end_date" type="datetime-local" value="{{ old('end_date', $ad?->end_date?->format('Y-m-d\TH:i')) }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <label class="flex items-center text-sm font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" class="mr-2" @checked(old('is_active', $ad->is_active ?? true))>
                    Active
                </label>
                <label class="flex items-center text-sm font-bold text-gray-700">
                    <input type="checkbox" name="is_house_ad" value="1" class="mr-2" @checked(old('is_house_ad', $ad->is_house_ad ?? false))>
                    House Ad
                </label>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="priority">Priority</label>
                    <input id="priority" name="priority" type="number" min="0" max="1000" value="{{ old('priority', $ad->priority ?? 0) }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="weight">Weight</label>
                    <input id="weight" name="weight" type="number" min="1" max="1000" value="{{ old('weight', $ad->weight ?? 1) }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800">Targeting</h3>
                <p class="text-sm text-gray-500 mt-1">Leave fields empty to let an ad run everywhere its assigned zone allows.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="target_countries">Countries</label>
                    <select id="target_countries" name="target_countries[]" multiple size="6" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" @selected(in_array($code, $selectedCountries))>{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="target_category_ids">Categories / Topics</label>
                    <select id="target_category_ids" name="target_category_ids[]" multiple size="6" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategoryIds))>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="target_routes">Route / Page Type</label>
                    <input id="target_routes" name="target_routes" type="text" value="{{ $targetRoutes }}" class="shadow border rounded w-full py-2 px-3 text-gray-700" placeholder="home, categories.show, topics.category">
                    <p class="text-xs text-gray-500 mt-1">Comma-separated route names or page-type identifiers.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="audience_scope">Audience</label>
                    <select id="audience_scope" name="audience_scope" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <option value="all" @selected(old('audience_scope', $ad->audience_scope ?? 'all') === 'all')>Everyone</option>
                        <option value="guest" @selected(old('audience_scope', $ad->audience_scope ?? 'all') === 'guest')>Guests Only</option>
                        <option value="authenticated" @selected(old('audience_scope', $ad->audience_scope ?? 'all') === 'authenticated')>Authenticated Users Only</option>
                    </select>
                </div>
            </div>

            <div>
                <span class="block text-sm font-bold text-gray-700 mb-2">Device Types</span>
                <div class="flex flex-wrap gap-4">
                    @foreach(['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'] as $deviceValue => $deviceLabel)
                        <label class="flex items-center text-sm text-gray-700">
                            <input type="checkbox" name="device_types[]" value="{{ $deviceValue }}" class="mr-2" @checked(in_array($deviceValue, $selectedDevices))>
                            {{ $deviceLabel }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Placement preview</h3>
                        <p class="mt-1 text-xs text-gray-500">Approximate responsive size and layout.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500" x-text="adTypeLabel()"></span>
                </div>

                <label class="mt-4 block text-xs font-semibold text-slate-600" for="preview_zone">Placement</label>
                <select id="preview_zone" x-model="previewZone" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">
                    <template x-for="zone in previewableZones()" :key="zone.id">
                        <option :value="zone.id" x-text="zone.name"></option>
                    </template>
                </select>

                <div class="mt-3 grid grid-cols-3 gap-2" role="group" aria-label="Preview device">
                    <template x-for="device in ['desktop', 'tablet', 'mobile']" :key="device">
                        <button type="button" class="rounded-lg border px-2 py-2 text-xs font-semibold capitalize" :class="previewDevice === device ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-500'" @click="previewDevice = device" x-text="device"></button>
                    </template>
                </div>

                <div class="ad-preview-stage mt-4">
                    <div class="ad-preview-device" :style="`max-width: ${deviceWidth()}`">
                        <div class="mb-3 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-red-300"></span><span class="h-2 w-2 rounded-full bg-amber-300"></span><span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                            <span class="ml-2 truncate text-[10px] text-slate-400" x-text="previewZoneDetails()"></span>
                        </div>
                        <div class="mb-3 h-8 rounded bg-slate-200"></div>
                        <div class="ad-preview-slot" :style="previewSlotStyle()">
                    <template x-if="adType === 'image_banner'">
                        <div class="space-y-3">
                            <template x-if="previewImage">
                                <img :src="previewImage" alt="" class="max-h-40 w-full object-contain rounded">
                            </template>
                            <p x-show="!previewImage" class="text-sm text-gray-400">Upload an image or import a product logo to preview it here.</p>
                            <div>
                                <p class="text-sm font-semibold text-gray-800" x-text="internalName || 'Ad title'"></p>
                                <p class="text-xs text-gray-500" x-text="tagline || 'Optional tagline'"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="adType === 'product_listing_card'">
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center gap-3">
                                <template x-if="previewImage">
                                    <img :src="previewImage" alt="" class="w-12 h-12 rounded-xl object-cover flex-shrink-0">
                                </template>
                                <template x-if="!previewImage">
                                    <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center text-xs text-gray-400 flex-shrink-0">Logo</div>
                                </template>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate" x-text="internalName || 'Product name'"></p>
                                    <p class="text-sm text-gray-600 line-clamp-2" x-text="tagline || 'Short tagline'"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="adType === 'text_link'">
                        <a href="#" class="text-blue-600 hover:underline text-sm" x-text="contentText || 'Preview link text'"></a>
                    </template>
                    <template x-if="adType === 'html_snippet'">
                        <iframe
                            title="Isolated HTML ad preview"
                            sandbox="allow-scripts"
                            :srcdoc="htmlPreviewDocument()"
                            class="block min-h-40 w-full border-0 bg-white"
                        ></iframe>
                    </template>
                    <p x-show="!adType" class="py-12 text-center text-sm text-slate-400">Choose an ad format to begin.</p>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2"><div class="h-16 rounded bg-slate-200"></div><div class="h-16 rounded bg-slate-200"></div><div class="h-16 rounded bg-slate-200"></div></div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-800">Before publishing</h3>
                <ol class="mt-3 space-y-2 text-xs text-gray-600">
                    <li>1. Check desktop, tablet, and mobile previews.</li>
                    <li>2. Confirm at least one placement is selected.</li>
                    <li>3. Use targeting only when the ad should not run everywhere.</li>
                    <li x-show="adType === 'html_snippet'">4. Test third-party scripts after saving; the preview is intentionally isolated.</li>
                </ol>
            </div>
        </aside>
    </div>

    <div class="ad-editor-actions flex items-center justify-between gap-4">
        <button
            class="inline-flex items-center justify-center rounded px-4 py-2 font-bold text-white transition hover:brightness-95"
            style="background-color: var(--color-primary-500, #2563eb); color: #fff;"
            type="submit"
        >
            {{ $isEdit ? 'Update Ad' : 'Create Ad' }}
        </button>
        <a href="{{ route('admin.advertising.index', ['tab' => 'ads']) }}" class="font-bold text-sm text-blue-500 hover:text-blue-800">
            Cancel
        </a>
    </div>

</form>

<script>
    function adAdminForm(config) {
        return {
            template: @js($selectedTemplate),
            adType: config.initialType,
            previewImage: config.initialImage,
            internalName: config.initialName,
            tagline: config.initialTagline,
            contentText: config.initialText,
            contentHtml: config.initialHtml,
            selectedZones: config.initialZones,
            zones: config.zones,
            products: config.products,
            productId: config.initialProductId,
            productSearch: config.products.find((product) => product.id === config.initialProductId)?.name || '',
            productSelectOpen: false,
            previewZone: config.initialZones[0] || config.zones[0]?.id || '',
            previewDevice: 'desktop',
            init() {
                this.$watch('selectedZones', (zones) => {
                    if (zones.length && !zones.includes(this.previewZone)) {
                        this.previewZone = zones[0];
                    }
                });
            },
            applyTemplate(template) {
                this.template = template;
                if (!config.isEdit && config.templateDefaults[template]) {
                    this.adType = config.templateDefaults[template].type;
                    const zoneIds = config.templateDefaults[template].zones.map(String);
                    this.selectedZones = zoneIds;
                    this.previewZone = zoneIds[0] || this.previewZone;
                }
            },
            filteredProducts() {
                const query = this.productSearch.trim().toLocaleLowerCase();
                return query
                    ? this.products.filter((product) => product.name.toLocaleLowerCase().includes(query))
                    : this.products;
            },
            selectFirstFilteredProduct() {
                const product = this.filteredProducts()[0];
                if (product) {
                    this.selectProduct(product);
                }
            },
            selectProduct(product) {
                this.productId = product.id;
                this.productSearch = product.name;
                this.productSelectOpen = false;
                this.internalName = product.name || '';
                this.tagline = product.tagline || '';
                document.getElementById('target_url').value = product.targetUrl || '';
                this.previewImage = product.logo || this.previewImage;
            },
            clearProduct() {
                this.productId = '';
                this.productSearch = '';
                this.productSelectOpen = false;
            },
            updateImagePreview(event) {
                const file = event.target.files?.[0];
                if (!file) {
                    return;
                }

                this.previewImage = URL.createObjectURL(file);
            },
            zoneSupports(types) {
                return this.adType === 'html_snippet' || !this.adType || types.includes(this.adType);
            },
            previewableZones() {
                const selected = this.zones.filter((zone) => this.selectedZones.includes(zone.id));
                return selected.length ? selected : this.zones;
            },
            previewZoneObject() {
                return this.zones.find((zone) => zone.id === this.previewZone) || this.previewableZones()[0] || null;
            },
            previewZoneDetails() {
                const zone = this.previewZoneObject();
                return zone ? `${zone.name} · ${zone.location}` : 'Select a placement';
            },
            deviceWidth() {
                return { desktop: '100%', tablet: '28rem', mobile: '18rem' }[this.previewDevice];
            },
            previewSlotStyle() {
                const placement = this.previewZoneObject()?.placement;
                if (placement === 'sidebar') {
                    return 'max-width: 18rem; margin-left: auto;';
                }
                if (placement === 'header' || placement === 'footer') {
                    return 'width: 100%; min-height: 5rem;';
                }
                return 'width: 100%; min-height: 7rem;';
            },
            contentHelp() {
                return {
                    image_banner: 'Upload a responsive banner and add its destination URL.',
                    product_listing_card: 'Upload a square logo and provide a short tagline.',
                    text_link: 'Enter the visible link text and its destination URL.',
                    html_snippet: 'Paste trusted HTML, CSS, and JavaScript. No destination URL is required.',
                }[this.adType] || 'Choose an ad format above.';
            },
            adTypeLabel() {
                return {
                    image_banner: 'Image banner',
                    product_listing_card: 'Product card',
                    text_link: 'Text link',
                    html_snippet: 'HTML / JavaScript',
                }[this.adType] || 'No format';
            },
            htmlPreviewDocument() {
                const content = this.contentHtml || '<div style="padding:24px;text-align:center;color:#94a3b8;font-family:sans-serif">HTML preview appears here</div>';
                return `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>html,body{margin:0;max-width:100%;overflow-x:hidden}*{box-sizing:border-box}img,iframe,video{max-width:100%}</style></head><body>${content}</body></html>`;
            },
        };
    }
</script>

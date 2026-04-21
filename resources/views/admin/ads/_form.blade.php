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
    class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4"
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
    })"
    x-init="init()"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $isEdit ? 'Edit Ad' : 'Create Ad' }}</h2>
                <p class="text-sm text-gray-500 mt-1">One form now handles sponsor ads, sidebar banners, and inline placements.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
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
                    <select
                        id="product_id"
                        name="product_id"
                        class="shadow border rounded w-full py-2 px-3 text-gray-700"
                        @change="applyProduct($event.target.options[$event.target.selectedIndex])"
                    >
                        <option value="">Manual ad</option>
                        @foreach($products as $product)
                            <option
                                value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-tagline="{{ $product->tagline }}"
                                data-target-url="{{ $product->link }}"
                                data-logo="{{ $product->logo_url }}"
                                @selected(old('product_id') == $product->id)
                            >
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Useful for sponsor ads copied from an approved product.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="internal_name">Internal Name</label>
                    <input id="internal_name" name="internal_name" type="text" value="{{ old('internal_name', $ad->internal_name ?? '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('internal_name') border-red-500 @enderror" placeholder="Homepage sponsor, launch week banner, etc.">
                    @error('internal_name') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="tagline">Tagline</label>
                    <input id="tagline" name="tagline" type="text" value="{{ old('tagline', $ad->tagline ?? '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('tagline') border-red-500 @enderror" placeholder="Optional short description">
                    @error('tagline') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="type">Ad Type</label>
                    <select id="type" name="type" x-model="adType" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('type') border-red-500 @enderror">
                        <option value="">Select ad type</option>
                        <option value="image_banner">Image Banner</option>
                        <option value="product_listing_card">Product Listing Card</option>
                        <option value="text_link">Text Link</option>
                        <option value="html_snippet">HTML Snippet</option>
                    </select>
                    @error('type') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
                <div x-show="['image_banner', 'product_listing_card'].includes(adType)" x-cloak>
                    <label class="block text-sm font-bold text-gray-700 mb-2" for="content_image">Image</label>
                    <input id="content_image" name="content_image" type="file" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('content_image') border-red-500 @enderror" @change="updateImagePreview($event)">
                    <p class="text-xs text-gray-500 mt-1" x-text="adType === 'product_listing_card' ? 'Upload the product logo or import an existing product.' : 'Upload a new file or import a product logo.'"></p>
                    @error('content_image') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                </div>
            </div>

            <div x-show="adType === 'text_link'" x-cloak>
                <label class="block text-sm font-bold text-gray-700 mb-2" for="content_text">Link Text</label>
                <input id="content_text" name="content_text" type="text" value="{{ old('content_text', isset($ad) && $ad->type === 'text_link' ? $ad->content : '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('content_text') border-red-500 @enderror">
                @error('content_text') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>

            <div x-show="adType === 'html_snippet'" x-cloak>
                <label class="block text-sm font-bold text-gray-700 mb-2" for="content_html">HTML Snippet</label>
                <textarea id="content_html" name="content_html" rows="6" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('content_html') border-red-500 @enderror">{{ old('content_html', isset($ad) && $ad->type === 'html_snippet' ? $ad->content : '') }}</textarea>
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

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2" for="ad_zones">Zone Assignments</label>
                <select id="ad_zones" name="ad_zones[]" multiple size="6" class="shadow border rounded w-full py-2 px-3 text-gray-700 @error('ad_zones') border-red-500 @enderror">
                    @foreach($adZones as $zone)
                        <option value="{{ $zone->id }}" @selected(in_array($zone->id, $selectedZoneIds))>
                            {{ $zone->name }} · {{ $zone->slug }} · max {{ $zone->max_ads }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Each selected zone will use its own delivery rules, max-ads limit, and rotation mode.</p>
                @error('ad_zones') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>

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

        <aside class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <h3 class="text-sm font-semibold text-gray-800">Preview</h3>
                <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-4 min-h-[180px]">
                    <template x-if="adType === 'image_banner'">
                        <div class="space-y-3">
                            <template x-if="previewImage">
                                <img :src="previewImage" alt="" class="max-h-40 w-full object-contain rounded">
                            </template>
                            <p x-show="!previewImage" class="text-sm text-gray-400">Upload an image or import a product logo to preview it here.</p>
                            <div>
                                <p class="text-sm font-semibold text-gray-800" x-text="fieldValue('internal_name', 'Ad title')"></p>
                                <p class="text-xs text-gray-500" x-text="fieldValue('tagline', 'Optional tagline')"></p>
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
                                    <p class="text-sm font-semibold text-gray-800 truncate" x-text="fieldValue('internal_name', 'Product name')"></p>
                                    <p class="text-sm text-gray-600 line-clamp-2" x-text="fieldValue('tagline', 'Short tagline')"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="adType === 'text_link'">
                        <a href="#" class="text-blue-600 hover:underline text-sm" x-text="fieldValue('content_text', 'Preview link text')"></a>
                    </template>
                    <template x-if="adType === 'html_snippet'">
                        <div class="text-xs text-gray-600 whitespace-pre-wrap" x-text="fieldValue('content_html', 'HTML preview source will appear here.')"></div>
                    </template>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-800">Zone Notes</h3>
                <div class="mt-3 space-y-3 text-xs text-gray-600">
                    @foreach($adZones as $zone)
                        <div class="rounded-lg bg-gray-50 p-3">
                            <div class="font-semibold text-gray-800">{{ $zone->name }}</div>
                            <div>{{ $zone->render_location ?: $zone->description }}</div>
                            <div>Supports: {{ implode(', ', $zone->supported_ad_types ?: \App\Models\AdZone::SUPPORTED_AD_TYPES) }}</div>
                            <div>Rotation: {{ ucfirst($zone->rotation_mode) }} · Max ads: {{ $zone->max_ads }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    <div class="flex items-center justify-between mt-8">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">
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
            init() {
            },
            applyTemplate(template) {
                this.template = template;
                if (!config.isEdit && config.templateDefaults[template]) {
                    this.adType = config.templateDefaults[template].type;
                    const zoneIds = config.templateDefaults[template].zones.map(String);
                    Array.from(document.getElementById('ad_zones').options).forEach((option) => {
                        option.selected = zoneIds.includes(option.value);
                    });
                }
            },
            applyProduct(option) {
                if (!option || !option.value) {
                    return;
                }

                document.getElementById('internal_name').value = option.dataset.name || '';
                document.getElementById('tagline').value = option.dataset.tagline || '';
                document.getElementById('target_url').value = option.dataset.targetUrl || '';
                this.previewImage = option.dataset.logo || this.previewImage;
            },
            updateImagePreview(event) {
                const file = event.target.files?.[0];
                if (!file) {
                    return;
                }

                this.previewImage = URL.createObjectURL(file);
            },
            fieldValue(id, fallback) {
                const element = document.getElementById(id);
                return element && element.value ? element.value : fallback;
            },
        };
    }
</script>

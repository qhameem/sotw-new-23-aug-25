@php
    $isEdit = isset($adZone) && $adZone;
    $selectedSupportedTypes = old('supported_ad_types', $adZone->supported_ad_types ?? \App\Models\AdZone::SUPPORTED_AD_TYPES);
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

<form action="{{ $isEdit ? route('admin.ad-zones.update', $adZone) : route('admin.ad-zones.store') }}" method="POST" class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $adZone->name ?? '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="slug">Slug</label>
            <input id="slug" name="slug" type="text" value="{{ old('slug', $adZone->slug ?? '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 mt-4">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="render_location">Where It Renders</label>
            <input id="render_location" name="render_location" type="text" value="{{ old('render_location', $adZone->render_location ?? '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700" placeholder="Right sidebar, homepage header, product list, etc.">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="placement_type">Placement Type</label>
            <select id="placement_type" name="placement_type" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                @foreach($placementTypes as $placementType)
                    <option value="{{ $placementType }}" @selected(old('placement_type', $adZone->placement_type ?? 'other') === $placementType)>{{ ucfirst(str_replace('_', ' ', $placementType)) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-4">
        <label class="block text-sm font-bold text-gray-700 mb-2" for="description">Description</label>
        <textarea id="description" name="description" rows="3" class="shadow border rounded w-full py-2 px-3 text-gray-700">{{ old('description', $adZone->description ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 md:grid-cols-4 mt-4">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="max_ads">Max Ads</label>
            <input id="max_ads" name="max_ads" type="number" min="1" max="50" value="{{ old('max_ads', $adZone->max_ads ?? 1) }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="rotation_mode">Rotation</label>
            <select id="rotation_mode" name="rotation_mode" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                @foreach($rotationModes as $rotationMode)
                    <option value="{{ $rotationMode }}" @selected(old('rotation_mode', $adZone->rotation_mode ?? 'random') === $rotationMode)>{{ ucfirst($rotationMode) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="device_scope">Device Scope</label>
            <select id="device_scope" name="device_scope" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                @foreach($deviceScopes as $deviceScope)
                    <option value="{{ $deviceScope }}" @selected(old('device_scope', $adZone->device_scope ?? 'all') === $deviceScope)>{{ ucfirst($deviceScope) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2" for="fallback_mode">Fallback</label>
            <select id="fallback_mode" name="fallback_mode" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                @foreach($fallbackModes as $fallbackMode)
                    <option value="{{ $fallbackMode }}" @selected(old('fallback_mode', $adZone->fallback_mode ?? 'empty') === $fallbackMode)>{{ ucfirst(str_replace('_', ' ', $fallbackMode)) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-4">
        <span class="block text-sm font-bold text-gray-700 mb-2">Supported Ad Types</span>
        <div class="flex flex-wrap gap-4">
            @foreach($supportedAdTypes as $supportedAdType)
                <label class="flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="supported_ad_types[]" value="{{ $supportedAdType }}" class="mr-2" @checked(in_array($supportedAdType, $selectedSupportedTypes))>
                    {{ ucwords(str_replace('_', ' ', $supportedAdType)) }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-4">
        <label class="block text-sm font-bold text-gray-700 mb-2" for="display_after_nth_product">Inline Position</label>
        <input id="display_after_nth_product" name="display_after_nth_product" type="number" min="1" value="{{ old('display_after_nth_product', $adZone->display_after_nth_product ?? '') }}" class="shadow border rounded w-full py-2 px-3 text-gray-700">
        <p class="text-xs text-gray-500 mt-1">Only used for in-feed zones like `below-product-listing`.</p>
    </div>

    <div class="flex items-center justify-between mt-8">
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">
            {{ $isEdit ? 'Update Ad Zone' : 'Create Ad Zone' }}
        </button>
        <a href="{{ route('admin.advertising.index', ['tab' => 'ad_zones']) }}" class="font-bold text-sm text-blue-500 hover:text-blue-800">
            Cancel
        </a>
    </div>
</form>

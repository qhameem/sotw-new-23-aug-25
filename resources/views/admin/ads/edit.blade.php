@extends('layouts.app')

@section('title', 'Edit Ad')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adForm()" x-init="adType = '{{ old('type', $ad->type) }}'; initializeContent();">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Edit Ad: {{ $ad->internal_name }}</h1>

    <form action="{{ route('admin.ads.update', $ad) }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
        @csrf
        @method('PUT')
        <!-- Internal Name -->
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="internal_name">
                Internal Reference Name
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('internal_name') border-red-500 @enderror" id="internal_name" name="internal_name" type="text" placeholder="e.g., Homepage Header Banner Summer Sale" value="{{ old('internal_name', $ad->internal_name) }}" required>
            @error('internal_name')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Ad Type -->
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="type">
                Ad Type
            </label>
            <select x-model="adType" id="type" name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('type') border-red-500 @enderror" required>
                <option value="image_banner" @if(old('type', $ad->type) == 'image_banner') selected @endif>Image Banner</option>
                <option value="text_link" @if(old('type', $ad->type) == 'text_link') selected @endif>Text Link</option>
                <option value="html_snippet" @if(old('type', $ad->type) == 'html_snippet') selected @endif>HTML Snippet</option>
            </select>
            @error('type')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Ad Content - Dynamic based on type -->
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="content">
                Ad Content
            </label>
            <!-- Image Banner Content -->
            <div x-show="adType === 'image_banner'">
                @if($ad->type === 'image_banner' && $ad->content)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $ad->content) }}" alt="{{ $ad->internal_name }}" class="max-w-xs max-h-48 object-contain rounded">
                        <p class="text-xs text-gray-500 mt-1">Current image. Upload a new image to replace it.</p>
                    </div>
                @endif
                <input type="file" id="content_image" name="content_image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('content_image') border-red-500 @enderror">
                 @error('content_image') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>
            <!-- Text Link Content -->
            <div x-show="adType === 'text_link'">
                <input type="text" id="content_text" name="content_text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('content_text') border-red-500 @enderror" placeholder="Enter link text" x-ref="contentText">
                 @error('content_text') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>
            <!-- HTML Snippet Content -->
            <div x-show="adType === 'html_snippet'">
                <textarea id="content_html" name="content_html" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('content_html') border-red-500 @enderror" placeholder="Enter HTML snippet" x-ref="contentHtml"></textarea>
                 @error('content_html') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Target URL (for image/text links) -->
        <div class="mb-4" x-show="adType === 'image_banner' || adType === 'text_link'">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="target_url">
                Target URL
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('target_url') border-red-500 @enderror" id="target_url" name="target_url" type="url" placeholder="https://example.com" value="{{ old('target_url', $ad->target_url) }}">
            @error('target_url')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Open in New Tab -->
        <div class="mb-4" x-show="adType === 'image_banner' || adType === 'text_link'">
            <label class="flex items-center text-gray-700 dark:text-gray-300 text-sm font-bold">
                <input type="checkbox" name="open_in_new_tab" value="1" class="form-checkbox h-5 w-5 text-blue-600 dark:bg-gray-700 border-gray-300 rounded focus:ring-blue-500" {{ old('open_in_new_tab', $ad->open_in_new_tab) ? 'checked' : '' }}>
                <span class="ml-2">Open link in new tab</span>
            </label>
        </div>

        <!-- Ad Zones -->
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="ad_zones">
                Assign to Ad Zones
            </label>
            <select id="ad_zones" name="ad_zones[]" multiple class="shadow border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('ad_zones') border-red-500 @enderror" size="5">
                @php $selectedZones = old('ad_zones', $ad->adZones->pluck('id')->toArray()); @endphp
                @foreach($adZones as $zone)
                    <option value="{{ $zone->id }}" @if(in_array($zone->id, $selectedZones)) selected @endif>{{ $zone->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (or Cmd on Mac) to select multiple zones.</p>
            @error('ad_zones')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Active Status -->
        <div class="mb-4">
            <label class="flex items-center text-gray-700 dark:text-gray-300 text-sm font-bold">
                <input type="checkbox" name="is_active" value="1" class="form-checkbox h-5 w-5 text-blue-600 dark:bg-gray-700 border-gray-300 rounded focus:ring-blue-500" {{ old('is_active', $ad->is_active) ? 'checked' : '' }}>
                <span class="ml-2">Active</span>
            </label>
        </div>

        <!-- Start Date -->
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="start_date">
                Start Date (Optional)
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('start_date') border-red-500 @enderror" id="start_date" name="start_date" type="datetime-local" value="{{ old('start_date', $ad->start_date ? $ad->start_date->format('Y-m-d\TH:i') : '') }}">
            @error('start_date')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- End Date -->
        <div class="mb-6">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="end_date">
                End Date (Optional)
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('end_date') border-red-500 @enderror" id="end_date" name="end_date" type="datetime-local" value="{{ old('end_date', $ad->end_date ? $ad->end_date->format('Y-m-d\TH:i') : '') }}">
            @error('end_date')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Update Ad
            </button>
            <a href="{{ route('admin.ads.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-600">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function adForm() {
    return {
        adType: '', // Initialized by x-init
        // adContent: @json(old('content', $ad->content)), // Be careful with JSON encoding complex HTML
        initializeContent() {
            // Pre-fill content fields based on current ad type and content
            // This is needed because file inputs cannot be pre-filled for security reasons
            // and text/textarea need to be explicitly set if their x-show was initially false.
            this.$nextTick(() => {
                if (this.adType === 'text_link') {
                    this.$refs.contentText.value = @json(old('content_text', $ad->type === 'text_link' ? $ad->content : ''));
                } else if (this.adType === 'html_snippet') {
                    this.$refs.contentHtml.value = @json(old('content_html', $ad->type === 'html_snippet' ? $ad->content : ''));
                }
            });
        }
    }
}
</script>
@endsection
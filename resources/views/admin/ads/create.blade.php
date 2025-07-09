@extends('layouts.app')

@section('title', 'Create New Ad')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adForm()" x-init="adType = '{{ old('type', '') }}'">
    <h1 class="text-2xl font-semibold text-gray-800  mb-6">Create New Ad</h1>

<!-- General Errors Display -->
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Oops! Something went wrong. Please check the errors below:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    <form action="{{ route('admin.ads.store') }}" method="POST" enctype="multipart/form-data" class="bg-white  shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
        @csrf
        <!-- Internal Name -->
        <div class="mb-4">
            <label class="block text-gray-700  text-sm font-bold mb-2" for="internal_name">
                Internal Reference Name
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('internal_name') border-red-500 @enderror" id="internal_name" name="internal_name" type="text" placeholder="e.g., Homepage Header Banner Summer Sale" value="{{ old('internal_name') }}" required>
            @error('internal_name')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Ad Type -->
        <div class="mb-4">
            <label class="block text-gray-700  text-sm font-bold mb-2" for="type">
                Ad Type
            </label>
            <select x-model="adType" id="type" name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('type') border-red-500 @enderror" required>
                <option value="">Select Ad Type</option>
                <option value="image_banner" @if(old('type') == 'image_banner') selected @endif>Image Banner</option>
                <option value="text_link" @if(old('type') == 'text_link') selected @endif>Text Link</option>
                <option value="html_snippet" @if(old('type') == 'html_snippet') selected @endif>HTML Snippet</option>
            </select>
            @error('type')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Ad Content - Dynamic based on type -->
        <div class="mb-4">
            <label class="block text-gray-700  text-sm font-bold mb-2" for="content">
                Ad Content
            </label>
            <!-- Image Banner Content -->
            <div x-show="adType === 'image_banner'">
                <input type="file" id="content_image" name="content_image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('content_image') border-red-500 @enderror">
                <p class="text-xs text-gray-500 mt-1">Upload an image for the banner.</p>
                 @error('content_image') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>
            <!-- Text Link Content -->
            <div x-show="adType === 'text_link'">
                <input type="text" id="content_text" name="content_text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('content_text') border-red-500 @enderror" placeholder="Enter link text" value="{{ old('content_text') }}">
                 @error('content_text') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>
            <!-- HTML Snippet Content -->
            <div x-show="adType === 'html_snippet'">
                <textarea id="content_html" name="content_html" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('content_html') border-red-500 @enderror" placeholder="Enter HTML snippet">{{ old('content_html') }}</textarea>
                 @error('content_html') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
            </div>
            <input type="hidden" name="content" :value="getCurrentContent()"> {{-- Fallback for non-JS or if needed --}}
        </div>

        <!-- Target URL (for image/text links) -->
        <div class="mb-4" x-show="adType === 'image_banner' || adType === 'text_link'">
            <label class="block text-gray-700  text-sm font-bold mb-2" for="target_url">
                Target URL
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('target_url') border-red-500 @enderror" id="target_url" name="target_url" type="url" placeholder="https://example.com" value="{{ old('target_url') }}">
            @error('target_url')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Open in New Tab -->
        <div class="mb-4" x-show="adType === 'image_banner' || adType === 'text_link'">
            <label class="flex items-center text-gray-700  text-sm font-bold">
                <input type="checkbox" name="open_in_new_tab" value="1" class="form-checkbox h-5 w-5 text-blue-600  border-gray-300 rounded focus:ring-blue-500" @if(is_null(old('open_in_new_tab')) && !$errors->any()) checked @elseif(old('open_in_new_tab') == '1') checked @endif>
                <span class="ml-2">Open link in new tab</span>
            </label>
        </div>

        <!-- Ad Zones -->
        <div class="mb-4">
            <label class="block text-gray-700  text-sm font-bold mb-2" for="ad_zones">
                Assign to Ad Zones
            </label>
            <select id="ad_zones" name="ad_zones[]" multiple class="shadow border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('ad_zones') border-red-500 @enderror" size="5">
                @foreach($adZones as $zone)
                    <option value="{{ $zone->id }}" @if(is_array(old('ad_zones')) && in_array($zone->id, old('ad_zones'))) selected @endif>{{ $zone->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Hold Ctrl (or Cmd on Mac) to select multiple zones.</p>
            @error('ad_zones')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- Active Status -->
        <div class="mb-4">
            <label class="flex items-center text-gray-700  text-sm font-bold">
                <input type="checkbox" name="is_active" value="1" class="form-checkbox h-5 w-5 text-blue-600  border-gray-300 rounded focus:ring-blue-500" @if(is_null(old('is_active')) && !$errors->any()) checked @elseif(old('is_active') == '1') checked @endif>
                <span class="ml-2">Active</span>
            </label>
        </div>

        <!-- Start Date -->
        <div class="mb-4">
            <label class="block text-gray-700  text-sm font-bold mb-2" for="start_date">
                Start Date (Optional)
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('start_date') border-red-500 @enderror" id="start_date" name="start_date" type="datetime-local" value="{{ old('start_date') }}">
            @error('start_date')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <!-- End Date -->
        <div class="mb-6">
            <label class="block text-gray-700  text-sm font-bold mb-2" for="end_date">
                End Date (Optional)
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700   leading-tight focus:outline-none focus:shadow-outline @error('end_date') border-red-500 @enderror" id="end_date" name="end_date" type="datetime-local" value="{{ old('end_date') }}">
            @error('end_date')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Create Ad
            </button>
            <a href="{{ route('admin.ads.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800  ">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function adForm() {
    return {
        adType: '', // Initialized by x-init
        getCurrentContent() {
            // This is a simplistic way to ensure 'content' is populated for backend if JS is involved
            // The actual content saving logic will be more robust in the controller
            if (this.adType === 'image_banner') return document.getElementById('content_image').value; // Or path after upload
            if (this.adType === 'text_link') return document.getElementById('content_text').value;
            if (this.adType === 'html_snippet') return document.getElementById('content_html').value;
            return '';
        }
    }
}
</script>
@endsection
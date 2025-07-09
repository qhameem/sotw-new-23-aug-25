@if(isset($ad) && $ad)
    <div class="my-4 p-4 bg-gray-50  rounded-lg ad-listing-item border border-gray-200 "> {{-- Removed shadow-md --}}
        {{-- You can add a small "Advertisement" label if desired --}}
        {{-- <span class="text-xs text-gray-400  block mb-2 text-right">Advertisement</span> --}}
        @if($ad->type === 'image_banner')
            <a href="{{ $ad->target_url }}" @if($ad->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                <img src="{{ asset('storage/' . $ad->content) }}" alt="{{ $ad->internal_name }}" class="w-full h-auto object-contain rounded">
            </a>
        @elseif($ad->type === 'text_link')
            <a href="{{ $ad->target_url }}" @if($ad->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="text-blue-600 hover:underline ">
                {{ $ad->content }}
            </a>
        @elseif($ad->type === 'html_snippet')
            {!! $ad->content !!}
        @endif
    </div>
@endif
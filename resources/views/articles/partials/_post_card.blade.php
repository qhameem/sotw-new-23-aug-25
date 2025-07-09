<div class="py-8 px-4">
    <div class="flex">
        <div class="flex-shrink-0 mr-2">
            @if($post->author->hasRole('admin'))
                <img class="h-4 w-4 rounded-full object-cover" src="{{ config('theme.favicon_url') ? Storage::url(config('theme.favicon_url')) : asset('favicon/favicon.ico') }}" alt="Software on the web">
            @else
                <img class="h-4 w-4 rounded-full object-cover" src="{{ $post->author->google_avatar ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($post->author->email))) }}" alt="{{ $post->author->name }}">
            @endif
        </div>
        <div class="flex-1">
            <div class="flex items-center text-xs">
                @if($post->author->hasRole('admin'))
                    <p class="font-semibold">Software on the web</p>
                @else
                    <p class="font-semibold">{{ $post->author->name }}</p>
                @endif
                <p class="text-gray-500 ml-1">on {{ $post->published_at->format('M d, Y') }}</p>
            </div>
            <div class="mt-2 flex justify-between items-start">
                <div>
                    <h3 class="text-base font-semibold">
                        <a href="{{ route('articles.show', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                    </h3>
                    <p class="mt-1 text-sm text-gray-700">
                        {{ Str::limit(strip_tags($post->content), 80) }}
                    </p>
                    <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
                        <div class="flex items-center space-x-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            <span>0</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                            <span>3</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                            <span>0</span>
                        </div>
                        @if($post->tags->isNotEmpty())
                            <div class="flex items-center space-x-2">
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a2 2 0 012 2v5a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm0 0l6.343 6.343a2 2 0 010 2.828L7 18.586M17 3h5a2 2 0 012 2v5a2 2 0 01-2 2h-5a2 2 0 01-2-2V5a2 2 0 012-2zm0 0l6.343 6.343a2 2 0 010 2.828L17 18.586" /></svg> -->
                                @foreach($post->tags as $tag)
                                    <a href="{{ route('articles.tag', $tag->slug) }}" class="text-gray-500 hover:text-gray-900">{{ $tag->name }}</a>@if(!$loop->last)<span>&middot;</span>@endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @if($post->featured_image_path)
                    <div class="ml-4 flex-shrink-0">
                        <a href="{{ route('articles.show', $post->slug) }}">
                            <img class="h-28 w-48 object-cover rounded-lg" src="{{ Str::startsWith($post->featured_image_path, ['http://', 'https://']) ? $post->featured_image_path : asset('storage/' . $post->featured_image_path) }}" alt="{{ $post->title }}">
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="py-8 px-4">
    <div class="flex justify-between items-start">
        <div class="flex-1">
            <h2 class="text-3xl font-bold text-gray-800">
                <a href="{{ route('articles.show', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
            </h2>
            <p class="mt-2 text-lg text-gray-600">
                {{ $post->excerpt }}
            </p>
        </div>
        @if($post->featured_image_path)
            <div class="ml-8 flex-shrink-0">
                <a href="{{ route('articles.show', $post->slug) }}">
                    <img class="h-32 w-56 object-cover rounded-xl"
                        src="{{ (Str::startsWith($post->featured_image_path, ['http://', 'https://']) || Str::startsWith($post->featured_image_path, '/storage')) ? $post->featured_image_path : asset('storage/' . $post->featured_image_path) }}"
                        alt="{{ $post->title }}">
                </a>
            </div>
        @endif
    </div>
</div>
@php
    $primaryCategory = $post->categories->first();
    $imageUrl = $post->display_image_url;

    $readingTime = max(1, (int) ceil(str_word_count(strip_tags($post->content ?? '')) / 220));
@endphp

<article class="group min-w-0">
    <a href="{{ route('articles.show', $post->slug) }}" class="block" aria-label="Read {{ $post->title }}">
        <div class="aspect-[16/9] overflow-hidden rounded-xl bg-stone-100">
            @if($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt=""
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.025]"
                    loading="lazy"
                >
            @else
                <div class="flex h-full items-end bg-stone-100 p-6">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">
                        {{ $primaryCategory?->name ?? 'Software on the Web' }}
                    </span>
                </div>
            @endif
        </div>

        <div class="pt-5">
            @if($primaryCategory)
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600">{{ $primaryCategory->name }}</p>
            @endif

            <h2 class="mt-2 text-xl font-semibold leading-snug tracking-tight text-stone-950 transition group-hover:text-primary-700 sm:text-2xl">
                {{ $post->title }}
            </h2>

            <p class="mt-3 line-clamp-2 text-[0.95rem] leading-6 text-stone-600">{{ $post->excerpt }}</p>

            <div class="mt-4 flex items-center gap-2 text-sm text-stone-500">
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('M j, Y') }}</time>
                <span aria-hidden="true">·</span>
                <span>{{ $readingTime }} min read</span>
            </div>
        </div>
    </a>
</article>

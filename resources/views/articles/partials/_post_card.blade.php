@php
    $author = $post->author;
    $authorName = $author?->name ?? 'Unknown author';
    $primaryCategory = $post->categories->first();
    $isAdminAuthor = $author?->hasRole('admin');
    $faviconPath = config('theme.favicon_url');
    $avatarUrl = $isAdminAuthor
        ? ($faviconPath ? \Illuminate\Support\Facades\Storage::url($faviconPath) : asset('favicon/favicon.ico'))
        : ($author?->google_avatar ?? ('https://www.gravatar.com/avatar/' . md5(strtolower(trim($author?->email ?? ''))) . '?d=mp'));
    $imageUrl = null;

    if ($post->featured_image_path) {
        $imageUrl = (\Illuminate\Support\Str::startsWith($post->featured_image_path, ['http://', 'https://']) || \Illuminate\Support\Str::startsWith($post->featured_image_path, '/storage'))
            ? $post->featured_image_path
            : asset('storage/' . $post->featured_image_path);
    }

    $readingTime = max(1, (int) ceil(str_word_count(strip_tags($post->content ?? '')) / 220));
@endphp

<article class="group border-b border-stone-200 py-8 first:pt-2 sm:py-10">
    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <img src="{{ $avatarUrl }}" alt="{{ $authorName }}" class="h-9 w-9 rounded-full object-cover">
                <div class="min-w-0">
                    <div class="font-medium text-stone-900">{{ $authorName }}</div>
                    <div class="text-stone-500">
                        @if($primaryCategory)
                            In {{ $primaryCategory->name }}
                        @else
                            Software on the Web
                        @endif
                    </div>
                </div>

                @if($post->staff_pick)
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">
                        Featured
                    </span>
                @endif
            </div>

            <h2 class="mt-5 text-xl font-bold leading-tight tracking-tight text-stone-950 sm:text-[1.75rem]">
                <a href="{{ route('articles.show', $post->slug) }}" class="transition hover:text-stone-600">
                    {{ $post->title }}
                </a>
            </h2>

            <p class="mt-3 max-w-3xl text-base leading-7 text-stone-500 sm:text-[1.05rem]">
                {{ $post->excerpt }}
            </p>

            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-stone-500">
                <span>{{ $post->published_at?->format('M j, Y') ?? 'Coming soon' }}</span>
                <span>{{ $readingTime }} min read</span>
                @if($primaryCategory)
                    <a href="{{ route('articles.category', $primaryCategory->slug) }}" class="rounded-full bg-stone-100 px-3 py-1 text-stone-600 transition hover:bg-stone-200 hover:text-stone-900">
                        {{ $primaryCategory->name }}
                    </a>
                @endif
            </div>
        </div>

        @if($imageUrl)
            <div class="w-full flex-shrink-0 sm:ml-8 sm:w-56 lg:w-64">
                <a href="{{ route('articles.show', $post->slug) }}" class="block overflow-hidden rounded-2xl bg-stone-100">
                    <img
                        class="h-52 w-full object-cover transition duration-300 group-hover:scale-[1.02] sm:h-40"
                        src="{{ $imageUrl }}"
                        alt="{{ $post->title }}"
                    >
                </a>
            </div>
        @endif
    </div>
</article>

@php
    $author = $post->author;
    $authorName = $author?->name ?? 'Unknown author';
    $categoryName = $post->categories->first()?->name;
    $isAdminAuthor = $author?->hasRole('admin');
    $faviconPath = config('theme.favicon_url');
    $avatarUrl = $isAdminAuthor
        ? ($faviconPath ? \Illuminate\Support\Facades\Storage::url($faviconPath) : asset('favicon/favicon.ico'))
        : ($author?->google_avatar ?? ('https://www.gravatar.com/avatar/' . md5(strtolower(trim($author?->email ?? ''))) . '?d=mp'));
@endphp

<article class="group">
    <a href="{{ route('articles.show', $post->slug) }}" class="block">
        <div class="flex items-start gap-3">
            @isset($rank)
                <span class="pt-0.5 font-serif text-2xl text-stone-300">{{ str_pad((string) $rank, 2, '0', STR_PAD_LEFT) }}</span>
            @endisset

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 text-xs text-stone-500">
                    <img src="{{ $avatarUrl }}" alt="{{ $authorName }}" class="h-6 w-6 rounded-full object-cover">
                    <span class="truncate font-medium text-stone-700">{{ $authorName }}</span>
                    @if($categoryName)
                        <span class="text-stone-300">in</span>
                        <span class="truncate">{{ $categoryName }}</span>
                    @endif
                </div>

                <h3 class="mt-3 text-base font-semibold leading-6 text-stone-900 transition group-hover:text-stone-600">
                    {{ $post->title }}
                </h3>

                <div class="mt-2 text-sm text-stone-500">
                    {{ $post->published_at?->format('M j, Y') ?? 'Coming soon' }}
                </div>
            </div>
        </div>
    </a>
</article>

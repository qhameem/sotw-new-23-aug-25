@extends('layouts.app', [
    'hideSidebar' => true,
    'mainContentMaxWidth' => 'max-w-none',
    'containerMaxWidth' => 'max-w-none',
    'mainPadding' => 'px-4 sm:px-6 lg:px-8',
    'headerPadding' => 'px-4 sm:px-6 lg:px-8',
])

@php
    use App\Models\Category;
    use Illuminate\Support\Str;

    $topCategories = cache()->remember('error_404_top_categories', config('performance.top_categories_cache_ttl', 3600), function () {
        return Category::whereDoesntHave('types', function ($query) {
            $query->where('types.id', 2);
        })
            ->withCount([
                'products' => function ($query) {
                    $query->where('approved', true)
                        ->where('is_published', true);
                },
            ])
            ->orderBy('products_count', 'desc')
            ->orderBy('name')
            ->take(8)
            ->get();
    });

    $requestedPath = request()->path();
    $requestedPath = $requestedPath === '/' ? '/' : '/' . ltrim($requestedPath, '/');
    $requestedPath = Str::limit($requestedPath, 90);
@endphp

@section('title', '404 | Page not found')
@section('meta_description', 'The page you requested could not be found. Browse categories, products, and articles on Software on the Web.')
@section('robots', 'noindex, follow')
@section('hide_desktop_page_header', '1')

@push('styles')
<style>
    .error-404-shell {
        background:
            radial-gradient(circle at top, rgba(59, 130, 246, 0.10), transparent 32%),
            linear-gradient(180deg, rgba(248, 250, 252, 0.92), rgba(255, 255, 255, 1));
    }

    .error-404-graphic-shadow {
        filter: drop-shadow(0 18px 32px rgba(59, 130, 246, 0.18));
    }
</style>
@endpush

@section('content')
    <section class="error-404-shell min-h-[calc(100vh-8.5rem)]">
        <div class="grid min-h-[calc(100vh-8.5rem)] lg:grid-cols-2">
            <div class="flex items-center justify-center px-6 py-12 sm:px-10 sm:py-16 lg:px-14 xl:px-20">
                <div class="w-full max-w-xl text-center lg:text-left">
                    <h1 class="text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                        Page Not Found
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-gray-600 sm:text-lg lg:max-w-lg">
                        We couldn&apos;t find the page you were looking for. Check the URL or jump back into the main parts of the site.
                    </p>
                    <p class="mt-3 text-sm text-gray-400">
                        Missing path: <span class="font-medium text-gray-500">{{ $requestedPath }}</span>
                    </p>

                    <div class="mt-8 rounded-xl border border-sky-100 bg-sky-50/80 px-4 py-4 text-left">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-600">Popular categories</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($topCategories as $category)
                                <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                                    class="inline-flex items-center gap-2 rounded-full border border-sky-100 bg-white px-3 py-2 text-sm text-gray-700 transition hover:border-sky-200 hover:bg-sky-50">
                                    <span>{{ $category->name }}</span>
                                    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs text-sky-700">
                                        {{ $category->products_count }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4 text-sm font-semibold lg:justify-start">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-primary-600 transition hover:text-primary-700">
                            <span>Back to homepage</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ route('categories.index') }}" class="inline-flex items-center gap-2 text-primary-600 transition hover:text-primary-700">
                            <span>Browse categories</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ route('faq') }}" class="inline-flex items-center gap-2 text-primary-600 transition hover:text-primary-700">
                            <span>Support</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <p class="mt-8 text-xs leading-6 text-gray-400 lg:max-w-md">
                    Image attribution:
                    <a
                        href="https://www.magnific.com"
                        target="_blank"
                        rel="nofollow noopener noreferrer"
                        class="font-medium text-gray-500 underline decoration-gray-300 underline-offset-2 transition hover:text-gray-700"
                    >
                        Designed by Magnific
                    </a>
                </p>
            </div>
            </div>

            <div class="relative hidden min-h-[calc(100vh-8.5rem)] lg:block">
                <img
                    src="{{ asset('images/errors/404-robot.jpg') }}"
                    alt="Cartoon robot sitting by a stream"
                    width="520"
                    height="520"
                    loading="eager"
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-r from-white/18 via-transparent to-slate-900/8"></div>
                <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-white/70 to-transparent"></div>
            </div>
        </div>
    </section>
@endsection

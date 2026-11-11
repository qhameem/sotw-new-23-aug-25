@php
    $categoryNavigation = app(\App\Services\CategoryNavigationService::class);
    $groups = collect($categoryNavigation->getBroadGroups());
@endphp

<section class="my-4 space-y-3 rounded-lg border bg-gradient-to-tr from-white to-sky-50 p-4 text-sm leading-relaxed text-gray-600">
    <h2 class="text-lg font-semibold text-gray-900">What is Software on the Web?</h2>

    <p>Software on the Web is a weekly platform for curating and launching software products. We review submissions. You can find software for a specific task, and makers can put their products in front of potential buyers.</p>

    <p>Each published product gets its own page with a description, categories, and a link to the product. Where available, listings include features, use cases, pros, and limitations. Your page stays accessible after launch week while it remains published.</p>

    <p>Browse <a href="{{ route('categories.index') }}" class="underline">{{ number_format($categoryNavigation->getDirectoryCategoryCount()) }} categories</a> across {{ $groups->count() }} groups: {{ $groups->pluck('label')->join(', ', ', and ') }}. You can also browse by use case, who the tool is best for, platform, and pricing model. If you’re replacing a tool you already use, browse <a href="{{ route('alternatives.index') }}" class="underline">software alternatives</a>.</p>

    <p>Builders can <a href="{{ route('badges.index') }}" class="underline">get a badge</a> and test their site with the <a href="{{ url('/tools/launch-readiness-checker') }}" class="underline">Launch Readiness Checker</a> before launch. Partner ads run in the homepage sidebar.</p>

    <p>The homepage shows the latest week with published products. Weeks run Monday to Sunday, and past weeks have their own archive pages. Organic rankings account for engagement and launch exposure. Promoted listings appear in assigned positions.</p>

    <p>The site lists {{ number_format($headerStats['public_products']) }} published products and has logged {{ number_format($headerStats['product_clicks']) }} outbound product clicks. We recorded {{ number_format($headerStats['ai_bot_requests']) }} AI crawler requests in the past 30 days.</p>
</section>

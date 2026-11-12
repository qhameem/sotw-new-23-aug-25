@extends('layouts.app', ['blankPageTemplate' => true])

@section('title', 'Launch Pricing | Software on the Web')
@section('meta_description', 'Compare Free Launch and Premium Launch for listing your software on Software on the Web.')

@section('content')
    @php
        $siteStats = app(\App\Services\HeaderStatsService::class)->get();
        $stats = [
            'All-time product clicks' => $siteStats['product_clicks'],
            'Live products' => $siteStats['public_products'],
            'Total AI reads' => $siteStats['total_ai_reads'],
        ];
        $premiumPrice = \App\Support\PremiumLaunchPricing::display();
        $plans = [
            ['name' => 'Free Launch', 'price' => '$0', 'button_label' => 'Launch for free', 'term' => 'with a verified badge',
                'features' => ['Public product listing', 'Choose an available launch date after badge verification', 'Keep the Software on the Web badge live on your website']],
            ['name' => 'Premium Launch', 'price' => $premiumPrice, 'button_label' => 'Get Premium - '.$premiumPrice, 'term' => 'USD per product, one time',
                'features' => ['Scheduled permanent listing, subject to review', 'Dofollow link to your product', 'No badge required']],
        ];
        $questions = [
            'What does Free Launch require?' => 'Add our badge and a link back to Software on the Web on a public page of your website. Verify that page in the submission form and keep the badge live after launch.',
            'Does paying affect my ranking?' => 'Payment covers the listing and its benefits. Organic rankings, traffic, and sales are never guaranteed.',
            'How do I choose a launch date?' => 'Available dates are shown in the submission form. Our team reviews products for suitability on both plans.',
        ];
    @endphp

    <header class="mx-auto max-w-3xl px-2 py-12 text-center sm:py-16">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Launch your product</h1>
        <p class="mt-4 text-lg text-gray-600">List your software for free with a verified badge, or pay once for a Premium Launch.</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="{{ route('products.create') }}" class="rounded-lg bg-primary-600 px-5 py-2.5 font-semibold text-white hover:bg-primary-700">Submit product</a>
            <a href="#launch-plans" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 font-semibold text-gray-900 hover:bg-gray-50">Compare plans</a>
        </div>
    </header>

    <dl aria-label="Platform statistics" class="mb-10 grid grid-cols-1 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 sm:grid-cols-3">
        @foreach($stats as $label => $value)
            <div class="bg-white p-4 sm:p-5">
                <dt class="text-sm font-medium text-gray-600">{{ $label }}</dt>
                <dd class="mt-2 text-2xl font-bold tabular-nums text-gray-900 sm:text-3xl">
                    @if($value === null)
                        <span aria-label="Unavailable">—</span>
                    @else
                        {{ number_format($value) }}
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>

    <section id="launch-plans" aria-labelledby="launch-plans-title" class="scroll-mt-28">
        <h2 id="launch-plans-title" class="text-2xl font-bold text-gray-900">Choose your launch plan</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @foreach($plans as $plan)
                <article @class(['flex flex-col rounded-xl border bg-white p-6', 'border-primary-500' => $loop->last, 'border-gray-200' => $loop->first])>
                    <h3 class="text-xl font-semibold text-gray-900">{{ $plan['name'] }}</h3>
                    <p class="mt-3 text-sm text-gray-600"><strong class="mr-1 text-3xl text-gray-900">{{ $plan['price'] }}</strong> {{ $plan['term'] }}</p>
                    <ul class="my-5 flex-1 list-disc space-y-2 pl-5 text-sm text-gray-700">
                        @foreach($plan['features'] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('products.create') }}"
                        @class(['rounded-lg border px-4 py-2 text-center text-sm font-semibold', 'border-primary-600 bg-primary-600 text-white hover:bg-primary-700' => $loop->last, 'border-gray-200 text-gray-900 hover:bg-gray-50' => $loop->first])>
                        {{ $plan['button_label'] }} <span aria-hidden="true">→</span>
                    </a>
                </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="pricing-faq-title" class="my-12">
        <h2 id="pricing-faq-title" class="text-2xl font-bold text-gray-900">Pricing FAQ</h2>
        <div class="mt-4 divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
            @foreach($questions as $question => $answer)
                <details class="p-4">
                    <summary class="cursor-pointer font-semibold text-gray-900">{{ $question }}</summary>
                    <p class="mt-2 text-sm leading-relaxed text-gray-700">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
        <p class="mt-4 text-sm text-gray-700">
            <a href="{{ route('faq') }}#launch-pricing" class="underline">Submission policy</a>
            · <a href="{{ route('refund-policy') }}" class="underline">Refund and cancellation policy</a>
        </p>
    </section>

    <section class="mb-10 rounded-xl border border-gray-200 bg-white p-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Ready to launch?</h2>
        <a href="{{ route('products.create') }}" class="mt-4 inline-flex rounded-lg bg-primary-600 px-5 py-2.5 font-semibold text-white hover:bg-primary-700">Submit product</a>
    </section>
@endsection

@extends('layouts.app')

@section('title')
    Premium Spot Details
@endsection

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ url()->previous() ?? route('home') }}" class="text-gray-700 text-xs hover:underline">
            &larr; Back to previous page
        </a>
    </div>

    <h1 class="text-3xl font-bold mb-4">Premium Spot</h1>

    <p class="text-lg font-bold mb-4">Get a Premium Spot – $149/month</p>
    <p class="text-gray-700 mb-6">Put your product front and center on Software on the web. With a Premium Spot, you’ll get prime visibility that drives real results.</p>

    <hr class="my-6">

    <h2 class="text-2xl font-bold mb-4">🌟 Why Go Premium?</h2>

    <h3 class="text-xl font-semibold mb-2">Maximum Visibility</h3>
    <ul class="list-disc list-inside text-gray-700 mb-4">
        <li><strong>Featured on key pages</strong>: Your product appears on the homepage, as well as the weekly, monthly, and yearly pages — for an entire month.</li>
        <li><strong>Top placement in categories</strong>: Show up in the top 4 spots on your category and tag pages.</li>
        <li><strong>Fair rotation</strong>: We rotate Premium listings regularly (roughly 1 in every 5 products shown), so everyone gets their moment in the spotlight.</li>
    </ul>

    <h3 class="text-xl font-semibold mb-2">Bigger Reach</h3>
    <ul class="list-disc list-inside text-gray-700 mb-4">
        <li><strong>20,000 to 40,000 views per month</strong>: That’s how many times people could see your product.</li>
        <li><strong>Targeted traffic</strong>: Reach users who love discovering new tools, startups, and innovations.</li>
    </ul>

    <h3 class="text-xl font-semibold mb-2">Better Targeting</h3>
    <ul class="list-disc list-inside text-gray-700 mb-4">
        <li><strong>Relevant exposure</strong>: Show up for the right users, based on categories and tags.</li>
        <li><strong>Smart discovery</strong>: Your product gets highlighted when people explore similar products.</li>
    </ul>

    <h3 class="text-xl font-semibold mb-2">Clean, Focused Layout</h3>
    <ul class="list-disc list-inside text-gray-700 mb-4">
        <li><strong>Distraction-free pages</strong>: No clutter, no “related products” section to pull attention away.</li>
        <li><strong>Premium badge</strong>: Your product gets a subtle badge that shows it’s featured.</li>
    </ul>

    <h3 class="text-xl font-semibold mb-2">Real Results</h3>
    <ul class="list-disc list-inside text-gray-700 mb-4">
        <li><strong>Faster adoption</strong>: Ideal for launches or boosting existing momentum.</li>
        <li><strong>More visibility</strong>: Whether you're new or growing, Premium gets you noticed.</li>
    </ul>

    <h3 class="text-xl font-semibold mb-2">Simple & Flexible</h3>
    <ul class="list-disc list-inside text-gray-700 mb-4">
        <li><strong>Monthly pricing</strong>: Just $157/month. No lock-in — cancel anytime.</li>
        <li><strong>Easy renewal</strong>: Stay Premium for as long as it helps you grow.</li>
        <li><strong>High value</strong>: Up to 40K views for a small monthly cost.</li>
    </ul>

    <hr class="my-6">

    <h2 class="text-2xl font-bold mb-4">✅ Reserve Your Premium Spot</h2>

    <p class="text-gray-700 mb-4">Don’t let your product get lost in the crowd. Boost your reach, get seen by the right people, and grow faster with a Premium Spot on Software on the web.</p>

    <blockquote class="border-l-4 border-gray-300 pl-4 italic text-gray-600">
        <strong>Software on the web</strong> is a fair launch platform made for tech creators — a strong alternative to Product Hunt.<br>
        Launch your product. Get seen. Build momentum.
    </blockquote>
</div>
@endsection
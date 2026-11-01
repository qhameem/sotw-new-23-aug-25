@extends('layouts.app')

@section('meta_description', 'Learn how Software on the Web curates software, helps users compare tools, and gives makers a place to launch their products.')

@section('title', 'About Us | Software on the Web')
@section('content')
<div class="p-4">
    <div class="prose max-w-none">
        <section class="mb-10">
            <h1 class="site-heading-text text-4xl md:text-5xl font-bold text-gray-900 text-left mb-4">About Us</h1>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Discover Better Digital Tools, Every Day</h2>
            <p class="text-gray-700 leading-relaxed">
                <strong>Software On The Web</strong> is a curated platform for discovering standout digital products—
                built for makers, seekers, and anyone passionate about innovative software.
                We handpick tools through weekly curation, combining editorial discoveries with products submitted by makers.
            </p>
            <p class="text-gray-700 mt-2 leading-relaxed">
                We’re not a firehose of launches. We’re a growing library of quality.
            </p>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Our Mission</h2>
            <p class="text-gray-700 leading-relaxed">
                Our mission is simple: <strong>to connect people with the most useful, inspiring, and well-made digital products on the web</strong>.
                We help people discover software that solves real problems.
                No noise. Just the tools that matter.
            </p>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Quality Over Quantity</h2>
            <p class="text-gray-700 leading-relaxed">
                Our team manually reviews every tool, including submissions automatically approved through badge verification or paid checkout. Automatic approval reserves a launch; our team may still withhold or remove a product that is unsuitable.
            </p>
            <p class="text-gray-700 mt-2 leading-relaxed">
                We handpick software for its purpose, usefulness, and quality as part of our weekly curation.
            </p>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">A Lasting Collection</h2>
            <p class="text-gray-700 leading-relaxed">
                Listings are permanent, subject to suitability review and our Terms of Use. For badge listings, the badge must remain live on your website. If it is removed, our system automatically removes the listing from public view. Paid listings have no badge requirement.
            </p>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Built for Creators and Discoverers</h2>
            <p class="text-gray-700 leading-relaxed">
                <strong>Software On The Web</strong> serves two communities:
            </p>
            <ul class="list-disc list-inside text-gray-700 mt-2 space-y-1">
                <li><strong>Creators:</strong> Gain meaningful exposure for your product in a curated space where quality is valued.</li>
                <li><strong>Discoverers:</strong> Explore a trustworthy stream of handpicked tools without the overwhelm of endless listings.</li>
            </ul>
            <p class="text-gray-700 mt-2 leading-relaxed">
                We aim to bridge the gap between people building great things and those looking to use them.
            </p>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Transparent and Independent</h2>
            <p class="text-gray-700 leading-relaxed">
                We offer a Free Launch with a verified website badge and a paid Premium Launch without a badge requirement. Paid launch services and promotional placement are separate from organic rankings.
            </p>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">How Rankings Work</h2>
            <p class="text-gray-700 leading-relaxed">
                Our rankings are designed to reward genuine interest, not shortcuts. We look at a blend of real engagement signals,
                such as how people interact with products over time, while also giving newly published products a fair chance to be seen.
            </p>
            <p class="text-gray-700 mt-2 leading-relaxed">
                We also actively separate organic rankings from promotional placement, monitor for suspicious behavior, and avoid relying on
                any single public metric alone. That helps keep rankings useful for honest makers and trustworthy for people discovering products.
            </p>
        </section>

        <section class="mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Always Growing, Always Evolving</h2>
            <p class="text-gray-700 leading-relaxed">
                With a rapidly expanding catalog and new features on the way, our vision is to become the most trusted,
                human-curated resource for discovering digital products that matter.
            </p>
        </section>

        <x-submission-policy />

        <section>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Start Exploring</h2>
            <p class="text-gray-700 leading-relaxed">
                Whether you’re a founder looking to launch, or a curious user looking for your next favorite tool—
                <strong>Software On The Web is where discovery begins</strong>.
            </p>
            <p class="h-6"></p>
            <p>
                <a href="/" class="mt-4 transition text-sm underline text-primary-600">
                    Browse Products Now
                </a>
            </p>
        </section>
    </div>
</div>
@endsection

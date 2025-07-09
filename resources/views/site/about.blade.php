@extends('layouts.app')

@section('title', 'About Us | Software on the Web')

@section('content')
<x-main-content-layout>
    <x-slot:title>
        <h1 class="text-xl font-bold text-gray-800">About Us</h1>
    </x-slot:title>

    <div class="p-4">
        <div class="prose max-w-none">
            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Discover Better Digital Tools, Every Day</h2>
                <p class="text-gray-700 leading-relaxed">
                    <strong>Software On The Web</strong> is a curated platform for discovering standout digital products—
                    built for makers, seekers, and anyone passionate about innovative software.
                    We surface <strong>exceptional tools daily</strong>, chosen through a rigorous handpicking process by a team
                    that lives and breathes digital.
                </p>
                <p class="text-gray-700 mt-2 leading-relaxed">
                    We’re not a firehose of launches. We’re a growing library of quality.
                </p>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Our Mission</h2>
                <p class="text-gray-700 leading-relaxed">
                    Our mission is simple: <strong>to connect people with the most useful, inspiring, and well-made digital products on the web</strong>.
                    Every day, we handpick software that solves real problems, delights users, and pushes digital creativity forward.
                    No noise. Just the tools that matter.
                </p>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Quality Over Quantity</h2>
                <p class="text-gray-700 leading-relaxed">
                    Curation is our craft. Unlike open-submission platforms, we don’t list everything—we select <strong>only the best</strong>.
                    Each product featured has been evaluated by our team of experts with years of experience in digital product strategy, design, and development.
                </p>
                <p class="text-gray-700 mt-2 leading-relaxed">
                    We look for products with clear purpose, solid execution, and thoughtful design. If it’s on Software On The Web, it’s there because it earned its place.
                </p>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">A Permanent Collection</h2>
                <p class="text-gray-700 leading-relaxed">
                    We believe great products deserve lasting recognition. That’s why <strong>no product is ever removed</strong> from our platform.
                    Our library grows every day, becoming a richer, more reliable resource for discovering software that stands the test of time.
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
                    We don’t accept pay-to-play listings or hidden promotions. Every product is featured because it meets our standards—not because it paid to be there.
                    <strong>You can trust what you see.</strong>
                </p>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Always Growing, Always Evolving</h2>
                <p class="text-gray-700 leading-relaxed">
                    With a rapidly expanding catalog and new features on the way, our vision is to become the most trusted,
                    human-curated resource for discovering digital products that matter.
                </p>
            </section>

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
</x-main-content-layout>
@endsection
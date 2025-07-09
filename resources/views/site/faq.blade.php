@extends('layouts.app')

@section('title', 'Frequently Asked Questions | Software on the Web')
@section('description', 'Find answers to common questions about Software on the Web, our product discovery process, submission guidelines, and how to get the most out of our platform.')

@section('content')
<x-main-content-layout>
    <x-slot:title>
        <h1 class="text-xl font-bold text-gray-800">Frequently Asked Questions (FAQ)</h1>
    </x-slot:title>

    <div class="p-4">
        <div class="prose max-w-none">
            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-3">About Software on the Web</h2>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">What is Software on the Web?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            Software on the Web is a curated platform dedicated to helping you discover exceptional digital products and software tools. We handpick and showcase innovative software daily, focusing on quality, utility, and design. Our goal is to connect makers with users and provide a trusted resource for finding the best tools for your needs.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">How do you select the software featured on your site?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            Our experienced team meticulously handpicks every product. We look for software that solves real problems, offers a great user experience, and demonstrates innovation. We prioritize quality over quantity, ensuring that every tool listed has earned its place through merit.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">Is Software on the Web free to use?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            Yes, browsing and discovering software on our platform is completely free for users. We aim to provide an accessible resource for everyone looking for great digital tools.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">When are submitted software products published?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            Submitted software products are published daily at 7:00 AM UTC (GMT), which corresponds to 12:00 AM PDT on the same day.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-3">For Product Creators/Makers</h2>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">How can I submit my product to be featured?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            We currently have a hand-curation process. While we don't have a public submission form at this moment, we are always on the lookout for new and exciting products. The best way to get on our radar is to build an amazing product that people love and talk about! We plan to introduce a more formal submission process in the future.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">What are the benefits of having my product featured?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            Being featured on Software on the Web provides exposure to a dedicated audience of tech enthusiasts, early adopters, and professionals looking for quality software. It's an opportunity to showcase your product in a trusted, curated environment.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">Do you charge for featuring products?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            No, we do not charge for featuring products. Our selection is based purely on merit and our curation criteria. We believe in transparent and independent discovery.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-semibold text-gray-800 mb-3">Using the Platform</h2>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">How often do you add new products?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            We aim to feature new, exceptional tools daily. Our collection is constantly growing, providing fresh discoveries regularly.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">Can I save or bookmark products I like?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            Currently, we offer user accounts where you can manage your profile and submitted products. Features like upvoting are available, and we are considering adding bookmarking or "favorites" functionality in a future update.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">How can I stay updated with the latest featured software?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            The best way is to visit our homepage regularly! You can also follow us on any social media channels we may launch (details will be on our site). We also have an RSS feed for our blog.
                        </p>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-800 mb-3">Contact & Support</h2>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-xl font-medium text-gray-700">I have a question not listed here. How can I contact you?</h3>
                        <p class="text-gray-600 mt-1 leading-relaxed">
                            We'd love to hear from you! For any inquiries, feedback, or support, please visit our <a href="{{ route('about') }}" class="text-primary-600 hover:underline">About Us</a> page for contact information or look for contact details in the site footer.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-main-content-layout>
@endsection
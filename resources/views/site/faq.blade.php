@extends('layouts.app')

@section('title', 'Frequently Asked Questions | Software on the Web')
@section('description', 'Find answers to common questions about Software on the Web, our product discovery process, submission guidelines, and how to get the most out of our platform.')

@section('content')
@section('header-title', 'Frequently Asked Questions (FAQ)')

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
                        Our directory includes editorial discoveries and maker submissions. Verified badge submissions and completed Premium Launch payments can be automatically approved for scheduling. A listing is not an individual expert review or endorsement. See the submission policy below.
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
                        Scheduled products publish at {{ \App\Support\ProductPublishSchedule::getPublishTime() }} UTC on their selected launch date. Available dates appear in the submission form.
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
                        Use <a href="{{ route('products.create') }}" class="text-primary-600 underline">Add Product</a>, sign in, complete your product details, and choose a launch option. You can read the submission conditions before registering.
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
                        Free Launch has no monetary fee and requires a verified badge on your website. Premium Launch is a one-time paid option without a badge requirement. Browsing is free. See the current price and conditions below.
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
                        Yes. Signed-in users can save products into collections, reuse quick-start lists like Favorites or Saved for Later, and keep lists public or private.
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

        <x-submission-policy />

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
@endsection

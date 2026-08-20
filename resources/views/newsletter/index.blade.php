@extends('layouts.app')

@section('title', 'Newsletter | Software on the Web')

@section('content')
<div class="mx-auto max-w-2xl p-4 py-10 sm:py-16">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-10">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wider text-primary-600">Software worth knowing</p>
        <h1 class="site-heading-text text-3xl font-bold text-gray-900 sm:text-4xl">The best software, delivered.</h1>
        <p class="mt-4 text-base leading-7 text-gray-600">Get curated product discoveries and practical software insights. No spam.</p>

        @if (session('newsletter_success'))
            <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">
                {{ session('newsletter_success') }}
            </div>
        @else
            <form method="POST" action="{{ route('newsletter.store') }}" class="mt-8 space-y-5">
                @csrf
                <input type="hidden" name="source" value="newsletter_page">

                <div class="hidden" aria-hidden="true">
                    <label for="company">Company</label>
                    <input id="company" name="company" type="text" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First name <span class="font-normal text-gray-400">(optional)</span></label>
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" autocomplete="given-name" maxlength="100"
                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" required autocomplete="email" maxlength="254"
                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>

                <div>
                    <label class="flex items-start gap-3 text-sm text-gray-600">
                        <input name="consent" type="checkbox" value="1" required class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span>I agree to receive the Software on the Web newsletter. I can unsubscribe at any time.</span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('consent')" />
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Subscribe
                </button>
            </form>
        @endif
    </div>
</div>
@endsection

@php
    $newsletterFormId = 'newsletter-email-'.($placement ?? 'inline');
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    @if (session('newsletter_success'))
        <div class="text-sm text-green-700" role="status">
            {{ session('newsletter_success') }}
        </div>
    @else
        <h2 class="text-base font-semibold text-gray-900">Discover better software</h2>
        <p class="mt-1 text-sm leading-5 text-gray-500">Curated products and practical insights. No spam.</p>

        <form method="POST" action="{{ route('newsletter.store') }}" class="mt-4">
            @csrf
            <input type="hidden" name="source" value="{{ $placement ?? 'inline' }}">

            <div class="hidden" aria-hidden="true">
                <label for="newsletter-company-{{ $placement ?? 'inline' }}">Company</label>
                <input id="newsletter-company-{{ $placement ?? 'inline' }}" name="company" type="text" tabindex="-1" autocomplete="off">
            </div>

            <label for="{{ $newsletterFormId }}" class="sr-only">Email address</label>
            <div class="flex gap-2">
                <input id="{{ $newsletterFormId }}" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" maxlength="254" placeholder="you@example.com"
                    class="min-w-0 flex-1 rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <button type="submit" class="shrink-0 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Join
                </button>
            </div>

            @if ($errors->has('email'))
                <p class="mt-2 text-xs text-red-600">{{ $errors->first('email') }}</p>
            @endif

            <label class="mt-3 flex items-start gap-2 text-xs leading-4 text-gray-500">
                <input name="consent" type="checkbox" value="1" required class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span>I agree to receive the newsletter and can unsubscribe anytime.</span>
            </label>
            @if ($errors->has('consent'))
                <p class="mt-2 text-xs text-red-600">{{ $errors->first('consent') }}</p>
            @endif
        </form>
    @endif
</div>

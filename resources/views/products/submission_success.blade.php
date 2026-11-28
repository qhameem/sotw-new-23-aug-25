@extends('layouts.app')

@section('title', 'Product Submission Complete')
@section('mobile_page_header_only', '1')
@section('hide_sidebar_content', '1')

@section('content')
    @php
        $editUrl = $product->exists ? route('products.edit', $product) : null;
        $referenceId = 'SOTW-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
        $submittedAt = $product->created_at?->timezone(config('app.timezone'))->format('M j, Y \a\t g:i A');
        $isBadgeSubmission = $product->submission_type === 'badge';
        $steps = [
            ['Received', 'Done'],
            ['In review', 'Up to ' . $daysToLive . ' days'],
            ['Approved', 'We email you'],
            ['Scheduled', 'Your launch date'],
        ];
    @endphp

    <div class="mx-auto max-w-4xl space-y-6 pt-6 pb-6 sm:pb-10" x-data="{
        referenceStatus: 'Copy',
        badgeStatus: 'Copy',
        async copy(value, status) {
            try {
                await navigator.clipboard.writeText(value);
                this[status] = 'Copied!';
            } catch {
                this[status] = 'Copy failed';
            }
            setTimeout(() => this[status] = 'Copy', 2000);
        }
    }">
        <section aria-labelledby="submission-heading" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-800" aria-hidden="true">
                    <x-simpleline-check class="h-5 w-5" />
                </span>
                <div>
                    <h1 id="submission-heading" class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Submission received</h1>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600 sm:text-base">
                        @if($isBadgeSubmission)
                            Your product is automatically approved for scheduling. Launching on <strong>{{ $launchDateFormatted }}</strong>.
                        @else
                            Your product is in the review queue. We will email you when it is approved and scheduled.
                        @endif
                    </p>
                </div>
            </div>

            <dl class="mt-5 grid gap-3 lg:grid-cols-2">
                <div class="relative flex flex-col justify-center rounded-xl border border-gray-100 bg-gray-50 px-3 py-3 pr-28 sm:px-4 sm:pr-28">
                    <dt class="text-sm font-medium text-gray-600">Reference ID</dt>
                    <dd class="break-all font-semibold text-gray-900">
                        <span x-ref="referenceId">{{ $referenceId }}</span>
                        <button type="button" @click="copy($refs.referenceId.textContent, 'referenceStatus')"
                            x-text="referenceStatus" aria-live="polite"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-100">Copy</button>
                    </dd>
                </div>
                <div class="flex flex-col justify-center rounded-xl border border-gray-100 bg-gray-50 px-3 py-3 sm:px-4">
                    <dt class="text-sm font-medium text-gray-600">Submitted</dt>
                    <dd class="font-semibold text-gray-900">{{ $submittedAt ?? 'Just now' }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('products.my') }}" class="rounded-xl bg-primary-500 px-5 py-3 text-sm font-semibold text-[var(--color-primary-button-text)] hover:bg-primary-600 sm:text-base">
                    Track my submissions
                </a>
                <a @if($editUrl) href="{{ $editUrl }}" @else role="link" aria-disabled="true" @endif
                    class="rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-50 sm:text-base">
                    Edit submission
                </a>
            </div>
        </section>

        @include('products.partials.submission-readiness')

        @if($isBadgeSubmission)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">What happens next</h2>
                <ol class="space-y-2 text-sm text-gray-700 list-decimal list-inside">
                    <li>Your placement is automatically approved for scheduling. Our team still manually reviews every tool and may withhold or remove unsuitable products.</li>
                    <li>Your badge was verified successfully and your launch week is reserved.</li>
                    <li>Keep the badge live. Failed verification is reported to administrators for review.</li>
                    <li>Your listing goes live on <strong>{{ $launchDateFormatted }}</strong>.</li>
                </ol>
                <p class="mt-6 border-t border-gray-200 pt-4 text-sm text-gray-600">
                    Need help? Include reference <strong class="text-gray-900">{{ $referenceId }}</strong> when you contact support.
                </p>
            </div>

            {{-- Badge Snippet Section --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Keep This Badge On Your Site</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Keep this badge on your website so your scheduled launch stays verified.
                    Your listing and <strong>dofollow backlink</strong> remain available while the badge stays verified, subject to manual suitability review and our Terms of Use.
                </p>

                <div class="relative">
                    <pre x-ref="badgeSnippet"
                        class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs font-mono text-gray-700 whitespace-pre-wrap break-all overflow-x-auto pr-24">{{ $badgeSnippet }}</pre>
                    <button
                        type="button"
                        @click="copy($refs.badgeSnippet.textContent, 'badgeStatus')"
                        x-text="badgeStatus"
                        aria-live="polite" class="absolute top-2 right-2 px-3 py-1 text-xs font-medium bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700 transition">
                        Copy
                    </button>
                </div>

                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Tip:</strong> Place this on a publicly accessible page of your website. Homepage placement is encouraged.
                        We'll continue checking for the badge automatically.
                    </p>
                </div>
            </div>
        @else
            <section aria-labelledby="submission-steps" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 id="submission-steps" class="text-xl font-bold text-gray-900">What happens next</h2>
                    <p class="text-sm text-gray-600">You can edit while the status is pending.</p>
                </div>

                <ol class="mt-6 grid gap-4 sm:grid-cols-4">
                    @foreach($steps as [$label, $description])
                        <li @if($loop->index === 1) aria-current="step" @endif class="flex gap-3 sm:block">
                            <div class="flex items-center gap-2" aria-hidden="true">
                                <span @class([
                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2',
                                    'border-green-800 bg-green-800 text-white' => $loop->first,
                                    'border-[#d92d16]' => $loop->index === 1,
                                    'border-gray-300' => $loop->index > 1,
                                ])>
                                    @if($loop->first)
                                        <x-simpleline-check class="h-3.5 w-3.5" />
                                    @elseif($loop->index === 1)
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#d92d16]"></span>
                                    @endif
                                </span>
                                @unless($loop->last)
                                    <span @class(['hidden h-0.5 grow sm:block', 'bg-green-800' => $loop->first, 'bg-gray-200' => !$loop->first])></span>
                                @endunless
                            </div>
                            <div class="sm:mt-2">
                                <p class="font-semibold {{ $loop->index > 1 ? 'text-gray-600' : 'text-gray-900' }}">{{ $label }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ $description }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-6 border-t border-gray-200 pt-4 text-sm text-gray-600">
                    Need help? Include reference <strong class="text-gray-900">{{ $referenceId }}</strong> when you contact support.
                </p>
            </section>
        @endif
    </div>
    @if($submissionSuccessModal)
        @include('products.partials._submission-success-modal', ['modal' => $submissionSuccessModal])
    @endif
@endsection

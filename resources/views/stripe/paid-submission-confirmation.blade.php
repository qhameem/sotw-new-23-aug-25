@php
    $mainContentMaxWidth = 'max-w-none';
    $containerMaxWidth = 'max-w-none';
    $hideSidebar = true;
    $mainPadding = 'px-0';
@endphp

@extends('layouts.app')

@section('title', 'Paid Submission Confirmed')

@section('content')
    @php
        $referenceId = 'TXN-' . str_pad((string) $checkout->id, 9, '0', STR_PAD_LEFT);
        $amount = number_format(($checkout->amount_cents ?? 0) / 100, 2);
        $scheduleAt = $checkout->schedule_date
            ? \App\Support\ProductPublishSchedule::forDate($checkout->schedule_date)
            : null;
        $scheduledDate = $scheduleAt?->format('M j, Y, H:i \U\T\C') ?? $checkout->schedule_date?->format('M j, Y') ?? 'Not selected';
        $paidDate = $checkout->paid_at?->format('M j, Y') ?? now()->format('M j, Y');
        $receiptEmail = $checkout->receipt_sent_at ? ($checkout->user?->email ?? auth()->user()?->email) : null;
        $productLogo = \App\Support\ProductLogo::storedUrl($product);
        $productUrl = $product?->slug
            ? route('products.show', [
                'product' => $product->slug,
                'return_to' => route('stripe.paid-submission.confirmation', $checkout),
                'return_label' => 'Payment Confirmation',
            ])
            : ($checkout->product_link ?: null);
        $productEditUrl = $product?->id ? route('products.edit', $product) : null;
        $productInitial = \App\Support\ProductLogo::initial($product ?? (object) ['name' => $checkout->product_name]);
        $canChangeScheduleDate = $checkout->canChangeScheduleDateOnce();
        $changeScheduleErrors = $errors->getBag('changePaidSubmissionSchedule');
        $changeMinDate = now()->startOfDay()->next(\Illuminate\Support\Carbon::MONDAY)->toDateString();
        $changeMaxDate = now()->startOfDay()->addDays(60)->toDateString();
        $changeScheduleOptions = [];

        for ($current = \Illuminate\Support\Carbon::parse($changeMinDate); $current->lte(\Illuminate\Support\Carbon::parse($changeMaxDate)); $current->addWeek()) {
            $changeScheduleOptions[] = [
                'value' => $current->toDateString(),
                'label' => $current->format('D, M j, Y'),
            ];
        }
    @endphp

    <div class="mx-auto w-full max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-10 xl:px-12">
        <div class="w-full">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white px-5 py-6 shadow-[0_12px_32px_rgba(15,23,42,0.08)]">
                @if(session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="flex flex-col gap-8 lg:grid lg:grid-cols-2 lg:items-start lg:gap-8">
                    <div class="flex flex-col items-center text-center lg:items-start lg:justify-center lg:py-4 lg:text-left">
                        <div class="flex items-center gap-4">
                            <div class="flex h-20 w-20 items-center justify-center">
                                <svg class="h-28 w-28 text-green-500" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="currentColor" />
                                </svg>
                            </div>

                            <div class="flex-1 text-left">
                                <h1 class="text-[1.5rem] font-semibold tracking-[-0.03em] text-emerald-600">
                                    Payment Successful!
                                </h1>

                                <p class="mt-1 text-[0.95rem] leading-5 text-slate-500">
                                    Your payment for <strong>Premium Launch</strong> has been processed successfully.
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="flex w-full flex-col gap-6">
                        <div class="rounded-xl bg-slate-50 px-5 py-5">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-6">
                                <div>
                                    <p class="text-[1.1rem] font-medium leading-none text-slate-500">Amount</p>
                                </div>
                                <p class="text-right text-[1.45rem] font-semibold leading-none tracking-[-0.03em] text-slate-900">
                                    ${{ $amount }}
                                </p>
                            </div>

                            <dl class="space-y-6 pt-6">
                                <div class="grid grid-cols-2 items-center gap-x-6">
                                    <dt class="text-sm font-medium text-slate-500">Transaction ID</dt>
                                    <dd class="inline-block justify-self-end rounded-lg border border-slate-300 bg-white px-3 py-1 text-right font-mono text-xs font-semibold text-slate-900">
                                        {{ $referenceId }}
                                    </dd>
                                </div>

                                <div class="grid grid-cols-2 items-center gap-x-6">
                                    <dt class="text-sm font-medium text-slate-500">Payment date</dt>
                                    <dd class="justify-self-end text-right text-sm font-semibold text-slate-900">
                                        {{ $paidDate }}
                                    </dd>
                                </div>

                                <div class="grid grid-cols-2 items-center gap-x-6">
                                    <dt class="text-sm font-medium text-slate-500">Product</dt>
                                    <dd class="justify-self-end text-right">
                                        <div class="inline-flex items-center justify-end gap-3 text-right">
                                            @if($productUrl)
                                                <a href="{{ $productUrl }}" class="text-xs font-medium text-primary-600 transition hover:text-primary-700 hover:underline">
                                                    View
                                                </a>
                                            @endif

                                            @if($productEditUrl)
                                                <a href="{{ $productEditUrl }}" class="text-xs font-medium text-primary-600 transition hover:text-primary-700 hover:underline">
                                                    Edit
                                                </a>
                                            @endif

                                            @if($productUrl)
                                                <a href="{{ $productUrl }}" class="inline-flex items-center justify-end gap-2 text-[0.98rem] font-semibold leading-6 text-slate-900 transition hover:text-primary-600">
                                                    @if($productLogo)
                                                        <img src="{{ $productLogo }}" alt="{{ $checkout->product_name }} logo" class="h-7 w-7 rounded-md border border-slate-200 object-cover">
                                                    @else
                                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-semibold text-slate-600">
                                                            {{ $productInitial }}
                                                        </span>
                                                    @endif
                                                    <span>{{ $checkout->product_name }}</span>
                                                </a>
                                            @else
                                                <span class="inline-flex items-center justify-end gap-2 text-[0.98rem] font-semibold leading-6 text-slate-900">
                                                    @if($productLogo)
                                                        <img src="{{ $productLogo }}" alt="{{ $checkout->product_name }} logo" class="h-7 w-7 rounded-md border border-slate-200 object-cover">
                                                    @else
                                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white text-[0.75rem] font-semibold text-slate-600">
                                                            {{ $productInitial }}
                                                        </span>
                                                    @endif
                                                    <span>{{ $checkout->product_name }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </dd>
                                </div>

                                <div class="grid grid-cols-2 items-center gap-x-6">
                                    <dt class="text-sm font-medium text-slate-500">Product publish date</dt>
                                    <dd class="justify-self-end text-right">
                                        <div class="inline-flex flex-nowrap items-center justify-end gap-3 text-right whitespace-nowrap">
                                            @if($canChangeScheduleDate)
                                                <button
                                                    type="button"
                                                    @click="$dispatch('open-modal', { name: 'change-paid-submission-date-modal' })"
                                                    class="whitespace-nowrap text-xs font-medium text-primary-600 transition hover:text-primary-700 hover:underline"
                                                >
                                                    Change once
                                                </button>
                                            @endif
                                            <span class="whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-mono font-semibold text-slate-900">{{ $scheduledDate }}</span>
                                        </div>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        @if($receiptEmail)
                            <div class="flex w-full items-center gap-3 rounded-xl bg-sky-50 px-4 py-4 text-left text-xs text-slate-500">
                                <svg class="h-6 w-6 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v10.5A2.75 2.75 0 0 1 17.25 20H6.75A2.75 2.75 0 0 1 4 17.25V6.75Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 7 7 5 7-5" />
                                </svg>
                                <p>Receipt sent to {{ $receiptEmail }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

    @if($canChangeScheduleDate || $changeScheduleErrors->any())
        <x-modal
            name="change-paid-submission-date-modal"
            :show="$changeScheduleErrors->any()"
            maxWidth="lg"
            :scrollable="false"
            viewportPadding="compact"
            focusable
        >
            <div class="px-6 py-6 sm:px-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Change publish date</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            You can change your product publish date only once, and only before the product is published.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'change-paid-submission-date-modal')"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                        aria-label="Close change publish date modal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <p><span class="font-medium text-slate-900">Current publish date:</span> {{ $scheduledDate }}</p>
                    <p class="mt-1"><span class="font-medium text-slate-900">Available dates:</span> Mondays from {{ \Illuminate\Support\Carbon::parse($changeMinDate)->format('M j, Y') }} to {{ \Illuminate\Support\Carbon::parse($changeMaxDate)->format('M j, Y') }}</p>
                </div>

                <form method="POST" action="{{ route('stripe.paid-submission.schedule-date.update', $checkout) }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="schedule_date" class="block text-sm font-medium text-slate-700">New publish date</label>
                        <select
                            id="schedule_date"
                            name="schedule_date"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            required
                        >
                            @foreach($changeScheduleOptions as $option)
                                <option value="{{ $option['value'] }}" @selected(old('schedule_date', $checkout->schedule_date?->toDateString()) === $option['value'])>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @if($changeScheduleErrors->has('schedule_date'))
                            <p class="mt-2 text-sm text-red-600">{{ $changeScheduleErrors->first('schedule_date') }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="$dispatch('close-modal', 'change-paid-submission-date-modal')"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700"
                        >
                            Update date
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endif
@endsection

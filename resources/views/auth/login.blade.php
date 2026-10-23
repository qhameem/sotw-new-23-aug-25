@extends('layouts.app', ['hideSidebar' => true])

@section('hide_desktop_page_header', '1')

@section('content')
    <div class="mx-auto my-8 w-full max-w-md rounded-xl border border-gray-300 bg-transparent px-6 py-6 sm:my-12">
    @php
        $lastMethod = request()->cookie(\App\Support\LastSignInMethod::COOKIE);
        $methods = ['google', 'email'];
    @endphp
    <div
        x-data="{
            intendedUrl: @js(session('url.intended', url()->previous())),
            googleSubmitting: false,
            showEmail: {{ $errors->has('email') && session('status') !== 'otp-sent' && ! $errors->has('otp') ? 'true' : 'false' }},
            emailSubmitting: false,
            otpSubmitting: false,
            otpSent: {{ session('status') === 'otp-sent' || $errors->has('otp') ? 'true' : 'false' }},
            otpEmail: @js(old('email', session('auth_email')))
        }"
        class="login-page space-y-6"
    >
        <x-auth-session-status class="mb-4" :status="session('status') === 'otp-sent' ? null : session('status')" />

        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Sign in or create an account</h1>
            <p class="mt-2 text-sm text-gray-600">Submit products, track your launches and create collections.</p>
        </div>

        <div x-show="!otpSent" class="space-y-4 pt-2">
            @foreach ($methods as $method)
                @if (! $loop->first)
                    <div class="login-divider flex items-center gap-3 text-xs text-gray-700"><span class="h-px flex-1 bg-gray-300"></span><span>or</span><span class="h-px flex-1 bg-gray-300"></span></div>
                @endif
                <div class="relative">
                    @if ($lastMethod === $method)
                        <span class="login-last-used absolute right-3 z-10 rounded-md px-2 py-0.5 text-[10px] font-semibold">Last used</span>
                    @endif
                    @include('auth.partials.'.$method.'-login-button', ['loginPage' => true, 'primary' => $loop->first])
                </div>
            @endforeach
        </div>

        <div x-show="showEmail && !otpSent" x-transition.opacity.duration.200ms style="display: none;">
            @include('auth.partials.login-form')
        </div>

        <div x-show="otpSent" x-transition.opacity.duration.200ms class="rounded-xl border border-emerald-200 bg-emerald-50 px-6 py-6 text-center" style="display: none;">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 8l-8.97 5.7a2 2 0 0 1-2.06 0L2 8" />
                    <path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" />
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">Code sent</h3>
            <p class="mt-2 text-sm text-gray-600">
                <span x-show="otpEmail">We sent a 6-digit sign-in code to <span class="font-semibold text-gray-900" x-text="otpEmail"></span>.</span>
                <span x-show="!otpEmail">We sent a 6-digit sign-in code to your email address.</span>
            </p>
            <p class="mt-2 text-sm text-gray-500">Check your inbox and spam folder, then enter the code below to continue.</p>
            @include('auth.partials.otp-form')
            <button
                type="button"
                @click="otpSent = false; showEmail = true; googleSubmitting = false; emailSubmitting = false; otpSubmitting = false; $nextTick(() => $refs.emailInput?.focus())"
                class="mt-5 inline-flex items-center justify-center rounded-full border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition-colors hover:bg-gray-50 hover:border-gray-300"
            >
                Use another email
            </button>
        </div>

        <div class="text-center text-sm text-gray-600" x-show="!otpSent">
            <p class="text-xs leading-relaxed">By continuing, you agree to the <a href="{{ route('legal') }}#terms-of-use" class="underline underline-offset-2">Terms</a> and <a href="{{ route('legal') }}#privacy-policy" class="underline underline-offset-2">Privacy Policy</a>.</p>
        </div>
    </div>
    </div>
@endsection

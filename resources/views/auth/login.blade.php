<x-guest-layout>
    <div
        x-data="{
            intendedUrl: @js(session('url.intended', url()->previous())),
            showEmail: {{ $errors->has('email') && session('status') !== 'otp-sent' && ! $errors->has('otp') ? 'true' : 'false' }},
            emailSubmitting: false,
            otpSubmitting: false,
            otpSent: {{ session('status') === 'otp-sent' || $errors->has('otp') ? 'true' : 'false' }},
            otpEmail: @js(old('email', session('auth_email')))
        }"
        class="space-y-6"
    >
        <x-auth-session-status class="mb-4" :status="session('status') === 'otp-sent' ? null : session('status')" />

        <div class="text-center">
            <h1 class="text-2xl font-semibold text-gray-900">Continue to your account</h1>
            <p class="mt-2 text-sm text-gray-600">Use Google or continue with email. New users are created automatically the first time they sign in.</p>
        </div>

        <div x-show="!otpSent" class="space-y-4">
            @include('auth.partials.google-login-button')
            @include('auth.partials.email-login-button')
        </div>

        <div x-show="showEmail && !otpSent" x-transition.opacity.duration.200ms style="display: none;">
            @include('auth.partials.login-form')
        </div>

        <div x-show="otpSent" x-transition.opacity.duration.200ms class="rounded-3xl border border-emerald-200 bg-emerald-50 px-6 py-6 text-center" style="display: none;">
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
                @click="otpSent = false; showEmail = true; emailSubmitting = false; otpSubmitting = false; $nextTick(() => $refs.emailInput?.focus())"
                class="mt-5 inline-flex items-center justify-center rounded-full border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition-colors hover:bg-gray-50 hover:border-gray-300"
            >
                Use another email
            </button>
        </div>

        <div class="text-center text-sm text-gray-600" x-show="!otpSent">
            No separate sign-up form is needed. Continuing with Google or email will create your account automatically if it doesn&apos;t exist yet.
        </div>
    </div>
</x-guest-layout>

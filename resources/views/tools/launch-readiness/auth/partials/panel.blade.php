@php
    $embedded = $embedded ?? false;
@endphp

<section
    x-data="{
        intendedUrl: @js($intended ?? $toolPath),
        googleSubmitting: false,
        showEmail: {{ $errors->has('email') && session('status') !== 'otp-sent' && ! $errors->has('otp') ? 'true' : 'false' }},
        emailSubmitting: false,
        otpSubmitting: false,
        otpSent: {{ session('status') === 'otp-sent' || $errors->has('otp') ? 'true' : 'false' }},
        otpEmail: @js(old('email', session('auth_email'))),
        openEmailLogin() {
            this.showEmail = true;
            this.$nextTick(() => this.$refs.emailInput?.focus());
        }
    }"
    class="{{ $embedded ? 'p-0' : 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60' }}"
>
    <x-auth-session-status class="mb-4" :status="session('status') === 'otp-sent' ? null : session('status')" />

    <div x-show="!otpSent" class="space-y-4">
        @if($toolGoogleAuthEnabled ?? false)
            <a href="{{ route('launch-readiness.auth.google', ['toolSlug' => $toolSlug]) }}"
               x-bind:href="'{{ route('launch-readiness.auth.google', ['toolSlug' => $toolSlug]) }}?intended=' + encodeURIComponent(intendedUrl || '{{ $toolPath }}')"
               @click="if (googleSubmitting) { $event.preventDefault(); return; } googleSubmitting = true"
               x-bind:class="googleSubmitting ? 'pointer-events-none border-blue-300 from-blue-50 to-blue-100/80 text-blue-900' : ''"
               class="flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-50">
                <span class="mr-3 flex h-6 w-6 items-center justify-center">
                    <img x-show="!googleSubmitting" class="h-6 w-6" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google logo">
                    <span x-cloak x-show="googleSubmitting" class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></span>
                </span>
                <span x-show="!googleSubmitting">Continue with Google</span>
                <span x-cloak x-show="googleSubmitting">Connecting to Google...</span>
            </a>
        @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center gap-3 text-sm font-semibold text-slate-500">
                    <img class="h-6 w-6 opacity-50" src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google logo">
                    <span>Continue with Google</span>
                    <span class="ml-auto rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-medium text-slate-500">Unavailable</span>
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-500">
                    {{ $toolGoogleAuthUnavailableReason ?? 'Google sign-in is unavailable right now.' }}
                </p>
            </div>
        @endif

        <button
            type="button"
            @click="openEmailLogin()"
            class="flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold tracking-wide text-slate-800 shadow-sm transition hover:bg-slate-50"
        >
            <svg class="mr-3 h-5 w-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" />
                <path d="m22 8-8.97 5.7a2 2 0 0 1-2.06 0L2 8" />
            </svg>
            Continue with email
        </button>
    </div>

    <div x-show="showEmail && !otpSent" x-transition.opacity.duration.200ms style="display: none;">
        <form method="POST" action="{{ route('launch-readiness.auth.email.send', ['toolSlug' => $toolSlug]) }}" class="space-y-5" @submit="emailSubmitting = true">
            @csrf
            <input type="hidden" name="intended" x-bind:value="intendedUrl">
            <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                <label for="company_name">Company</label>
                <input id="company_name" type="text" name="company_name" tabindex="-1" autocomplete="off">
            </div>
            <div>
                <x-input-label for="tool_email_login" :value="__('Email')" />
                <x-text-input id="tool_email_login" x-ref="emailInput" class="mt-1 block w-full" type="email" name="email" x-model="otpEmail" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                We&apos;ll email you a 6-digit one-time code. Entering it on the next screen signs you in and creates your tool account automatically if needed.
            </div>
            <button
                type="submit"
                :disabled="emailSubmitting"
                :class="emailSubmitting ? 'cursor-wait opacity-80' : 'hover:bg-slate-800'"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition"
            >
                <svg x-show="emailSubmitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" style="display: none;" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                    <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                </svg>
                <span x-text="emailSubmitting ? 'Sending code...' : 'Email me a sign-in code'"></span>
            </button>
        </form>
    </div>

    <div x-show="otpSent" x-transition.opacity.duration.200ms class="rounded-xl border border-emerald-200 bg-emerald-50 px-6 py-6 text-center" style="display: none;">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 8l-8.97 5.7a2 2 0 0 1-2.06 0L2 8" />
                <path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" />
            </svg>
        </div>
        <h3 class="mt-4 text-lg font-semibold text-slate-900">Code sent</h3>
        <p class="mt-2 text-sm text-slate-600">
            <span x-show="otpEmail">We sent a 6-digit sign-in code to <span class="font-semibold text-slate-900" x-text="otpEmail"></span>.</span>
            <span x-show="!otpEmail">We sent a 6-digit sign-in code to your email address.</span>
        </p>
        <p class="mt-2 text-sm text-slate-500">Check your inbox and spam folder, then enter the code below to continue.</p>

        <form method="POST" action="{{ route('launch-readiness.auth.email.verify', ['toolSlug' => $toolSlug]) }}" class="mt-5 space-y-4" @submit="otpSubmitting = true">
            @csrf
            <input type="hidden" name="email" x-bind:value="otpEmail">
            <div>
                <x-input-label for="tool_email_otp" :value="__('One-time code')" />
                <x-text-input
                    id="tool_email_otp"
                    class="mt-1 block w-full text-center text-lg tracking-[0.35em]"
                    type="text"
                    name="otp"
                    :value="old('otp')"
                    required
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="123456"
                />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>
            <button
                type="submit"
                :disabled="otpSubmitting"
                :class="otpSubmitting ? 'cursor-wait opacity-80' : 'hover:bg-slate-800'"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-900 bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition"
            >
                <svg x-show="otpSubmitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" style="display: none;" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                    <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                </svg>
                <span x-text="otpSubmitting ? 'Verifying code...' : 'Verify code and continue'"></span>
            </button>
        </form>

        <button
            type="button"
            @click="otpSent = false; showEmail = true; googleSubmitting = false; emailSubmitting = false; otpSubmitting = false; $nextTick(() => $refs.emailInput?.focus())"
            class="mt-5 inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
        >
            Use another email
        </button>
    </div>
</section>

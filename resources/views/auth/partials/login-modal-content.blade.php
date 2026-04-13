<div
    x-data="{ intendedUrl: '', showEmail: {{ $errors->has('email') ? 'true' : 'false' }} }"
    x-init="intendedUrl = window.location.href"
    class="px-8 py-5 sm:px-10 sm:py-6"
>
    <div class="flex justify-center mb-5">
        <x-application-logo class="h-9 w-auto" />
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="flex flex-col items-center justify-center mt-4">
        <div class="text-[1.4rem] text-gray-800 font-semibold tracking-tight">Continue to your account</div>
        <div class="text-xs text-gray-500 w-72 text-center">Use Google or continue with email. New users are created automatically the first time they sign in.</div>
    </div>

    <div class="mt-7 mb-3 space-y-4">
        @include('auth.partials.google-login-button')
        @include('auth.partials.email-login-button')

        <div x-show="showEmail" x-transition.opacity.duration.200ms class="space-y-4" style="display: none;">
            @include('auth.partials.login-form')
        </div>
    </div>
</div>

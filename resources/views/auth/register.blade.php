<x-guest-layout>
    <div
        x-data="{ intendedUrl: @js(session('url.intended', url()->previous())), showEmail: {{ $errors->has('email') ? 'true' : 'false' }} }"
        class="space-y-6"
    >
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div class="text-center">
            <h1 class="text-2xl font-semibold text-gray-900">Continue to your account</h1>
            <p class="mt-2 text-sm text-gray-600">Use Google or continue with email. New users are created automatically the first time they sign in.</p>
        </div>

        <div class="space-y-4">
            @include('auth.partials.google-login-button')
            @include('auth.partials.email-login-button')
        </div>

        <div x-show="showEmail" x-transition.opacity.duration.200ms style="display: none;">
            @include('auth.partials.login-form')
        </div>

        <div class="text-center text-sm text-gray-600">
            No separate sign-up form is needed. Continuing with Google or email will create your account automatically if it doesn&apos;t exist yet.
        </div>
    </div>
</x-guest-layout>

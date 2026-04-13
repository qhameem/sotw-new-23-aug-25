<form method="POST" action="{{ route('auth.email-link.send') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="intended" x-bind:value="intendedUrl">
    <div>
        <x-input-label for="email_modal_login" :value="__('Email')" />
        <x-text-input id="email_modal_login" x-ref="emailInput" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
        We&apos;ll email you a secure magic link. Opening it will sign you in and keep you logged in for 30 days on this device.
    </div>
    <div class="flex justify-end pt-1">
        <div>
        <button class="inline-flex items-center gap-2 text-sm bg-gray-900 text-white border border-gray-900 rounded-lg px-8 py-2.5 font-semibold shadow-sm transition-colors hover:cursor-pointer hover:bg-gray-800">
            {{ __('Email me a sign-in link') }}
            <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </div>
</form>

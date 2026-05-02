<form method="POST" action="{{ route('auth.email-link.send') }}" class="space-y-5" @submit="emailSubmitting = true">
    @csrf
    <input type="hidden" name="intended" x-bind:value="intendedUrl">
    <div>
        <x-input-label for="email_modal_login" :value="__('Email')" />
        <x-text-input id="email_modal_login" x-ref="emailInput" class="block mt-1 w-full" type="email" name="email" x-model="otpEmail" :value="old('email')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
        We&apos;ll email you a 6-digit one-time code. Entering it will sign you in and keep you logged in for 30 days on this device.
    </div>
    <div class="flex justify-end pt-1">
        <div>
        <button
            type="submit"
            :disabled="emailSubmitting"
            :class="emailSubmitting ? 'cursor-wait opacity-80' : 'hover:cursor-pointer hover:bg-gray-800'"
            class="inline-flex min-w-[220px] items-center justify-center gap-2 text-sm bg-gray-900 text-white border border-gray-900 rounded-lg px-8 py-2.5 font-semibold shadow-sm transition-colors"
        >
            <svg x-show="emailSubmitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" style="display: none;" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
            </svg>
            <span x-text="emailSubmitting ? 'Sending code...' : 'Email me a sign-in code'"></span>
            <span x-show="!emailSubmitting" aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </div>
</form>

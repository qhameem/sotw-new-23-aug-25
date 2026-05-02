<form method="POST" action="{{ route('auth.email-otp.verify') }}" class="mt-5 space-y-4" @submit="otpSubmitting = true">
    @csrf
    <input type="hidden" name="email" x-bind:value="otpEmail">
    <div>
        <x-input-label for="email_otp" :value="__('One-time code')" />
        <x-text-input
            id="email_otp"
            x-ref="otpInput"
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
        :class="otpSubmitting ? 'cursor-wait opacity-80' : 'hover:cursor-pointer hover:bg-gray-800'"
        class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-900 bg-gray-900 px-8 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors"
    >
        <svg x-show="otpSubmitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" style="display: none;" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
        </svg>
        <span x-text="otpSubmitting ? 'Verifying code...' : 'Verify code and continue'"></span>
    </button>
</form>

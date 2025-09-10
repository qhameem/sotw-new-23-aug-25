<form method="POST" action="{{ route('login') }}" class="space-y-6">
    @csrf
    <input type="hidden" name="intended" x-bind:value="intendedUrl">
    <div>
        <x-input-label for="email_modal_login" :value="__('Email')" />
        <x-text-input id="email_modal_login" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div class="mt-4">
        <x-input-label for="password_modal_login" :value="__('Password')" />
        <x-text-input id="password_modal_login" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="current-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div class="block mt-4">
        <label for="remember_me_modal_login" class="inline-flex items-center">
            <input id="remember_me_modal_login" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
        </label>
    </div>
    <div class="flex items-center justify-end mt-4">
        @if (Route::has('password.request'))
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
            </a>
        @endif
        <x-primary-button class="ms-3">
            {{ __('Log in') }}
        </x-primary-button>
    </div>
</form>
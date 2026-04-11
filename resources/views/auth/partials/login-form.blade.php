<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="intended" x-bind:value="intendedUrl">
    <div>
        <x-input-label for="email_modal_login" :value="__('Email')" />
        <x-text-input id="email_modal_login" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div class="mt-4">
        <!-- <x-input-label for="password_modal_login" :value="__('Password')" /> -->
        <div class="flex items-center justify-between">
            <x-input-label for="password_modal_login" :value="__('Password')" />

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="text-xs font-medium text-gray-400 hover:text-gray-600 hover:underline"
                >
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <x-text-input id="password_modal_login" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="current-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div class="flex flex-row justify-between items-center pt-1">
        <div>
        <label for="remember_me_modal_login" class="inline-flex items-center">
            <input id="remember_me_modal_login" type="checkbox" class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-500" name="remember">
            <span class="ms-2 text-xs text-gray-600">{{ __('Remember me') }}</span>
        </label>
        </div>
        <div>
        <button class="inline-flex items-center gap-2 text-sm bg-gray-900 text-white border border-gray-900 rounded-lg px-8 py-2.5 font-semibold shadow-sm transition-colors hover:cursor-pointer hover:bg-gray-800">
            {{ __('Log In') }}
            <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </div>
    
</form>

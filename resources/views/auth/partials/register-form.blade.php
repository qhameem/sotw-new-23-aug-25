<form method="POST" action="{{ route('register') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="intended" x-bind:value="intendedUrl">
    <div>
        <x-input-label for="name_modal_register" :value="__('Name')" />
        <x-text-input id="name_modal_register" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div class="mt-4">
        <x-input-label for="email_modal_register" :value="__('Email')" />
        <x-text-input id="email_modal_register" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div class="mt-4">
        <x-input-label for="password_modal_register" :value="__('Password')" />
        <x-text-input id="password_modal_register" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div class="mt-4">
        <x-input-label for="password_confirmation_modal_register" :value="__('Confirm Password')" />
        <x-text-input id="password_confirmation_modal_register" class="block mt-1 w-full"
                        type="password"
                        name="password_confirmation" required autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
    </div>
    <div class="flex items-center justify-end pt-1">
        <button class="inline-flex items-center gap-2 text-sm bg-gray-900 text-white border border-gray-900 rounded-lg px-8 py-2.5 font-semibold shadow-sm transition-colors hover:cursor-pointer hover:bg-gray-800">
            {{ __('Create account') }}
            <span aria-hidden="true">&rarr;</span>
        </button>
    </div>
</form>

<form method="POST" action="{{ route('register') }}" class="space-y-6">
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
    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="ms-4">
            {{ __('Register') }}
        </x-primary-button>
    </div>
</form>
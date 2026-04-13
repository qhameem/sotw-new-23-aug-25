<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Complete your profile</h1>
        <p class="mt-2 text-sm text-gray-600">Your email is verified and you&apos;re signed in. Add your display name to finish setting up your account.</p>
    </div>

    <form method="POST" action="{{ route('auth.complete-profile.store') }}" class="space-y-6">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Display Name')" />
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', auth()->user()->name)"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>

            <x-primary-button>
                {{ __('Save and continue') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

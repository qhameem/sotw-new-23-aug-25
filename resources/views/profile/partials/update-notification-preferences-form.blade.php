<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Notification Preferences') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Manage your email notification settings.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update.notifications') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <h3 class="text-md font-medium text-gray-700">{{ __('Product Notifications') }}</h3>
            <div class="mt-2 space-y-2">
                <label for="product_approval_notifications" class="flex items-center">
                    <input id="product_approval_notifications" name="notification_preferences[product_approval_notifications]" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ (auth()->user()->profile->notification_preferences['product_approval_notifications'] ?? true) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-600">{{ __('Product Approval Notifications') }}</span>
                    <x-input-error class="mt-2" :messages="$errors->get('notification_preferences.product_approval_notifications')" />
                </label>
                {{-- Add other product-related notification toggles here if needed --}}
            </div>
        </div>

        {{-- Add other categories of notifications here e.g., General, Newsletter --}}
        {{-- Example:
        <div>
            <h3 class="text-md font-medium text-gray-700">{{ __('Newsletter') }}</h3>
            <div class="mt-2 space-y-2">
                <label for="newsletter_notifications" class="flex items-center">
                    <input id="newsletter_notifications" name="notification_preferences[newsletter_notifications]" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ (auth()->user()->profile->notification_preferences['newsletter_notifications'] ?? true) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-600">{{ __('Receive Newsletter') }}</span>
                     <x-input-error class="mt-2" :messages="$errors->get('notification_preferences.newsletter_notifications')" />
                </label>
            </div>
        </div>
        --}}

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'notification-preferences-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
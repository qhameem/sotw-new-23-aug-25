<div x-data="{ activeTab: 'login', intendedUrl: '' }" x-init="intendedUrl = window.location.href" class="px-8 py-5 sm:px-10 sm:py-6">
    <div class="flex justify-center mb-5">
        <x-application-logo class="h-9 w-auto" />
    </div>
    
    <div class="mx-auto w-full max-w-xs">
        <nav class="relative grid grid-cols-2 rounded-2xl border border-gray-200 bg-gray-100/80 p-1" aria-label="Authentication tabs">
            <div
                class="pointer-events-none absolute inset-y-1 w-[calc(50%-0.25rem)] rounded-xl bg-white shadow-[0_10px_30px_rgba(15,23,42,0.08)] transition-transform duration-300 ease-out"
                :class="activeTab === 'login' ? 'translate-x-1' : 'translate-x-[calc(100%+0.25rem)]'"
            ></div>

            <button
                type="button"
                @click="activeTab = 'login'"
                :aria-pressed="activeTab === 'login'"
                class="relative z-10 rounded-xl px-4 py-2.5 text-sm font-medium tracking-tight transition-colors duration-200"
                :class="activeTab === 'login' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            >
                Log in
            </button>

            <button
                type="button"
                @click="activeTab = 'register'"
                :aria-pressed="activeTab === 'register'"
                class="relative z-10 rounded-xl px-4 py-2.5 text-sm font-medium tracking-tight transition-colors duration-200"
                :class="activeTab === 'register' ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            >
                Sign up
            </button>
        </nav>
    </div>

    <div class="flex flex-col items-center justify-center mt-4">
        <div class="text-[1.4rem] text-gray-800 font-semibold tracking-tight" x-text="activeTab === 'login' ? 'Welcome back!' : 'Create your account'"></div>
        <div class="text-xs text-gray-500 w-72 text-center" x-text="activeTab === 'login' ? 'Pick a sign-in method to continue exploring and submitting products.' : 'Join the community to submit products, save your favorites, and keep up with new launches.'"></div>
    </div>

    <div x-show="activeTab === 'login'" class="mt-7 mb-3">
        @include('auth.partials.google-login-button')
        <div class="mt-5 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-sm">or</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        @include('auth.partials.login-form')
    </div>

    <div x-show="activeTab === 'register'" class="mt-7 mb-3" style="display: none;">
        @include('auth.partials.google-login-button')
        <div class="mt-5 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-sm">or</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        @include('auth.partials.register-form')
    </div>
</div>

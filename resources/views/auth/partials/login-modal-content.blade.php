<div x-data="{ activeTab: 'login' }" class="p-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <a href="#" @click.prevent="activeTab = 'login'"
               :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'login', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'login' }"
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Sign In
            </a>
            <a href="#" @click.prevent="activeTab = 'register'"
               :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'register', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'register' }"
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Sign Up
            </a>
        </nav>
    </div>

    <div x-show="activeTab === 'login'" class="mt-6">
        @include('auth.partials.google-login-button')
        <div class="my-4 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-sm">Or with email</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        @include('auth.partials.login-form')
    </div>

    <div x-show="activeTab === 'register'" class="mt-6" style="display: none;">
        @include('auth.partials.google-login-button')
        <div class="my-4 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-sm">Or with email</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        @include('auth.partials.register-form')
    </div>
</div>
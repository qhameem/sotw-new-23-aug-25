<div x-data="{ activeTab: 'login', intendedUrl: '' }" x-init="intendedUrl = window.location.href" class="px-10 py-6">
    <div class="flex justify-center mb-6">
        <img src="{{ asset('storage/theme/branding/logo_68a8d1f5c0d39.svg') }}" alt="Site Logo" class="h-9 w-auto">
    </div>
    
    <div class="">
        
        <nav class="flex justify-center mx-auto bg-neutral-50 rounded-md w-1/2 px-1" aria-label="Tabs">
            <a href="#" @click.prevent="activeTab = 'login'"
               :class="{ 'text-gray-800': activeTab === 'login', 'border-transparent text-gray-400 hover:text-gray-700 hover:border-gray-300': activeTab !== 'login' }"
               class="whitespace-nowrap py-1 px-1 font-medium text-sm">
               <div class="flex flex-row bg-white px-2 py-0.5 rounded justify-between content-center items-center transition-shadow duration-200"  :class="{ 'shadow': activeTab === 'login' }">
                <div class="mr-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M8 16C8 18.8284 8 20.2426 8.87868 21.1213C9.75736 22 11.1716 22 14 22H15C17.8284 22 19.2426 22 20.1213 21.1213C21 20.2426 21 18.8284 21 16V8C21 5.17157 21 3.75736 20.1213 2.87868C19.2426 2 17.8284 2 15 2H14C11.1716 2 9.75736 2 8.87868 2.87868C8 3.75736 8 5.17157 8 8" stroke="#383838" stroke-width="1.5" stroke-linecap="round"></path> <path d="M8 19.5C5.64298 19.5 4.46447 19.5 3.73223 18.7678C3 18.0355 3 16.857 3 14.5V9.5C3 7.14298 3 5.96447 3.73223 5.23223C4.46447 4.5 5.64298 4.5 8 4.5" stroke="#383838" stroke-width="1.5"></path> <path d="M6 12L15 12M15 12L12.5 14.5M15 12L12.5 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
               </div>
                
               <div> 
                    Login
                </div>
            </div>
                        
            </a>
            <a href="#" @click.prevent="activeTab = 'register'"
               :class="{ 'text-gray-800': activeTab === 'register', 'border-transparent text-gray-400 hover:text-gray-700 hover:border-gray-300': activeTab !== 'register' }"
               class="whitespace-nowrap py-1 px-1 font-medium text-sm">
                <div class="flex flex-row bg-white px-2 py-0.5 rounded justify-between content-center items-center transition-shadow duration-200"  :class="{ 'shadow': activeTab === 'register' }">
                    <div class="mr-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <circle cx="12" cy="6" r="4" stroke="#383838" stroke-width="1.5"></circle> <path d="M15 13.3271C14.0736 13.1162 13.0609 13 12 13C7.58172 13 4 15.0147 4 17.5C4 19.9853 4 22 12 22C17.6874 22 19.3315 20.9817 19.8068 19.5" stroke="#383838" stroke-width="1.5"></path> <circle cx="18" cy="16" r="4" stroke="#383838" stroke-width="1.5"></circle> <path d="M18 14.6667V17.3333" stroke="#383838" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M16.6665 16L19.3332 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                   </div>
                
                   <div> 
                        Sign Up
                    </div>
                </div>
            </a>
        </nav>
    </div>

    <div class="flex flex-col items-center justify-center mt-4">
        <div class="text-xl text-gray-700 font-semibold">Welcome!</div>
        <div class="text-sm text-gray-500" x-text="activeTab === 'login' ? 'Please enter your details to login.' : 'Please enter your details to sign up.'"></div>
    </div>

    <div x-show="activeTab === 'login'" class="mt-8 mb-6">
        @include('auth.partials.google-login-button')
        <div class="mt-6 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-sm">or</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        @include('auth.partials.login-form')
    </div>

    <div x-show="activeTab === 'register'" class="mt-8 mb-6" style="display: none;">
        @include('auth.partials.google-login-button')
        <div class="mt-6 flex items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-sm">or</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        @include('auth.partials.register-form')
    </div>
</div>
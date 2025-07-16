<div class="flex items-center ms-auto me-4 sm:ms-6">
    <x-dropdown align="right" width="48">
        <x-slot name="trigger">
            <button @click="open = ! open" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500  bg-white  hover:text-gray-700  focus:outline-none transition ease-in-out duration-150">
                @auth {{-- Use Blade's @auth directive --}}
                    @if (Auth::user()->google_avatar)
                        <img src="{{ Auth::user()->google_avatar }}" alt="{{ Auth::user()->name }}" class="h-8 w-8 rounded-full object-cover me-2">
                    @else
                        <span class="flex items-center justify-center h-8 w-8 rounded-full bg-primary-500 text-white text-xs font-semibold me-2">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                    @endif
                    <div x-data="{ name: '{{ strtok(Auth::user()->name, ' ') }}' }" x-text="name" x-on:profile-updated.window="name = $event.detail.name.split(' ')[0]" class="hidden sm:block"></div>
               @else
                    <span class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-400 text-white text-xs font-semibold me-2">?</span>
                    <span>Guest</span>
               @endauth
                <div class="ms-1 hidden sm:block">
                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </button>
        </x-slot>

        <x-slot name="content">
           @auth
           <div class="px-4 py-2 text-sm text-gray-700">
               {{ strtok(Auth::user()->name, ' ') }}
           </div>
           @if(Auth::user()->hasRole('admin'))
           <x-dropdown-link :href="route('admin.theme.edit')" wire:navigate>
               {{ __('Theme Settings') }}
           </x-dropdown-link>
           <x-dropdown-link :href="route('admin.settings.index')" wire:navigate>
               {{ __('Settings') }}
           </x-dropdown-link>
           <x-dropdown-link :href="route('admin.seo.meta-tags.index')" wire:navigate>
               {{ __('Meta Tags') }}
           </x-dropdown-link>
           @endif
           <x-dropdown-link :href="route('profile.edit')" wire:navigate>
               {{ __('Profile') }}
           </x-dropdown-link>

           <!-- Authentication -->
           <button wire:click="logout" class="w-full text-start">
               <x-dropdown-link>
                   {{ __('Log Out') }}
               </x-dropdown-link>
           </button>
           @endauth
        </x-slot>
    </x-dropdown>
</div>
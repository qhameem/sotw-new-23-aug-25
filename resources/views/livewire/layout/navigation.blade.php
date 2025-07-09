<?php

use App\Livewire\Actions\Logout;
// $user variable removed from here for now to test direct Auth::user() access in template
// use Illuminate\Support\Facades\Auth;
// $user = Auth::user();


$logout = function (Logout $logout) {
    $logout();

    $this->redirect('/', navigate: true);
};

?>

<nav x-data="{ open: false }" class="bg-white  border-b border-gray-100 ">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" wire:navigate> {{-- Changed to home --}}
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 " />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate> {{-- Changed to home --}}
                        {{ __('Home') }}
                    </x-nav-link>
                    {{-- Add other main navigation links here if needed --}}
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500  bg-white  hover:text-gray-700  focus:outline-none transition ease-in-out duration-150">
                            @auth {{-- Use Blade's @auth directive --}}
                                @if (Auth::user()->google_avatar)
                                    <img src="{{ Auth::user()->google_avatar }}" alt="{{ Auth::user()->name }}" class="h-8 w-8 rounded-full object-cover me-2">
                                @else
                                    <span class="flex items-center justify-center h-8 w-8 rounded-full bg-primary-500 text-white text-xs font-semibold me-2">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </span>
                                @endif
                                <div x-data="{ name: '{{ strtok(Auth::user()->name, ' ') }}' }" x-text="name" x-on:profile-updated.window="name = $event.detail.name.split(' ')[0]"></div>
                           @else
                                <span class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-400 text-white text-xs font-semibold me-2">?</span>
                                <span>Guest</span>
                           @endauth
                            <div class="ms-1">
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
                       <x-dropdown-link :href="route('livewire.admin.seo.manage-meta-tags')" wire:navigate>
                           {{ __('Meta Tags') }}
                       </x-dropdown-link>
                       @endif
                       <x-dropdown-link :href="route('profile')" wire:navigate>
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

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                   <svg :class="{'hidden': open, 'inline-flex': ! open }" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                   <svg :class="{'hidden': ! open, 'inline-flex': open }" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate> {{-- Changed to home --}}
                {{ __('Home') }}
            </x-responsive-nav-link>
            {{-- Add other main responsive navigation links here if needed --}}
        </div>

        <!-- Responsive Settings Options -->
        @auth {{-- Use Blade's @auth directive --}}
        <div class="pt-4 pb-1 border-t border-gray-200 ">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 " x-data="{ name: {{ Js::from(Auth::user()->name) }} }" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
        @endauth
    </div>
</nav>

@props(['mainContentMaxWidth' => 'max-w-3xl', 'sidebarSticky' => true])

<x-top-bar />

<div class="flex flex-col md:flex-row max-w-7xl mx-auto">
    
    <!-- Main Content -->
    <main class="flex-1 w-full {{ $mainContentMaxWidth }} order-1 md:order-2 pl-6 pt-14">
        <x-page-header>
            <x-slot:title>
                {!! $title !!}
            </x-slot:title>
            @if (isset($actions))
                <x-slot:actions>
                    {!! $actions !!}
                </x-slot:actions>
            @endif
            @if (isset($below_header))
                <x-slot:below_header>
                    {{ $below_header }}
                </x-slot:below_header>
            @endif
        </x-page-header>
        <div>
            {{ $slot }}
        </div>
    </main>

    <!-- Right Sidebar -->
    <div @class([
        'w-full md:w-96 flex-shrink-0 order-2 md:order-3 h-auto md:h-screen',
        'md:sticky top-14' => $sidebarSticky,
    ])>
        <div class="flex-grow p-6">
            @if (isset($right_sidebar_content) && trim($right_sidebar_content))
                {{ $right_sidebar_content }}
            @else
                @include('partials._right-sidebar')
            @endif
        </div>
    </div>


</div>
<x-footer />
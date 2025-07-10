<div class="flex flex-col md:flex-row">
    <!-- Left Sidebar -->
    <div class="hidden md:flex md:flex-col md:w-72 md:h-screen md:sticky md:top-0 md:overflow-y-auto no-scrollbar border-r border-gray-200  flex-shrink-0">
        @include('partials._left-sidebar')
    </div>

    <!-- Main Content -->
    <main class="flex-1 w-full {{ $mainContentMaxWidth }} no-scrollbar order-1 md:order-2">
        <x-page-header>
            <x-slot:title>
                {{ $title }}
            </x-slot:title>
            <x-slot:actions>
                {{ $actions }}
            </x-slot:actions>
        </x-page-header>
        <div >
            {{ $slot }}
        </div>
    </main>

    <!-- Right Sidebar -->
    <div class="hidden md:flex md:flex-col @if(!$hideSidebar) md:w-96 @else md:w-auto @endif md:h-screen md:sticky md:top-0 md:overflow-y-auto no-scrollbar border-gray-200 flex-shrink-0 order-2 md:order-3">
        <x-right-sidebar-header />
        @if(!$hideSidebar)
            @if(!request()->is('admin/*'))
                <div class="flex-grow overflow-y-auto scrollbar-hide">
                    @include('partials._right-sidebar')
                </div>
            @endif
        @endif
    </div>

    <!-- Footer Menu for Mobile -->
    <div class="block md:hidden order-3">
        @include('partials._left-sidebar')
    </div>
</div>
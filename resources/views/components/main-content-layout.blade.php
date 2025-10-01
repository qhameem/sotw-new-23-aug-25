<x-top-bar />
<div class="flex flex-col md:flex-row pt-14 max-w-7xl mx-auto">

    <!-- Main Content -->
    <main class="flex-1 w-full {{ $mainContentMaxWidth ?? '' }} order-1 md:order-2 pl-6">
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
    <div class="hidden md:flex md:flex-col md:w-96 flex-shrink-0 order-2 md:order-3">
        <div class="flex-grow">
            @include('partials._right-sidebar')
        </div>
    </div>


</div>
<x-footer />
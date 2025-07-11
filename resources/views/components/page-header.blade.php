<div class="sticky top-0 bg-white z-10">
    <div class="flex justify-between items-center px-4 py-[0.78rem]">
        <div>
            <h1 class="text-lg md:text-base font-semibold tracking-tight">{!! $title !!}</h1>
        </div>
        <div>
            @if (isset($actions))
                {{ $actions }}
            @endif
        </div>
    </div>
    <hr class="">
</div>
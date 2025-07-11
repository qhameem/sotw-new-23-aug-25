<div class="sticky top-0 bg-white z-10">
    <div class="flex justify-between items-center px-4 pt-3.5">
        <div>
            <h1 class="text-lg md:text-base pt-4 font-semibold tracking-tight">{!! $title !!}</h1>
        </div>
        <div>
            @if (isset($actions))
                {{ $actions }}
            @endif
        </div>
    </div>
    <hr class="mt-[11px]">
</div>
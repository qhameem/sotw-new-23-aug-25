<div class="space-y-6 p-6">

    @if($product->techStacks->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-gray-800 mb-2">Tech Stack</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($product->techStacks as $techStack)
                    <span
                        class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                        {{ $techStack->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

</div>
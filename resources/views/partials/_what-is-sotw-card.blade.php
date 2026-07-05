@props([
    'compact' => false,
])

<div class="rounded-lg border bg-gradient-to-tr from-white to-sky-50 p-4 text-center tracking-tight">
    <h3 class="px-2 text-lg font-base font-noto-serif tracking-tighter text-gray-700">
        What is <span class="italic">Software on the Web?</span>
    </h3>

    @if($compact)
        <p class="mt-2 px-2 text-sm text-gray-600">
            A curated software directory with fresh launches, ranked tools, and product pages across AI, productivity, design, and developer workflows.
        </p>
    @else
        <p class="mt-2 px-2 text-sm text-gray-600">
            Software on the Web is a weekly curation and launch platform for software products. We handpick each tool to ensure quality and utility.
        </p>
        <div class="mt-5 space-y-4">
            <div>
                <h3 class="px-2 text-lg font-base font-noto-serif tracking-tighter text-gray-700">
                    For the <span class="italic">curious:</span>
                </h3>
                <p class="mt-2 px-2 text-sm text-gray-600">
                    We do the digging for you. Whether you need a tool for a specific task or a niche app you cannot find anywhere else, our detailed listings help you find exactly what you need.
                </p>
            </div>
            <div>
                <h3 class="px-2 text-lg font-base font-noto-serif tracking-tighter text-gray-700">
                    For the <span class="italic">builders:</span>
                </h3>
                <p class="mt-2 px-2 text-sm text-gray-600">
                    Showcase your work. Launch your product on our platform and get it in front of potential customers.
                </p>
            </div>
        </div>
    @endif

    <div class="mt-4 px-2">
        <a href="{{ route('signup') }}" class="rounded-lg bg-gray-900 px-4 py-1 text-sm font-semibold text-white">Sign up <span aria-hidden="true">&rarr;</span></a>
    </div>
</div>

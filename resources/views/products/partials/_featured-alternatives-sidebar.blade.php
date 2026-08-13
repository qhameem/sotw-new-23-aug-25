@if($alternativeProducts->isNotEmpty())
    <section aria-labelledby="sidebar-alternatives-heading">
        <h3 id="sidebar-alternatives-heading" class="text-base font-semibold text-gray-900">Alternatives</h3>

        <a href="{{ route('pseo.alternatives', $product->slug) }}" wire:navigate.hover
            class="group mt-4 flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-lg">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-primary-100 bg-primary-50 p-2" aria-hidden="true">
                <img src="{{ asset('images/icons/alternatives-list.svg') }}" alt="" class="h-full w-full object-contain">
            </span>

            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold text-gray-900 transition group-hover:text-primary-700">
                    View all {{ $product->name }} alternatives
                </span>
                <span class="mt-0.5 block text-xs text-gray-500">Compare similar products</span>
            </span>

            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-primary-600"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" />
                <path d="m12 5 7 7-7 7" />
            </svg>
        </a>
    </section>
@endif

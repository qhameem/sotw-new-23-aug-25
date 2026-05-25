@php
    $sidebarSnippets = \App\Models\CodeSnippet::where('location', 'sidebar')->get();
    $page = \Illuminate\Support\Facades\Route::currentRouteName();
    $isAdminSubmittedProduct = $product->user->hasRole('admin');
    $showAdminClaimPrompt = $isAdminSubmittedProduct && !(Auth::check() && Auth::user()->hasRole('admin'));
@endphp
<div class="space-y-6">
    <div class="sidebar-snippets-container w-full overflow-x-auto">
        @foreach ($sidebarSnippets as $snippet)
            @if ($snippet->shouldRenderFor(request()))
                {!! html_entity_decode($snippet->code) !!}
            @endif
        @endforeach
    </div>
    @unless($isAdminSubmittedProduct)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Submitted by</h3>
            <div class="flex items-center gap-2">
                <img src="{{ $product->user->avatar() }}" alt="{{ $product->user->name }}"
                    class="size-6 rounded-full border border-gray-100">
                <div class="site-body-text text-gray-800 text-sm font-medium">
                    {{ $product->user->name }}
                </div>
            </div>
        </div>
    @endunless

    @if($product->published_at)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Launched</h3>
            <time datetime="{{ $product->published_at->toDateString() }}" class="site-body-text text-sm font-medium text-gray-800">
                {{ $product->published_at->format('F j, Y') }}
            </time>
        </div>
    @endif

    @if(($idealForItems ?? collect())->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Ideal for</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($idealForItems as $idealForItem)
                    <span class="inline-flex items-center text-xs text-gray-700 font-medium bg-gray-50 px-2 py-0.5 rounded border border-gray-100">
                        {{ $idealForItem }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if($showAdminClaimPrompt)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-xs text-gray-500 mb-2">Ownership</h3>
            <p class="text-sm text-gray-700">
                If this is your product, contact us and we can help transfer it to you.
            </p>
            <button type="button"
                @click="$dispatch('open-modal', { name: 'admin-product-claim-modal' })"
                class="mt-2 inline-flex items-center text-sm font-medium text-primary-600 hover:underline">
                Claim this product
            </button>
        </div>
    @elseif(($canClaimProduct ?? false) && Auth::check())
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-xs text-gray-500 mb-2">Ownership</h3>

            @if(($currentUserClaim?->status ?? null) === \App\Models\ProductClaim::STATUS_PENDING)
                <p class="text-sm text-gray-700">
                    Your claim is pending admin review.
                </p>
                <a href="{{ route('products.claim.create', $product) }}" class="inline-flex items-center text-sm font-medium text-primary-600 hover:underline mt-2">
                    Manage claim
                </a>
            @elseif(Auth::user()->hasVerifiedEmail())
                <p class="text-sm text-gray-700">
                    If you own this product, you can submit a claim and the admin can assign it to you after review.
                </p>
                <a href="{{ route('products.claim.create', $product) }}" class="inline-flex items-center text-sm font-medium text-primary-600 hover:underline mt-2">
                    Claim this product
                </a>
            @else
                <p class="text-sm text-gray-700">
                    Verify your email first to submit a product claim.
                </p>
            @endif
        </div>
    @endif

    @if($product->techStacks->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Built with</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($product->techStacks as $techStack)
                    <a href="{{ route('pseo.builtWith', $techStack->slug) }}"
                       class="inline-flex items-center text-xs text-gray-700 font-medium bg-gray-50 px-2 py-0.5 rounded border border-gray-100 hover:bg-gray-100 hover:border-gray-200 transition-colors">
                        {{ $techStack->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>

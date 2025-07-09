<div>
    @if(Auth::check())
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[90vh] flex flex-col">
            <h2 class="text-xl font-semibold mb-4">Select Products to Feature</h2>
            <p class="text-gray-600 mb-4">Select the products you want to feature. Each spot costs $149 per month.</p>
            <div class="mb-4">
                <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search your products..." class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
            <div class="space-y-4 overflow-y-auto scrollbar-hide flex-grow">
                @forelse($this->filteredProducts as $product)
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div class="flex items-center">
                            <img src="{{ $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} logo" class="w-10 h-10 mr-4 rounded-md object-cover">
                            <div>
                                <h3 class="font-semibold">{{ $product->name }}</h3>
                                <a href="{{ $product->link }}" target="_blank" class="text-sm text-blue-500 hover:underline">{{ $product->link }}</a>
                                <p class="text-sm text-gray-500">{{ $product->tagline }}</p>
                            </div>
                        </div>
                        <input type="checkbox" wire:model.live="selectedProducts" value="{{ $product->id }}" class="form-checkbox h-5 w-5 text-primary-600">
                    </div>
                @empty
                    <p class="text-gray-500">You have no products to feature.</p>
                @endforelse
            </div>
            <div class="mt-6">
                <p class="text-lg font-semibold">Total: ${{ $totalPrice }}</p>
                <p class="text-sm text-gray-500">{{ count($selectedProducts) }} of {{ $spotsAvailable }} spots selected.</p>
            </div>
            <div class="mt-6 flex justify-end">
                <button wire:click="checkout" class="bg-primary-500 text-white hover:opacity-90 px-4 py-2 rounded-md font-semibold">
                    Proceed to Checkout
                </button>
            </div>
        </div>
    @else
        <div class="text-center">
            <p class="text-lg">Please <a href="{{ route('login') }}" class="text-primary-500 hover:underline">log in</a> to feature your products.</p>
        </div>
    @endif
</div>

<div class="bg-white border rounded-lg shadow-sm p-6 relative mb-6">
    <div class="flex items-start">
        {{-- Logo --}}
        <div class="mr-6 flex-shrink-0">
            @if($product->logo)
                <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="{{ $product->name }} logo" class="w-16 h-16 object-contain rounded-lg bg-gray-100 border">
            @else
                <img src="https://www.google.com/s2/favicons?sz=64&domain_url={{ urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="w-16 h-16 object-contain rounded-lg bg-gray-100 border">
            @endif
        </div>

        {{-- Main Content --}}
        <div class="flex-grow">
            {{-- Header --}}
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow" class="hover:underline">{{ $product->name }}</a>
                    </h2>
                    <div class="text-sm text-gray-600 mt-1">
                        <p><strong>Tagline:</strong> {{ $product->tagline }}</p>
                        <p><strong>Product Page Tagline:</strong> {{ $product->product_page_tagline }}</p>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        <strong>Slug:</strong> {{ $product->slug }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Submitted by: {{ $product->user->name ?? 'N/A' }}</div>
                </div>
                <label class="absolute top-4 right-4">
                    <input type="checkbox" name="products[]" value="{{ $product->id }}" class="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded product-checkbox">
                </label>
            </div>

            {{-- Description --}}
            <div class="prose prose-sm max-w-none mt-4 text-sm text-gray-700">
                {!! $product->description !!}
            </div>

            {{-- Categories --}}
            @if($product->categories->count() > 0)
                <div class="mt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Categories</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->categories as $category)
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 text-xs rounded-sm">{{ $category->name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6">
        <div class="flex justify-between items-center gap-3">
            <div>
                <label for="published_at_{{ $product->id }}" class="block text-xs font-medium text-gray-700 mb-1">Publish On:</label>
                <x-scheduled-datepicker name="published_at[{{ $product->id }}]" value="{{ today()->toDateString() }}" />
                <div id="utc-time-info-{{ $product->id }}" class="text-xs text-gray-500 mt-1"></div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.edit', $product->id) }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Edit
                </a>
            </div>
        </div>
        <div class="mt-4 flex justify-end items-center gap-3">
            <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="publish_option" value="specific_date">
                <input type="hidden" name="published_at" id="hidden_published_at_{{ $product->id }}">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" onclick="document.getElementById('hidden_published_at_{{ $product->id }}').value = document.querySelector('[name=\'published_at[{{ $product->id }}]\']').value; return confirm('Are you sure you want to approve this product for the selected date?')">
                    Publish on selected date and time
                </button>
            </form>
            <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="publish_option" value="now">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm font-medium hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                    Publish
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const settings = JSON.parse('{!! addslashes(json_encode($settings)) !!}');
    const publishTime = settings.product_publish_time || '07:00';
    const [publishHour, publishMinute] = publishTime.split(':').map(Number);

    function updateUTCTime() {
        const now = new Date();
        
        const timeInfoDiv = document.getElementById('utc-time-info-{{ $product->id }}');
        if (timeInfoDiv) {
            const formattedUTC = now.toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true,
                timeZone: 'UTC'
            });

            let nextLaunch = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), publishHour, publishMinute, 0));
            if (now >= nextLaunch) {
                nextLaunch.setUTCDate(nextLaunch.getUTCDate() + 1);
            }

            const diff = nextLaunch - now;
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            timeInfoDiv.textContent = `Current UTC: ${formattedUTC} (${hours} hours ${minutes} mins left till next launch)`;
        }
    }

    updateUTCTime();
    setInterval(updateUTCTime, 60000); // Update every minute
});
</script>
@endpush
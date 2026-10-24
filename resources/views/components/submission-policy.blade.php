@props(['collapsible' => false])

<section {{ $attributes->merge(['class' => 'not-prose my-6 rounded-lg border border-gray-200 bg-white p-5 text-sm text-gray-700']) }} aria-label="Product submission policy">
    @if($collapsible)
        <details>
            <summary class="cursor-pointer rounded text-lg font-semibold text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-600">Before you submit a product</summary>
            <div class="mt-4">
    @else
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Before you submit a product</h2>
    @endif
    <dl class="space-y-4">
        @foreach(\App\Support\SubmissionPolicy::sections() as $heading => $description)
            <div>
                <dt class="font-semibold text-gray-900">{{ $heading }}</dt>
                <dd class="mt-1 leading-relaxed">{{ $description }}</dd>
            </div>
        @endforeach
    </dl>
    <p class="mt-4">
        <a href="{{ route('products.create') }}" class="text-primary-600 underline">Submit a product</a>
        · <a href="{{ url('/get-the-badge') }}" class="text-primary-600 underline">Badge instructions</a>
        · <a href="{{ route('legal') }}" class="text-primary-600 underline">Terms of Use</a>
        · <a href="{{ route('refund-policy') }}" class="text-primary-600 underline">Refund &amp; Cancellation Policy</a>
    </p>
    @if($collapsible)
            </div>
        </details>
    @endif
</section>

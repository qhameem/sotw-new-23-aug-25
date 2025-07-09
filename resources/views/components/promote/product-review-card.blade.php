<div class="border-2 border-gray-300 rounded-lg p-4 text-left flex flex-col h-full">
      <div class="flex flex-row content-center items-center gap-2">
        <h4 class="text-3xl font-semibold">Product Review</h4>
      </div>

      <div class="h-2"></div>

      <div class="font-semibold text-gray-700 tracking-tight">
        We’ll write an in-depth review of your product
      </div>

      <div class="h-3"></div>

      <div class="text-3xl font-bold mb-1">$249</div>

      <ul class="text-sm text-gray-600 text-left mt-4 space-y-1">
        <li>&check; Rank on Google for "[your_product] review"</li>
        <li>&check; Get a backlink</li>
        <li>&check; Gain customer confidence</li>
        <li>&check; Free Skip the Queue included</li>
        <!-- <li>&check; Private feedback from our team</li> -->
        <li>&check; Already 34 reviews sold and published</li>
      </ul>

      <!-- Spacer -->
      <div class="flex-grow"></div>

      <form action="{{ route('stripe.product-review.checkout') }}" method="POST">
        @csrf
        <button type="submit" class="bg-primary-500 hover:bg-rose-600 text-white px-6 py-2 rounded-md font-semibold w-full mt-4">
          Buy a review
        </button>
      </form>
      <a href="{{ route('software-review') }}" class="text-xs text-center italic font text-gray-600 hover:underline mt-2 inline-block">More details</a>
    </div>
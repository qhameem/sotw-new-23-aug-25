<div class="border-2 border-rose-500 rounded-lg p-4 text-left flex flex-col h-full">
      <div class="flex flex-row content-center items-center gap-2">
        <div>
          <h4 class="text-3xl font-semibold">Premium Spot</h4>
        </div>
        <div class="bg-rose-50 border border-rose-300 rounded-md px-3 py-1 text-xs text-rose-500 font-bold">
          @if($spotsAvailable > 0)
            {{ $spotsAvailable }} spot{{ $spotsAvailable > 1 ? 's' : '' }} left
          @else
            No spots left
          @endif
        </div>
      </div>

      <div class="h-2"></div>

      <div class="font-semibold text-gray-700 tracking-tight">
        Promote your product on <i>Software on the web</i>
      </div>

      <div class="h-3"></div>

      <div class="text-3xl font-bold mb-1">
        $149 <span class="opacity-40 font-normal text-sm">/month</span>
      </div>

      <ul class="text-sm text-gray-600 text-left mt-4 space-y-1">
        <li>&check; Shown on every listing page</li>
        <li>&check; Direct link to your website</li>
        <li>&check; Special look and badge</li>
        <li>&check; 20k to 50k impressions</li>
      </ul>

      <!-- Spacer pushes the button to bottom -->
      <div class="flex-grow"></div>

      <a href="{{ route('premium-spot.index') }}" class="px-6 py-2 rounded-md font-semibold w-full mt-4 text-center inline-block
        @if($spotsAvailable > 0)
          bg-primary-500 hover:bg-rose-600 text-white
        @else
          bg-gray-300 text-gray-500 cursor-not-allowed pointer-events-none
        @endif
      ">
        @if($spotsAvailable > 0)
          Book a premium spot
        @else
          Sold out
        @endif
      </a>
      <a href="{{ route('premium-spot.details') }}" class="text-xs text-center italic font text-gray-600 hover:underline mt-2 inline-block">More details</a>
    </div>
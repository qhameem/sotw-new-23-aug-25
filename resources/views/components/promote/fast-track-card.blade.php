<div class="bg-gradient-to-tr from-white to-amber-50 border border-amber-200 rounded-xl p-4 text-left">
  <div class="flex flex-col md:flex-row gap-4">

    <!-- Pricing Box: Hidden on mobile, visible on desktop -->
    <div class="hidden md:flex w-full md:w-1/3 bg-white text-center border border-rose-100 rounded-lg py-3 px-2 flex-col order-1">
      <p class="text-gray-700 text-sm font-semibold mb-3">
        One-time payment. Get discovered. Earn a permanent backlink.
      </p>

      <div class="text-3xl font-extrabold mb-4">$30</div>

      <div class="flex-grow"></div>

      <a href="{{ route('fast-track.index') }}"
        class="bg-primary-500 hover:bg-rose-600 text-white px-6 py-1.5 rounded-md font-semibold w-full mt-4 text-center">
        Skip the Line
      </a>
      <a href="{{ route('fast-track-approval') }}"
        class="text-xs text-center italic text-gray-600 hover:underline mt-2 inline-block">More details</a>
    </div>

    <!-- Main Launch Info: Full width on mobile, right side on desktop -->
    <div class="w-full md:w-2/3 px-3 order-2">
      <h3 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-2">Fast-track your launch</h3>
      <span class="font-semibold text-gray-700 block">
        Launch your product on <i>Software on the web</i> the day you choose—no more waiting.
      </span>

      <!-- Price on mobile -->
      <div class="text-3xl font-extrabold mt-4 mb-3 md:hidden">$30</div>

      <ul class="space-y-3 text-gray-700 text-sm mt-3">
        <li class="flex items-start gap-2">
          <div class="text-primary-500 text-sm font-extrabold">✓</div>
          <div>Your product stays listed on <span class="font-noto-serif italic">Software on the web</span> permanently and earns a lifetime backlink—regardless of how your launch performs.</div>
        </li>
        <li class="flex items-start gap-2">
          <div class="text-primary-500 text-sm font-extrabold">✓</div>
          <div>If you're among the first 3 launches of the day, you’ll earn a special badge and will be featured on our social media.</div>
        </li>
      </ul>

      <!-- Mobile-only button -->
      <div class="md:hidden mt-4">
        <a href="{{ route('fast-track.index') }}"
          class="bg-primary-500 hover:bg-rose-600 text-white px-6 py-2 rounded-md font-semibold w-full block text-center">
          Skip the Line
        </a>
        <a href="{{ route('fast-track-approval') }}"
          class="text-xs text-center italic text-gray-600 hover:underline mt-2 block">More details</a>
      </div>
    </div>

  </div>
</div>

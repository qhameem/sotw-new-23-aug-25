<a href="{{ route('auth.google') }}"
   x-bind:href="'{{ route('auth.google') }}?intended=' + encodeURIComponent(intendedUrl || window.location.href)"
   @click="if (googleSubmitting) { $event.preventDefault(); return; } googleSubmitting = true"
   x-bind:aria-busy="googleSubmitting.toString()"
   x-bind:class="googleSubmitting ? 'pointer-events-none border-blue-300 from-blue-50 to-blue-100/80 text-blue-900 shadow-[0_16px_40px_-28px_rgba(37,99,235,0.9)]' : ''"
   class="flex items-center justify-center w-full px-4 py-3 border border-blue-200 rounded-full text-sm font-semibold tracking-wide text-gray-800 bg-gradient-to-b from-white to-blue-50/70 shadow-[0_12px_30px_-24px_rgba(37,99,235,0.75)] transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:from-white hover:to-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 focus-visible:ring-offset-2">
    <span class="mr-3 flex h-6 w-6 items-center justify-center">
        <img
            x-show="!googleSubmitting"
            class="h-6 w-6"
            src="https://www.svgrepo.com/show/475656/google-color.svg"
            alt="Google logo"
        >
        <span
            x-cloak
            x-show="googleSubmitting"
            class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"
            aria-hidden="true"
        ></span>
    </span>
    <span x-show="!googleSubmitting">Continue with Google</span>
    <span x-cloak x-show="googleSubmitting">Connecting to Google...</span>
</a>

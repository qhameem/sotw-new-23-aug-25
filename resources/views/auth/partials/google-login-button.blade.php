<a href="{{ route('auth.google') }}"
   x-bind:href="'{{ route('auth.google') }}?intended=' + encodeURIComponent(intendedUrl || window.location.href)"
   class="flex items-center justify-center w-full px-4 py-3 border border-blue-200 rounded-full text-sm font-semibold tracking-wide text-gray-800 bg-gradient-to-b from-white to-blue-50/70 shadow-[0_12px_30px_-24px_rgba(37,99,235,0.75)] transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:from-white hover:to-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 focus-visible:ring-offset-2">
    <img class="h-6 w-6 mr-3" src="https://www.svgrepo.com/show/475656/google-color.svg"
         alt="Google logo">
    Continue with Google
</a>

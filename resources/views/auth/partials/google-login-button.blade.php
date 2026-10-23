<a href="{{ route('auth.google') }}"
   x-bind:href="'{{ route('auth.google') }}?intended=' + encodeURIComponent(intendedUrl || window.location.href)"
   @click="if (googleSubmitting) { $event.preventDefault(); return; } googleSubmitting = true"
   x-bind:aria-busy="googleSubmitting.toString()"
   x-bind:class="googleSubmitting ? 'pointer-events-none' : ''"
   class="{{ ($loginPage ?? false) ? 'login-method login-method--google ' . (($primary ?? false) ? 'login-method--primary' : '') : '' }} relative flex items-center justify-center w-full px-4 py-3 border border-gray-300 rounded-lg text-sm font-semibold text-gray-900 shadow-sm transition-all duration-200 hover:bg-gray-100 focus-visible:outline-none">
    @if(! ($loginPage ?? false) && request()->cookie(\App\Support\LastSignInMethod::COOKIE) === \App\Support\LastSignInMethod::GOOGLE)
        <span class="absolute right-3 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-700">Last used</span>
    @endif
    <span class="{{ ($loginPage ?? false) ? 'login-google-icon' : '' }} mr-3 flex h-6 w-6 items-center justify-center">
        <img
            x-show="!googleSubmitting"
            class="h-6 w-6"
            src="{{ asset('images/branding/google-g.svg') }}"
            alt=""
            width="20"
            height="20"
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

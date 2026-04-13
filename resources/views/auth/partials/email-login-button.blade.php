<button
    type="button"
    @click="showEmail = true; $nextTick(() => $refs.emailInput?.focus())"
    class="flex items-center justify-center w-full px-4 py-3 border border-gray-200 rounded-full text-sm font-semibold tracking-wide text-gray-800 bg-white shadow-sm transition-colors hover:bg-gray-50 hover:border-gray-300"
>
    <svg class="h-6 w-6 mr-3 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" />
        <path d="m22 8-8.97 5.7a2 2 0 0 1-2.06 0L2 8" />
    </svg>
    Continue with email
</button>

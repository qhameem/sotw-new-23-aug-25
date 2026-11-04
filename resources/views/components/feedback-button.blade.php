@props(['mobile' => false])

<button type="button" @click="$dispatch('open-modal', { name: 'site-feedback' })"
    aria-haspopup="dialog"
    class="{{ $mobile ? 'flex min-h-11 flex-col items-center justify-center gap-1 text-xs font-medium text-gray-600' : 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap text-sm font-semibold text-gray-900' }} transition-colors hover:text-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9l-6 3V6a2 2 0 0 1 2-2Z" />
    </svg>
    <span>Feedback</span>
</button>

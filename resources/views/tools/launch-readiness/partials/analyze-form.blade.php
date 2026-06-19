@php
    $submittedUrl = $submittedUrl ?? old('url');
@endphp

<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60 sm:p-5">
    <form
        method="POST"
        action="{{ route('launch-readiness.analyze', ['toolSlug' => $toolSlug]) }}"
        class="flex flex-col gap-3 sm:flex-row sm:items-center"
        @submit.prevent="submitAnalyze($event)"
    >
        @csrf
        <div class="min-w-0 flex-1">
            <label for="url" class="sr-only">URL</label>
            <div class="relative flex h-12 items-center rounded-xl border border-slate-200 bg-slate-50 px-4 pr-[9.5rem] transition focus-within:border-[#b17915] focus-within:ring-2 focus-within:ring-[#eb9e2b]/30 sm:pr-[10.5rem]">
                <svg class="mr-3 h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                    <path d="M23.0551 14.2115 29.9971 7.2694c2.6788-2.6788 7.5386-2.1623 10.2172.5164s3.1951 7.5384.5163 10.2172L30.4481 28.2856c-2.6788 2.6788-7.5386 2.1623-10.2172-.5163" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                    <path d="M24.9449 33.7885 18.0029 40.7306c-2.6788 2.6788-7.5386 2.1623-10.2172-.5164S4.5906 32.6758 7.2694 29.997L17.5519 19.7144c2.6788-2.6788 7.5386-2.1623 10.2172.5163" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                </svg>
                <input id="url" name="url" type="text" value="{{ $submittedUrl }}" placeholder="https://your-site.com" autofocus class="h-full w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">

                <div class="absolute inset-y-1.5 right-1.5 flex items-center">
                    <button
                        type="submit"
                        :disabled="submitting"
                        :class="submitting ? 'cursor-wait' : 'hover:-translate-y-0.5 active:translate-y-0.5 active:shadow-none'"
                        class="inline-flex h-9 min-w-[132px] items-center justify-center gap-2 rounded-xl border-2 border-[#b17915] bg-[#eb9e2b] px-5 text-sm font-bold text-black shadow-[0_4px_0_#c77f12,0_8px_14px_rgba(15,23,42,0.14)] transition duration-150 disabled:opacity-100"
                    >
                        <svg
                            x-cloak
                            x-show="submitting"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                        </svg>
                        <span x-text="submitting ? 'Analyzing...' : 'Analyze'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex shrink-0">
            <label class="inline-flex h-12 items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 text-xs text-slate-700" :class="submitting ? 'opacity-60 pointer-events-none' : ''">
                <span>Save to history</span>
                <span class="relative inline-flex items-center">
                    <input type="hidden" name="save_to_history" value="0">
                    <input type="checkbox" name="save_to_history" value="1" checked class="peer sr-only">
                    <span class="h-6 w-12 rounded-md bg-slate-200 transition peer-checked:bg-[#eb9e2b]"></span>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-md bg-white shadow-lg transition peer-checked:translate-x-6"></span>
                </span>
            </label>
        </div>
    </form>
</section>

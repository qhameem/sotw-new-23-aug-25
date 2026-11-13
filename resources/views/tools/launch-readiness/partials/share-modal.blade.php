<div
    x-cloak
    x-show="shareModalOpen"
    x-transition.opacity
    @keydown.escape.window="closeShareModal()"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="launch-readiness-share-title"
>
    <div class="absolute inset-0 bg-slate-900/25 backdrop-blur-[2px]" @click="closeShareModal()"></div>

    <div
        x-show="shareModalOpen"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-y-3 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0"
        class="relative w-full max-w-[510px] max-h-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-[0_24px_60px_-28px_rgba(15,23,42,0.35)]"
    >
        <div class="px-5 pt-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h2 id="launch-readiness-share-title" class="text-xl font-bold text-slate-900">Your scan is ready</h2>
                        <p class="break-all text-sm text-slate-600" x-text="shareTargetName || 'Your site'"></p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="closeShareModal()"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-400 text-slate-500 transition hover:border-slate-500 hover:text-slate-700"
                    aria-label="Close share modal"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M8 8L16 16M16 8L8 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 px-5 py-5 sm:grid-cols-2">
            <button
                type="button"
                @click="viewResultPage()"
                class="min-h-11 rounded-xl border-2 border-[var(--lr-brand-edge)] bg-[var(--lr-brand)] px-3 text-sm font-bold text-[color:var(--lr-button-label)] shadow-[0_3px_0_var(--lr-brand-edge)] transition active:translate-y-0.5 active:shadow-none"
            >
                View report
            </button>
            <button
                type="button"
                @click="copyShareLink()"
                class="min-h-11 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <span x-text="shareLinkCopied ? 'Copied' : 'Copy report link'"></span>
            </button>
            <span class="sr-only" role="status" x-text="shareLinkCopied ? 'Report link copied' : ''"></span>
        </div>

        <div class="mx-5 border-t border-slate-200 pb-5 pt-4">
            <h3 id="launch-readiness-share-networks" class="mb-3 text-sm font-semibold text-slate-900">Share on</h3>
            <div class="grid grid-cols-3 gap-2" role="group" aria-labelledby="launch-readiness-share-networks">
                <button
                    type="button"
                    @click="shareOnX()"
                    class="inline-flex min-h-11 flex-col items-center justify-center gap-1.5 rounded-xl border border-slate-300 px-1 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:flex-row"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M17.53 3H20.6L13.89 10.67L21.79 21H15.6L10.75 14.73L5.26 21H2.18L9.36 12.8L1.79 3H8.13L12.51 8.72L17.53 3ZM16.45 19.08H18.15L7.2 4.82H5.37L16.45 19.08Z" fill="currentColor"></path>
                    </svg>
                    <span class="whitespace-nowrap">X/Twitter</span>
                </button>

                <button
                    type="button"
                    @click="shareOnReddit()"
                    class="inline-flex min-h-11 flex-col items-center justify-center gap-1.5 rounded-xl border border-slate-300 px-1 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:flex-row"
                >
                    <svg class="h-4 w-4" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <path d="M16 2C8.27812 2 2 8.27812 2 16C2 23.7219 8.27812 30 16 30C23.7219 30 30 23.7219 30 16C30 8.27812 23.7219 2 16 2Z" fill="#FC471E"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M20.0193 8.90951C20.0066 8.98984 20 9.07226 20 9.15626C20 10.0043 20.6716 10.6918 21.5 10.6918C22.3284 10.6918 23 10.0043 23 9.15626C23 8.30819 22.3284 7.6207 21.5 7.6207C21.1309 7.6207 20.7929 7.7572 20.5315 7.98359L16.6362 7L15.2283 12.7651C13.3554 12.8913 11.671 13.4719 10.4003 14.3485C10.0395 13.9863 9.54524 13.7629 9 13.7629C7.89543 13.7629 7 14.6796 7 15.8103C7 16.5973 7.43366 17.2805 8.06967 17.6232C8.02372 17.8674 8 18.1166 8 18.3696C8 21.4792 11.5817 24 16 24C20.4183 24 24 21.4792 24 18.3696C24 18.1166 23.9763 17.8674 23.9303 17.6232C24.5663 17.2805 25 16.5973 25 15.8103C25 14.6796 24.1046 13.7629 23 13.7629C22.4548 13.7629 21.9605 13.9863 21.5997 14.3485C20.2153 13.3935 18.3399 12.7897 16.2647 12.7423L17.3638 8.24143L20.0193 8.90951ZM12.5 18.8815C13.3284 18.8815 14 18.194 14 17.3459C14 16.4978 13.3284 15.8103 12.5 15.8103C11.6716 15.8103 11 16.4978 11 17.3459C11 18.194 11.6716 18.8815 12.5 18.8815ZM19.5 18.8815C20.3284 18.8815 21 18.194 21 17.3459C21 16.4978 20.3284 15.8103 19.5 15.8103C18.6716 15.8103 18 16.4978 18 17.3459C18 18.194 18.6716 18.8815 19.5 18.8815ZM12.7773 20.503C12.5476 20.3462 12.2372 20.4097 12.084 20.6449C11.9308 20.8802 11.9929 21.198 12.2226 21.3548C13.3107 22.0973 14.6554 22.4686 16 22.4686C17.3446 22.4686 18.6893 22.0973 19.7773 21.3548C20.0071 21.198 20.0692 20.8802 19.916 20.6449C19.7628 20.4097 19.4524 20.3462 19.2226 20.503C18.3025 21.1309 17.1513 21.4449 16 21.4449C15.3173 21.4449 14.6345 21.3345 14 21.1137C13.5646 20.9621 13.1518 20.7585 12.7773 20.503Z" fill="white"></path>
                    </svg>
                    <span class="whitespace-nowrap">Reddit</span>
                </button>

                <button
                    type="button"
                    @click="shareOnFacebook()"
                    class="inline-flex min-h-11 flex-col items-center justify-center gap-1.5 rounded-xl border border-slate-300 px-1 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:flex-row"
                >
                    <img src="{{ asset('images/tools/launch-readiness/facebook-logo-primary.png') }}" alt="" class="h-4 w-4 object-contain" aria-hidden="true">
                    <span class="whitespace-nowrap">Facebook</span>
                </button>
            </div>
        </div>

    </div>
</div>

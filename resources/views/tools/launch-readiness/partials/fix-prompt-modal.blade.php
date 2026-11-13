<div
    x-cloak
    x-show="fixPromptModalOpen"
    x-transition.opacity
    @keydown.escape.window="closeFixPrompt()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="fix-prompt-title"
>
    <div @click.outside="closeFixPrompt()" class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="fix-prompt-title" class="text-lg font-semibold text-slate-900">Fix issues with an LLM</h2>
                <p class="mt-1 text-sm text-slate-500">Copy this scan-specific prompt into ChatGPT, Claude, Gemini, or another coding assistant.</p>
            </div>
            <button type="button" @click="closeFixPrompt()" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close prompt">&times;</button>
        </div>
        <textarea x-ref="fixPromptTextarea" x-model="fixPrompt" readonly class="mt-5 min-h-72 w-full flex-1 resize-y rounded-xl border border-slate-200 bg-slate-50 p-4 font-mono text-xs leading-5 text-slate-700 focus:border-slate-300 focus:ring-slate-300"></textarea>
        <div class="mt-4 flex justify-end">
            <button type="button" @click="copyFixPrompt()" class="inline-flex h-10 items-center rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800" x-text="fixPromptCopied ? 'Copied' : 'Copy prompt'"></button>
        </div>
    </div>
</div>

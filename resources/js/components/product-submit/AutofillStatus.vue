<template>
  <div class="mt-4">
    <div class="overflow-hidden rounded-lg border border-slate-700 bg-slate-950">
      <div class="border-b border-slate-800 px-3 py-2 font-mono text-[10px] uppercase tracking-wider !text-slate-400" aria-hidden="false">
        <div class="flex items-center justify-between gap-3">
          <span>AI fill</span>
          <button v-if="isLoading && canStop" type="button" class="rounded border border-rose-400/50 px-2 py-1 text-xs normal-case tracking-normal !text-rose-200 hover:bg-rose-400/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-rose-300" @click="$emit('stop')">Stop fetching</button>
        </div>
      </div>
      <div v-if="isLoading" class="space-y-2 px-3 py-3 font-mono text-xs leading-5" role="status" aria-live="polite" aria-atomic="true">
        <div class="flex items-start gap-2">
          <span class="shrink-0 !text-emerald-400" aria-hidden="true">&gt;</span>
          <span class="min-w-0 break-words !text-slate-100"><span class="font-semibold !text-emerald-400">current</span> {{ currentAutofillFields || 'Preparing auto-fill' }}</span>
          <span class="mt-1 h-3 w-1.5 shrink-0 bg-emerald-400 motion-safe:animate-pulse" aria-hidden="true"></span>
        </div>
        <div v-if="nextAutofillFields" class="flex items-start gap-2">
          <span class="shrink-0 !text-slate-400" aria-hidden="true">&gt;</span>
          <span class="min-w-0 break-words !text-slate-400"><span class="font-semibold !text-slate-300">next</span> {{ nextAutofillFields }}</span>
        </div>
      </div>
      <div v-else class="space-y-3 px-3 py-3 font-mono text-xs leading-5" role="status" aria-live="polite">
        <p class="!text-slate-100"><span class="font-semibold !text-emerald-400">&gt; {{ extractionIssues.length ? 'finished with issues' : 'finished' }}</span> {{ aiFilledCount }} out of {{ aiFieldCount }} fields populated. <template v-if="reviewRemaining"> {{ reviewRemaining }} required {{ reviewRemaining === 1 ? 'field needs' : 'fields need' }} your input.</template> Please review the extracted details before submitting.</p>
        <div class="flex items-center justify-end gap-3">
          <span v-if="draftAutosaveState === 'saved'" class="!text-emerald-400">Saved just now</span>
          <button type="button" class="font-semibold !text-sky-400 hover:!text-sky-300" @click="$emit('regenerate')">Regenerate</button>
        </div>
        <div v-if="extractionIssues.length" class="rounded border border-rose-400/30 bg-rose-400/10 p-3">
          <p class="font-semibold !text-rose-200">Extraction issues</p>
          <ul class="mt-1 space-y-1">
            <li v-for="issue in extractionIssues" :key="issue.field" class="!text-rose-100">{{ issue.label }}: {{ issue.message }}</li>
          </ul>
        </div>
        <div v-if="reviewRemaining" class="rounded border border-amber-400/30 bg-amber-400/10 p-3">
          <p class="font-semibold !text-amber-300">Needs your input</p>
          <p class="mt-1 !text-amber-200">{{ reviewFieldsLabel }}.</p>
        </div>
      </div>
      <div
        class="flex items-center gap-3 border-t border-slate-800 bg-slate-900 px-3 py-2 font-mono text-[10px] sm:text-xs"
        role="progressbar"
        :aria-label="isLoading ? 'AI autofill progress' : 'Required input progress'"
        :aria-valuenow="terminalProgress"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-valuetext="`${terminalProgress}% complete`"
      >
        <span class="inline-flex min-w-0 whitespace-nowrap !text-slate-400" aria-hidden="true">
          <span>[</span>
          <span
            v-for="segment in 20"
            :key="segment"
            :class="[segment <= filledCount ? '!text-emerald-400' : '!text-slate-400', { 'terminal-segment-active': isLoading }]"
            :style="{ animationDelay: `${(segment - 1) * 75}ms` }"
          >{{ segment <= filledCount ? '=' : '-' }}</span>
          <span>]</span>
        </span>
        <span v-if="isLoading" class="hidden items-center gap-2 !text-emerald-300 sm:inline-flex" aria-hidden="true">
          <span class="h-3 w-3 rounded-full border-2 border-emerald-400/30 border-t-emerald-300 motion-safe:animate-spin"></span>
          Working
        </span>
        <span class="ml-auto shrink-0 tabular-nums !text-emerald-400" aria-hidden="true">{{ terminalProgress }}%</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isLoading: { type: Boolean, default: false },
  canStop: { type: Boolean, default: false },
  autofillProgress: { type: Number, default: 0 },
  aiFilledCount: { type: Number, default: 0 },
  aiFieldCount: { type: Number, default: 0 },
  reviewRemaining: { type: Number, default: 0 },
  extractionIssues: { type: Array, default: () => [] },
  reviewFieldsLabel: { type: String, default: '' },
  draftAutosaveState: { type: String, default: '' },
  displayProgress: { type: Number, default: 0 },
  currentAutofillFields: { type: String, default: '' },
  nextAutofillFields: { type: String, default: '' },
});
defineEmits(['regenerate', 'stop']);

const terminalProgress = computed(() => Math.max(0, Math.min(100, Math.round(Number(props.isLoading ? props.autofillProgress : props.displayProgress) || 0))));
const filledCount = computed(() => Math.floor(terminalProgress.value / 5));
</script>

<style scoped>
@media (prefers-reduced-motion: no-preference) {
  .terminal-segment-active {
    animation: terminal-scan 1.8s ease-in-out infinite;
  }
}

@keyframes terminal-scan {
  0%, 65%, 100% {
    opacity: 1;
    text-shadow: none;
  }
  30% {
    opacity: 0.45;
    text-shadow: 0 0 6px #34d399;
  }
}
</style>

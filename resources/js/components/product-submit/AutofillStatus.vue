<template>
  <div class="mt-4">
    <div class="overflow-hidden rounded-lg border border-[#cbd5e1] bg-[#fff]">
      <div class="border-b border-[#e2e8f0] px-3 py-2 font-mono text-[10px] uppercase tracking-wider !text-[#64748b]" aria-hidden="false">
        <div class="flex items-center justify-between gap-3">
          <span>AI fill</span>
          <button v-if="isLoading && canStop" type="button" class="rounded border border-rose-500/50 px-2 py-1 text-xs normal-case tracking-normal !text-rose-700 hover:bg-rose-500/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-rose-500" @click="$emit('stop')">Stop fetching</button>
        </div>
      </div>
      <div v-if="isLoading" class="space-y-2 px-3 py-3 font-mono text-xs leading-5" role="status" aria-live="polite" aria-atomic="true">
        <div class="flex items-start gap-2">
          <span class="shrink-0 !text-emerald-700" aria-hidden="true">&gt;</span>
          <span class="min-w-0 break-words !text-[#0f172a]"><span class="font-semibold !text-emerald-700">current</span> {{ currentAutofillFields || 'Preparing auto-fill' }}</span>
          <span class="mt-1 h-3 w-1.5 shrink-0 bg-emerald-600 motion-safe:animate-pulse" aria-hidden="true"></span>
        </div>
        <div v-if="nextAutofillFields" class="flex items-start gap-2">
          <span class="shrink-0 !text-[#64748b]" aria-hidden="true">&gt;</span>
          <span class="min-w-0 break-words !text-[#64748b]"><span class="font-semibold !text-[#334155]">next</span> {{ nextAutofillFields }}</span>
        </div>
      </div>
      <div v-else class="space-y-3 px-3 py-3 font-mono text-xs leading-5" role="status" aria-live="polite">
        <p class="!text-[#0f172a]"><span class="font-semibold !text-emerald-700">&gt; {{ extractionIssues.length ? 'finished with issues' : 'finished' }}</span> {{ aiFilledCount }} out of {{ aiFieldCount }} fields populated. <template v-if="reviewRemaining"> {{ reviewRemaining }} required {{ reviewRemaining === 1 ? 'field needs' : 'fields need' }} your input.</template> Please review the extracted details before submitting.</p>
        <div class="flex items-center justify-end gap-3">
          <span v-if="draftAutosaveState === 'saved'" class="!text-emerald-700">Saved just now</span>
          <button type="button" class="font-semibold !text-sky-700 hover:!text-sky-800" @click="$emit('regenerate')">Regenerate</button>
        </div>
        <div v-if="extractionIssues.length" class="rounded border border-rose-300 bg-rose-50 p-3">
          <p class="font-semibold !text-rose-800">Extraction issues</p>
          <ul class="mt-1 space-y-1">
            <li v-for="issue in extractionIssues" :key="issue.field" class="!text-rose-900">{{ issue.label }}: {{ issue.message }}</li>
          </ul>
        </div>
        <div v-if="reviewRemaining" class="rounded border border-amber-300 bg-amber-50 p-3">
          <p class="font-semibold !text-amber-800">Needs your input</p>
          <p class="mt-1 !text-amber-900">{{ reviewFieldsLabel }}.</p>
        </div>
      </div>
      <div v-if="isAdmin && report.length" class="border-t border-slate-200">
        <div class="flex justify-between px-3 py-2 font-mono text-xs">
          <span>Admin extraction report · {{ report.length }} events</span>
          <button type="button" class="text-sky-700" @click="downloadReport">Download report</button>
        </div>
        <div class="max-h-[640px] min-h-[240px] overflow-y-auto px-3 pb-3 font-mono text-xs leading-5" role="log" aria-live="polite" aria-label="Admin extraction report">
          <div v-for="(entry, index) in report" :key="index" class="break-words whitespace-pre-wrap" :class="entry.level === 'error' ? 'text-rose-700' : entry.level === 'warning' ? 'text-amber-700' : 'text-slate-700'">{{ formatEntry(entry) }}</div>
        </div>
      </div>
      <div
        class="flex items-center gap-3 border-t border-[#e2e8f0] bg-[#fff] px-3 py-2 font-mono text-[10px] sm:text-xs"
        role="progressbar"
        :aria-label="isLoading ? 'AI autofill progress' : 'Required input progress'"
        :aria-valuenow="terminalProgress"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-valuetext="`${terminalProgress}% complete`"
      >
        <span class="inline-flex min-w-0 whitespace-nowrap !text-[#64748b]" aria-hidden="true">
          <span>[</span>
          <span
            v-for="segment in 20"
            :key="segment"
            :class="[segment <= filledCount ? '!text-emerald-700' : '!text-[#64748b]', { 'terminal-segment-active': isLoading }]"
            :style="{ animationDelay: `${(segment - 1) * 75}ms` }"
          >{{ segment <= filledCount ? '=' : '-' }}</span>
          <span>]</span>
        </span>
        <span v-if="isLoading" class="hidden items-center gap-2 !text-emerald-700 sm:inline-flex" aria-hidden="true">
          <span class="h-3 w-3 rounded-full border-2 border-emerald-600/30 border-t-emerald-700 motion-safe:animate-spin"></span>
          Working
        </span>
        <span class="ml-auto shrink-0 tabular-nums !text-emerald-700" aria-hidden="true">{{ terminalProgress }}%</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isAdmin: { type: Boolean, default: false },
  report: { type: Array, default: () => [] },
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

const formatEntry = (entry) => `[${new Date(entry.time).toLocaleTimeString()}] ${entry.level.toUpperCase()} [${entry.stage}] ${entry.message}`;
const downloadReport = () => {
  const url = URL.createObjectURL(new Blob([props.report.map(formatEntry).join('\n')], { type: 'text/plain;charset=utf-8' }));
  const link = document.createElement('a');
  link.href = url;
  link.download = 'product-extraction-report.txt';
  link.click();
  URL.revokeObjectURL(url);
};

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

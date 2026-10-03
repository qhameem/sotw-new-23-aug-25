<template>
  <div class="mt-4">
    <div class="overflow-hidden rounded-lg border border-slate-700 bg-slate-950">
      <div class="border-b border-slate-800 px-3 py-2 font-mono text-[10px] uppercase tracking-wider !text-slate-400" aria-hidden="true">AI fill</div>
      <div v-if="isLoading" class="space-y-2 px-3 py-3 font-mono text-xs leading-5" role="status" aria-live="polite" aria-atomic="true">
        <div class="flex items-start gap-2">
          <span class="shrink-0 text-emerald-400" aria-hidden="true">&gt;</span>
          <span class="min-w-0 break-words text-slate-100"><span class="font-semibold text-emerald-400">current</span> {{ currentAutofillFields || 'Preparing auto-fill' }}</span>
          <span class="mt-1 h-3 w-1.5 shrink-0 bg-emerald-400 motion-safe:animate-pulse" aria-hidden="true"></span>
        </div>
        <div v-if="nextAutofillFields" class="flex items-start gap-2">
          <span class="shrink-0 text-slate-500" aria-hidden="true">&gt;</span>
          <span class="min-w-0 break-words text-slate-400"><span class="font-semibold text-slate-300">next</span> {{ nextAutofillFields }}</span>
        </div>
      </div>
      <div v-else class="space-y-3 px-3 py-3 font-mono text-xs leading-5" role="status" aria-live="polite">
        <p class="text-slate-100"><span class="font-semibold text-emerald-400">&gt; complete</span> AI filled {{ aiFilledCount }} out of {{ aiFieldCount }} fields. {{ reviewRemaining }} need your input. Please review the highlighted fields before submitting.</p>
        <div class="flex items-center justify-end gap-3">
          <span v-if="draftAutosaveState === 'saved'" class="text-emerald-400">Saved just now</span>
          <button type="button" class="font-semibold text-sky-400 hover:text-sky-300" @click="$emit('regenerate')">Regenerate</button>
        </div>
        <div v-if="reviewRemaining" class="rounded border border-amber-400/30 bg-amber-400/10 p-3">
          <p class="font-semibold text-amber-300">Needs your input</p>
          <p class="mt-1 text-amber-200">{{ reviewFieldsLabel }}.</p>
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
        <span class="min-w-0 whitespace-nowrap text-slate-500" aria-hidden="true">[<span class="text-emerald-400">{{ filledSegments }}</span>{{ remainingSegments }}]</span>
        <span class="ml-auto shrink-0 tabular-nums text-emerald-400" aria-hidden="true">{{ terminalProgress }}%</span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isLoading: { type: Boolean, default: false },
  autofillProgress: { type: Number, default: 0 },
  aiFilledCount: { type: Number, default: 0 },
  aiFieldCount: { type: Number, default: 0 },
  reviewRemaining: { type: Number, default: 0 },
  reviewFieldsLabel: { type: String, default: '' },
  draftAutosaveState: { type: String, default: '' },
  displayProgress: { type: Number, default: 0 },
  currentAutofillFields: { type: String, default: '' },
  nextAutofillFields: { type: String, default: '' },
});
defineEmits(['regenerate']);

const terminalProgress = computed(() => Math.max(0, Math.min(100, Math.round(Number(props.isLoading ? props.autofillProgress : props.displayProgress) || 0))));
const filledCount = computed(() => Math.floor(terminalProgress.value / 5));
const filledSegments = computed(() => '='.repeat(filledCount.value));
const remainingSegments = computed(() => '-'.repeat(20 - filledCount.value));
</script>

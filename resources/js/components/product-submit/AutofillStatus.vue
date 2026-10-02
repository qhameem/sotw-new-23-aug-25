<template>
  <div class="mt-4 border-t border-slate-200 pt-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm font-semibold text-slate-900">
        <template v-if="isLoading">AI fill: {{ autofillProgress }}% complete.</template>
        <template v-else>AI filled {{ aiFilledCount }} out of {{ aiFieldCount }} fields. {{ reviewRemaining }} need your input. Please review the highlighted fields before submitting.</template>
      </p>
      <div class="flex items-center gap-3">
        <span v-if="draftAutosaveState === 'saved'" class="text-xs text-emerald-700">Saved just now</span>
        <button type="button" class="text-xs font-semibold text-sky-700 hover:text-sky-900 disabled:opacity-50" @click="$emit('regenerate')" :disabled="isLoading">Regenerate</button>
      </div>
    </div>
    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-label="isLoading ? 'AI autofill progress' : 'Required input progress'" :aria-valuenow="displayProgress" aria-valuemin="0" aria-valuemax="100"><div class="h-full bg-emerald-500 transition-all duration-300" :style="{ width: `${displayProgress}%` }"></div></div>
    <div v-if="isLoading" class="mt-3 overflow-hidden rounded-lg border border-slate-700 bg-slate-950" role="status" aria-live="polite" aria-atomic="true">
      <div class="border-b border-slate-800 px-3 py-2 font-mono text-[10px] uppercase tracking-wider !text-slate-400" aria-hidden="true">AI fill</div>
      <div class="space-y-2 px-3 py-3 font-mono text-xs leading-5">
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
    </div>
    <div v-if="!isLoading && reviewRemaining" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
      <p class="text-xs font-semibold text-amber-900">Needs your input</p>
      <p class="mt-1 text-xs text-amber-800">Pricing model, pricing page URL, hosting provider, domain registrar, and product sale choice.</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isLoading: { type: Boolean, default: false },
  autofillProgress: { type: Number, default: 0 },
  aiFilledCount: { type: Number, default: 0 },
  aiFieldCount: { type: Number, default: 0 },
  reviewRemaining: { type: Number, default: 0 },
  draftAutosaveState: { type: String, default: '' },
  displayProgress: { type: Number, default: 0 },
  currentAutofillFields: { type: String, default: '' },
  nextAutofillFields: { type: String, default: '' },
});
defineEmits(['regenerate']);
</script>

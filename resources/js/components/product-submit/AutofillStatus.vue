<template>
  <div class="mt-4 border-t border-slate-200 pt-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm font-semibold text-slate-900">
        <template v-if="isLoading">AI fill: {{ autofillProgress }}% complete.</template>
        <template v-else>AI filled {{ aiFilledCount }} out of {{ aiFieldCount }} fields. {{ reviewRemaining }} need your input.</template>
      </p>
      <div class="flex items-center gap-3">
        <span v-if="draftAutosaveState === 'saved'" class="text-xs text-emerald-700">Saved just now</span>
        <button type="button" class="text-xs font-semibold text-sky-700 hover:text-sky-900 disabled:opacity-50" @click="$emit('regenerate')" :disabled="isLoading">Regenerate</button>
      </div>
    </div>
    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-label="isLoading ? 'AI autofill progress' : 'Required input progress'" :aria-valuenow="displayProgress" aria-valuemin="0" aria-valuemax="100"><div class="h-full bg-emerald-500 transition-all duration-300" :style="{ width: `${displayProgress}%` }"></div></div>
    <div v-if="isLoading" class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600" aria-live="polite">
      <span class="inline-flex items-center gap-2">
        <span class="font-semibold text-slate-700">Now:</span> {{ currentAutofillFields }}
        <span class="inline-flex items-end gap-0.5" aria-hidden="true">
          <span class="h-1 w-1 animate-bounce rounded-full bg-emerald-500 [animation-delay:-0.3s]"></span>
          <span class="h-1 w-1 animate-bounce rounded-full bg-emerald-500 [animation-delay:-0.15s]"></span>
          <span class="h-1 w-1 animate-bounce rounded-full bg-emerald-500"></span>
        </span>
      </span>
      <span v-if="nextAutofillFields"><span class="font-semibold text-slate-700">Next:</span> {{ nextAutofillFields }}</span>
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

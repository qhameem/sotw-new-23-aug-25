<template>
  <article class="launch-plan flex h-full min-w-0 flex-col rounded-2xl border bg-white p-5" :class="selected ? 'border-primary-400 ring-2 ring-primary-400' : 'border-gray-200'">
    <button type="button" class="flex w-full items-start gap-2 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-4" :aria-pressed="selected" @click="$emit('select')">
      <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path v-if="premium" stroke-linejoin="round" d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z" />
        <template v-else>
          <path stroke-linejoin="round" d="M14 5c3-3 7-2 7-2s1 4-2 7l-7 7-5-5 7-7ZM7 12l-4 1 3-6 5-1M12 17l-1 4 6-3 1-5" />
          <circle cx="16" cy="8" r="1.5" />
          <path stroke-linecap="round" d="M7 17c-2 0-3 1-3 3 2 0 3-1 3-3Z" />
        </template>
      </svg>
      <span class="flex min-w-0 flex-1 items-center gap-1.5">
        <span class="min-w-0 text-base font-semibold leading-6 text-gray-950">{{ premium ? 'Premium launch' : 'Free launch' }}</span>
        <span v-if="premium" class="shrink-0 whitespace-nowrap rounded-md bg-primary-100 px-1.5 py-0.5 text-[10px] font-semibold leading-4 text-primary-700">Recommended</span>
      </span>
      <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2" :class="selected ? 'border-primary-600 bg-primary-600' : 'border-gray-300'" aria-hidden="true">
        <span v-if="selected" class="h-2.5 w-2.5 rounded-full bg-white"></span>
      </span>
    </button>

    <div class="mt-4 flex flex-wrap items-baseline gap-2">
      <span class="text-3xl font-semibold tracking-tight text-gray-950">{{ premium ? priceLabel : '$0' }}</span>
      <span v-if="premium" class="text-sm text-gray-600">per launch, one time</span>
    </div>
    <p class="plan-caption mt-1.5 text-sm text-gray-600">{{ premium ? 'No badge needed' : 'Badge required' }}</p>

    <dl class="mt-4 text-sm">
      <div v-for="row in comparisonRows" :key="row.label" class="flex items-center justify-between gap-4 border-t border-gray-200 py-2.5">
        <dt class="plan-row-label flex items-center gap-1.5 text-gray-600">
          {{ row.label }}
          <span v-if="row.info" :title="row.info" tabindex="0" :aria-label="row.info" class="cursor-help">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10" cy="10" r="8" /><path d="M10 9v5M10 6v1" /></svg>
          </span>
        </dt>
        <dd class="plan-row-value flex items-center gap-1 text-right" :class="row.guaranteed ? 'plan-guaranteed' : row.muted ? 'plan-unavailable' : 'plan-available'">
          <svg v-if="row.guaranteed" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m4 10 4 4 8-8" /></svg>
          {{ row.value }}
        </dd>
      </div>
    </dl>
    <div class="mt-4 flex flex-1 flex-col gap-4"><slot /></div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
  premium: { type: Boolean, default: false },
  selected: { type: Boolean, default: false },
  priceLabel: { type: String, default: '' },
});
defineEmits(['select']);
const comparisonRows = computed(() => [
  { label: 'Launch date', value: props.premium ? 'Date you choose' : 'Open slot, 9 per day' },
  { label: 'Permanent listing', value: props.premium ? 'Guaranteed' : 'Not guaranteed', guaranteed: props.premium, muted: !props.premium },
  { label: 'Do-follow backlink', value: props.premium ? 'Guaranteed' : 'Not guaranteed', guaranteed: props.premium, muted: !props.premium, info: 'A link to your website that search engines can follow.' },
  { label: 'Schedule ahead', value: props.premium ? 'Up to 60 days' : 'Up to 365 days' },
]);
</script>

<style scoped>
.launch-plan .plan-caption,
.launch-plan .plan-row-label {
  color: #525252 !important;
}
.launch-plan .plan-guaranteed {
  color: #15803d !important;
}
.launch-plan .plan-unavailable {
  color: #9ca3af !important;
}
.launch-plan .plan-available {
  color: var(--color-site-text, #111827) !important;
}
</style>

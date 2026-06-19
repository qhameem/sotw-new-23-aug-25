<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/55 p-3 sm:p-4"
      @click.self="emit('close')"
    >
      <div class="flex max-h-[calc(100vh-1.5rem)] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">
        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-4 py-4 sm:px-6">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-500">Free listing</p>
            <h2 class="mt-1 text-xl font-semibold text-gray-900">Add our badge to skip the queue</h2>
            <p class="mt-1 text-sm text-gray-500">Copy the badge, place it on your site, then verify the page.</p>
          </div>
          <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-gray-300 hover:text-gray-700"
            @click="emit('close')"
          >
            <span class="sr-only">Close</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
          <div class="space-y-6">
            <div class="rounded-xl border border-stone-200 bg-stone-50 p-5">
              <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <span
                      class="inline-flex h-9 w-9 items-center justify-center rounded-full"
                      :class="modelValue.badge_verified ? 'bg-primary-500 text-white' : 'bg-white text-primary-500 ring-1 ring-primary-100'"
                    >
                      <svg v-if="modelValue.badge_verified" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                      </svg>
                      <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.59 14.37a6 6 0 00-5.84-5.84m5.84 5.84c-.18.19-.37.37-.59.53v0a6 6 0 01-7.57-7.57h0c.16-.22.34-.41.53-.59m7.63 7.63 2.07 2.07a1 1 0 01-1.41 1.41l-2.07-2.07m1.41-1.41 5.66-5.66a2 2 0 000-2.83l-3.17-3.17a2 2 0 00-2.83 0L9.88 8.46" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15l-4 4m0 0 1.5-4.5M5 19l4.5-1.5" />
                      </svg>
                    </span>
                    <div>
                      <p class="text-sm font-semibold text-gray-900">{{ modelValue.badge_verified ? 'Badge verified' : '3 quick steps' }}</p>
                      <p class="text-xs text-gray-600 sm:text-sm">{{ modelValue.badge_verified ? 'Your listing can move into the badge queue.' : 'Your free listing stays the same, but moves ahead in the queue.' }}</p>
                    </div>
                  </div>
                </div>
                <a
                  href="/get-the-badge"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center text-sm font-semibold text-primary-600 underline decoration-primary-300 underline-offset-4 transition hover:text-primary-700 hover:decoration-primary-500"
                >
                  Badge guide
                </a>
              </div>

              <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-white/70 bg-white/80 p-3">
                  <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Step 1</p>
                  <p class="mt-1 text-xs font-medium text-gray-900 sm:text-sm">Copy the badge code.</p>
                  <p class="mt-1 text-[11px] leading-[1.3] text-gray-500 sm:text-xs">Paste it on any public page.</p>
                </div>
                <div class="rounded-xl border border-white/70 bg-white/80 p-3">
                  <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Step 2</p>
                  <p class="mt-1 text-xs font-medium text-gray-900 sm:text-sm">Enter that page URL.</p>
                  <p class="mt-1 text-[11px] leading-[1.3] text-gray-500 sm:text-xs">Use the full `https://` address.</p>
                </div>
                <div class="rounded-xl border border-white/70 bg-white/80 p-3">
                  <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Step 3</p>
                  <p class="mt-1 text-xs font-medium text-gray-900 sm:text-sm">Verify and pick a week.</p>
                  <p class="mt-1 text-[11px] leading-[1.3] text-gray-500 sm:text-xs">We will use that week for your launch.</p>
                </div>
              </div>
            </div>

            <div v-if="badgeSnippet" class="rounded-xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm font-semibold text-gray-900">Badge code</p>
                  <p class="mt-1 text-sm text-gray-500">Copy this and add it to your website.</p>
                </div>
                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-lg border border-primary-200 bg-transparent px-3 py-1.5 text-xs font-semibold text-primary-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700"
                  @click="emit('copy-badge')"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-8-4h8M8 8V6a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2h-2m-4 0H6a2 2 0 01-2-2V8a2 2 0 012-2h8a2 2 0 012 2v10z" />
                  </svg>
                  <span>{{ hasCopiedBadgeSnippet ? 'Copied' : 'Copy code' }}</span>
                </button>
              </div>
              <pre class="mt-4 overflow-x-auto whitespace-pre-wrap break-all rounded-xl bg-gray-950 px-4 py-3 text-xs text-emerald-100">{{ badgeSnippet }}</pre>
              <div v-if="badgePreviewSrc" class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Badge preview</p>
                <div class="mt-3 flex items-center justify-center rounded-xl bg-stone-50 px-4 py-5">
                  <img
                    :src="badgePreviewSrc"
                    alt="Software on the Web badge preview"
                    class="h-auto max-h-9 w-auto max-w-full object-contain"
                  >
                </div>
              </div>
            </div>

            <div id="field-badge-placement-url" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
              <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <label for="badge-placement-url" class="block text-sm font-semibold text-gray-900">Badge page URL</label>
                  <p class="mt-1 text-xs text-gray-400">This should be the page where the badge is live.</p>
                </div>
              </div>
              <div class="flex flex-col gap-3 sm:flex-row">
                <input
                  id="badge-placement-url"
                  type="url"
                  :value="modelValue.badge_placement_url || ''"
                  placeholder="https://your-site.com/page"
                  class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                  :class="{ '!border-primary-500 !ring-primary-100': validationErrors.badge_placement_url }"
                  @input="emit('update-badge-url', $event.target.value)"
                >
                <button
                  id="badge-verify-button"
                  type="button"
                  :disabled="isVerifyingBadge || !badgePlacementUrlReady"
                  :class="{
                    'cursor-wait': isVerifyingBadge,
                    'cursor-not-allowed opacity-50': !badgePlacementUrlReady && !isVerifyingBadge,
                    'hover:bg-primary-600': badgePlacementUrlReady && !isVerifyingBadge
                  }"
                  class="relative inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl bg-primary-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                  @click="emit('verify-badge')"
                >
                  <span
                    class="whitespace-nowrap transition-opacity duration-150"
                    :class="isVerifyingBadge ? 'opacity-0' : 'opacity-100'"
                  >
                    Verify badge
                  </span>
                  <span
                    v-if="isVerifyingBadge"
                    class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current"
                    aria-live="polite"
                  >
                    <span class="flex items-center gap-1.5" aria-hidden="true">
                      <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.3s]"></span>
                      <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.15s]"></span>
                      <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                    </span>
                    <span>Verifying</span>
                  </span>
                </button>
              </div>
            </div>

            <div
              v-if="badgeVerificationMessage"
              id="field-badge-verified"
              :class="badgeVerificationSuccess ? 'border-primary-200 bg-primary-50 text-primary-700' : 'border-amber-200 bg-amber-50 text-amber-800'"
              class="rounded-xl border px-4 py-3 text-sm"
            >
              <div class="flex items-center gap-3">
                <span
                  class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                  :class="badgeVerificationSuccess ? 'bg-primary-500 text-white' : 'bg-amber-100 text-amber-700'"
                >
                  <svg v-if="badgeVerificationSuccess" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3h.008v.008H12v-.008z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.29 3.86 1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                  </svg>
                </span>
                <p>{{ badgeVerificationMessage }}</p>
              </div>
            </div>

            <div id="field-badge-week-start" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
              <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <label for="badge-week-start" class="block text-sm font-semibold text-gray-900">Launch week</label>
                  <p class="mt-1 text-sm text-gray-500">Pick the week you want us to use.</p>
                </div>
                <p
                  v-if="validationErrors.badge_week_start"
                  class="inline-flex max-w-xs items-center rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-[11px] font-medium text-amber-800 shadow-sm"
                >
                  {{ validationErrors.badge_week_start }}
                </p>
              </div>
              <select
                id="badge-week-start"
                :value="modelValue.badge_week_start || ''"
                :disabled="!modelValue.badge_verified"
                style="color-scheme: light;"
                class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                @change="emit('update-badge-week-start', $event.target.value)"
              >
                <option value="">Select a week</option>
                <option v-for="week in launchWeekOptions" :key="week.value" :value="week.value">
                  {{ week.label }}
                </option>
              </select>
            </div>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  badgeSnippet: {
    type: String,
    default: '',
  },
  hasCopiedBadgeSnippet: {
    type: Boolean,
    default: false,
  },
  badgeVerificationMessage: {
    type: String,
    default: '',
  },
  badgeVerificationSuccess: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: Object,
    required: true,
  },
  validationErrors: {
    type: Object,
    default: () => ({}),
  },
  launchWeekOptions: {
    type: Array,
    default: () => [],
  },
  isVerifyingBadge: {
    type: Boolean,
    default: false,
  },
  badgePlacementUrlReady: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'close',
  'copy-badge',
  'update-badge-url',
  'verify-badge',
  'update-badge-week-start',
  'reset-badge-flow',
]);

const badgePreviewSrc = computed(() => {
  const match = props.badgeSnippet.match(/<img[^>]+src=["']([^"']+)["']/i);
  return match?.[1] || '';
});

const handleEscape = (event) => {
  if (event.key === 'Escape') {
    emit('close');
  }
};

watch(
  () => props.show,
  (isOpen) => {
    document.body.classList.toggle('overflow-hidden', isOpen);

    if (isOpen) {
      window.addEventListener('keydown', handleEscape);
    } else {
      window.removeEventListener('keydown', handleEscape);
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-hidden');
  window.removeEventListener('keydown', handleEscape);
});
</script>

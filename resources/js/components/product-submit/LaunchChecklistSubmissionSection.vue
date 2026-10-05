<template>
  <section>
    <div v-if="!!modelValue.id && !isAdmin" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
      <h3 class="mb-2 text-lg font-semibold text-gray-700">Save Changes</h3>
      <p class="mb-6 text-sm text-gray-600">You can save your edits directly without selecting a pricing option.</p>
      <div class="flex flex-col items-start gap-4">
        <div v-if="!isAllRequiredFilled" class="text-sm font-medium text-amber-600">
          Note: Some required fields are missing, but you can still save.
        </div>
        <button
          type="button"
          :disabled="isLoading"
          :class="{
            'cursor-wait': isLoading,
            'hover:bg-rose-700': !isLoading,
          }"
          class="relative inline-flex min-h-12 items-center justify-center rounded-lg bg-rose-600 px-8 py-3 text-sm font-bold text-white shadow-md transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
          @click="$emit('submit')"
        >
          <span class="whitespace-nowrap transition-opacity duration-150" :class="isLoading ? 'opacity-0' : 'opacity-100'">
            Save All Changes
          </span>
          <span v-if="isLoading" class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current" aria-live="polite">
            <span class="flex items-center gap-1.5" aria-hidden="true">
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.3s]"></span>
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.15s]"></span>
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current"></span>
            </span>
            <span>Saving</span>
          </span>
        </button>
      </div>
    </div>

    <div v-else-if="!isAdmin">
      <h3 class="mb-2 text-lg font-semibold text-gray-700">Submission</h3>
      <p class="mb-4 text-sm text-gray-600">Choose launch type</p>
      <div v-if="progress.completed < progress.total" class="mb-4 text-xs font-semibold text-gray-400 transition-all duration-300">
        {{ progress.completed }} of {{ progress.total }} total required fields filled
      </div>

      <div>
        <div class="grid items-stretch gap-6 lg:grid-cols-2">
          <LaunchPlanCard :selected="selectedSubmissionCard === 'free'" @select="$emit('select-free-submission')">
            <div class="space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-gray-900">Add our badge to launch free</p>
                <span class="rounded-md bg-primary-100 px-2 py-0.5 text-[11px] font-medium uppercase tracking-wide text-primary-700">Required</span>
              </div>
              <p class="text-xs text-gray-500">Place it on a public page of your site, then verify.</p>
              <FreeLaunchBadgePreview :snippet="badgeSnippet">
                <div id="field-badge-placement-url" class="space-y-2 pt-1">
                  <label for="inline-badge-placement-url" class="flex items-center gap-2 text-xs font-medium text-gray-600"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 text-primary-700">2</span> Verify placement</label>
                  <div class="flex gap-2">
                    <input id="inline-badge-placement-url" type="url" :value="modelValue.badge_placement_url || ''" placeholder="https://yoursite.com/page" class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 !text-xs focus:border-primary-500 focus:ring-primary-500" :aria-invalid="Boolean(validationErrors.badge_placement_url)" aria-describedby="field-badge-verified" :disabled="isVerifyingBadge || isLoading" @input="$emit('update-badge-url', $event.target.value)">
                    <button type="button" :disabled="isVerifyingBadge || isLoading || !badgePlacementUrlReady" :aria-busy="isVerifyingBadge" class="shrink-0 rounded-lg border border-primary-300 bg-primary-50 px-3 py-2 text-xs font-medium text-primary-700 focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50" @click="$emit('verify-badge')">
                      {{ isVerifyingBadge ? 'Checking…' : modelValue.badge_verified ? 'Verified' : badgeVerificationMessage ? 'Check again' : 'Verify' }}
                    </button>
                  </div>
                  <div id="field-badge-verified" role="status" aria-live="polite" class="text-xs leading-5" :class="modelValue.badge_verified ? '!text-green-700' : badgeVerificationMessage && !isVerifyingBadge ? '!text-red-600' : '!text-gray-500'">
                    {{ isVerifyingBadge ? 'Scanning your page for the badge link…' : badgeVerificationMessage || 'We scan this page for the badge link.' }}
                    <ul v-if="badgeVerificationMessage && !modelValue.badge_verified && !isVerifyingBadge" class="mt-1 list-disc space-y-1 pl-4 text-gray-500">
                      <li>Place the badge on the exact page entered above.</li>
                      <li>Make sure the page is public and accessible.</li>
                      <li>Clear your site cache, then check again.</li>
                    </ul>
                  </div>
                  <p v-if="validationErrors.badge_placement_url" class="text-xs !text-red-600">{{ validationErrors.badge_placement_url }}</p>
                </div>
              </FreeLaunchBadgePreview>
              <div v-if="modelValue.badge_verified" id="field-badge-week-start" class="space-y-2">
                <label for="inline-badge-launch-date" class="block text-xs font-medium text-gray-600">Pick your launch date</label>
                <select id="inline-badge-launch-date" :value="modelValue.badge_week_start || ''" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 !text-xs focus:border-primary-500 focus:ring-primary-500" :disabled="isLoading" @change="$emit('update-badge-week-start', $event.target.value)">
                  <option value="" disabled>Choose a launch date</option>
                  <option v-for="option in launchWeekOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <p v-if="validationErrors.badge_week_start" class="text-xs !text-red-600">{{ validationErrors.badge_week_start }}</p>
              </div>
            </div>
            <div v-if="badgeStatusMessage || modelValue.badge_verified || selectedLaunchWeekLabel" class="space-y-2">
              <p v-if="modelValue.badge_verified" class="text-xs font-medium text-green-700">Badge verified</p>
              <p v-if="selectedLaunchWeekLabel" class="text-xs text-gray-600">{{ selectedLaunchWeekLabel }}</p>
              <p v-if="badgeStatusMessage" class="text-xs" :class="badgeStatusTone">{{ badgeStatusMessage }}</p>
            </div>
            <button type="button" class="launch-card-action mt-auto" :disabled="cardButtonDisabled || modelValue.badge_verified !== true || !modelValue.badge_week_start || isVerifyingBadge || isLoading" @click="$emit('submit-free-card')">
              {{ isLoading ? freeButtonLabel : !modelValue.badge_verified ? 'Verify badge to continue' : !modelValue.badge_week_start ? 'Choose a launch date to continue' : 'Start free launch' }}
            </button>
          </LaunchPlanCard>

          <LaunchPlanCard premium :price-label="premiumLaunchPriceLabel" :selected="selectedSubmissionCard === 'paid'" @select="$emit('select-paid-submission')">
            <div class="mt-auto">
            <LaunchChecklistSchedulePicker
              compact
              dropdown-id="paid-schedule-date"
              :dropdown-ref="paidScheduleDropdownRef"
              :selected-value="selectedPaidScheduleDate"
              :selected-option="selectedPaidScheduleOption"
              :options="paidScheduleOptions"
              :is-open="isPaidScheduleDropdownOpen"
              :scheduled-date-label="paidScheduleMessageDateLabel"
              :publish-time-label="publishTimeLabel"
              :error="validationErrors.paid_schedule_date"
              :action-label="isLoading ? premiumButtonLabel : 'Get premium launch'"
              :action-state="premiumButtonState"
              :action-disabled="cardButtonDisabled"
              variant="paid"
              @toggle="activatePaidSchedule('toggle-paid-schedule-dropdown')"
              @open="activatePaidSchedule('open-paid-schedule-dropdown')"
              @close="$emit('close-paid-schedule-dropdown')"
              @select="$emit('select-paid-schedule-option', $event)"
              @submit="$emit('submit-paid-card')"
            />
            </div>
          </LaunchPlanCard>
        </div>

        <div class="mt-6 flex flex-col items-start gap-4">
          <LaunchChecklistValidationSummary
            :validation-summary="validationSummary"
            :general-error-message="generalErrorMessage"
            @focus-field="$emit('focus-validation-field', $event)"
          />
          <div v-if="!isAllRequiredFilled" class="text-sm font-medium text-amber-600">
            Fill all required fields before submitting.
          </div>
          <div v-else-if="selectedSubmissionCard === 'free' && wantsBadgeLaunch && (!modelValue.badge_verified || !modelValue.badge_week_start)" class="text-sm font-medium text-amber-600">
            Finish badge setup to unlock the free launch.
          </div>
        </div>
      </div>

      <BadgeLaunchModal
        :show="isBadgeModalOpen"
        :badge-snippet="badgeSnippet"
        :has-copied-badge-snippet="hasCopiedBadgeSnippet"
        :badge-verification-message="badgeVerificationMessage"
        :badge-verification-success="badgeVerificationSuccess"
        :model-value="modelValue"
        :validation-errors="validationErrors"
        :launch-week-options="launchWeekOptions"
        :is-verifying-badge="isVerifyingBadge"
        :badge-placement-url-ready="badgePlacementUrlReady"
        @close="$emit('close-badge-modal')"
        @copy-badge="$emit('copy-badge')"
        @update-badge-url="$emit('update-badge-url', $event)"
        @verify-badge="$emit('verify-badge')"
        @update-badge-week-start="$emit('update-badge-week-start', $event)"
        @reset-badge-flow="$emit('reset-badge-flow')"
      />
    </div>

    <div v-else-if="!!modelValue.id" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
      <h3 class="mb-2 text-lg font-semibold text-gray-700">Admin Controls</h3>
      <p class="mb-6 text-sm text-gray-600">{{ adminDescription }}</p>

      <div class="mb-6 space-y-4">
        <div>
          <label for="comparison-overrides" class="mb-1 block text-sm font-semibold text-gray-700">Curated Comparisons</label>
          <textarea
            id="comparison-overrides"
            :value="modelValue.comparison_overrides_input || ''"
            rows="3"
            placeholder="Comma or newline separated product IDs or slugs (e.g. 12, ai-agent-flow, another-product)"
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-600 shadow-sm placeholder-gray-400 focus:border-sky-400 focus:outline-none focus:ring-sky-400 sm:text-sm"
            @input="emitFieldUpdate('comparison_overrides_input', $event.target.value)"
          ></textarea>
          <p class="mt-1 text-xs text-gray-500">These are shown first in the sidebar "Compare with" section.</p>
        </div>

        <div>
          <label for="alternative-overrides" class="mb-1 block text-sm font-semibold text-gray-700">Curated Alternatives</label>
          <textarea
            id="alternative-overrides"
            :value="modelValue.alternative_overrides_input || ''"
            rows="3"
            placeholder="Comma or newline separated product IDs or slugs"
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-600 shadow-sm placeholder-gray-400 focus:border-sky-400 focus:outline-none focus:ring-sky-400 sm:text-sm"
            @input="emitFieldUpdate('alternative_overrides_input', $event.target.value)"
          ></textarea>
          <p class="mt-1 text-xs text-gray-500">These are shown first on the alternatives page.</p>
        </div>
      </div>

      <div class="flex flex-col items-start gap-4">
        <LaunchChecklistValidationSummary
          :validation-summary="validationSummary"
          :general-error-message="generalErrorMessage"
          @focus-field="$emit('focus-validation-field', $event)"
        />
        <div v-if="isSandboxAvailable && modelValue.sandbox_mode" class="text-sm font-medium text-amber-700">
          Sandbox mode ignores all required fields and keeps this run out of the database.
        </div>
        <div v-else-if="!isAllRequiredFilled" class="text-sm font-medium text-amber-600">
          Note: Some required fields are missing, but you can still save as admin.
        </div>

        <AnimatedSubmitButton
          v-if="isSandboxAvailable && modelValue.sandbox_mode"
          :label="adminActionLabel"
          :state="submitButtonVisualState"
          :disabled="isLoading"
          @click="$emit('admin-submit')"
        />
        <button
          v-else
          type="button"
          :disabled="isLoading"
          :class="{
            'cursor-wait': isLoading,
            'hover:bg-rose-700': !isLoading,
          }"
          class="relative inline-flex min-h-12 items-center justify-center rounded-lg bg-rose-600 px-8 py-3 text-sm font-bold text-white shadow-md transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
          @click="$emit('admin-submit')"
        >
          <span class="whitespace-nowrap transition-opacity duration-150" :class="isLoading ? 'opacity-0' : 'opacity-100'">
            Save All Changes
          </span>
          <span v-if="isLoading" class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current" aria-live="polite">
            <span class="flex items-center gap-1.5" aria-hidden="true">
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.3s]"></span>
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.15s]"></span>
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current"></span>
            </span>
            <span>Saving</span>
          </span>
        </button>
      </div>
    </div>

    <div v-else class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
      <h3 class="mb-2 text-lg font-semibold text-gray-700">Admin Submission</h3>
      <p class="mb-6 text-sm text-gray-600">{{ adminCreateDescription }}</p>

      <div class="flex flex-col items-start gap-4">
        <LaunchChecklistValidationSummary
          :validation-summary="validationSummary"
          :general-error-message="generalErrorMessage"
          @focus-field="$emit('focus-validation-field', $event)"
        />
        <div v-if="isSandboxAvailable && modelValue.sandbox_mode" class="text-sm font-medium text-amber-700">
          Sandbox mode is active, so this button will simulate submission without saving anything.
        </div>
        <div v-else-if="!isAllRequiredFilled" class="text-sm font-medium text-amber-600">
          Fill the required fields to submit this product.
        </div>

        <AnimatedSubmitButton
          v-if="isSandboxAvailable && modelValue.sandbox_mode"
          :label="adminCreateActionLabel"
          :state="submitButtonVisualState"
          :disabled="isLoading"
          @click="$emit('admin-submit')"
        />
        <button
          v-else
          type="button"
          :disabled="isLoading"
          :class="{
            'cursor-wait': isLoading,
            'hover:bg-primary-600': !isLoading,
          }"
          class="relative inline-flex min-h-12 items-center justify-center rounded-lg bg-primary-500 px-8 py-3 text-sm font-bold text-white shadow-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          @click="$emit('admin-submit')"
        >
          <span class="whitespace-nowrap transition-opacity duration-150" :class="isLoading ? 'opacity-0' : 'opacity-100'">
            Submit Product
          </span>
          <span v-if="isLoading" class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current" aria-live="polite">
            <span class="flex items-center gap-1.5" aria-hidden="true">
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.3s]"></span>
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.15s]"></span>
              <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current"></span>
            </span>
            <span>Submitting</span>
          </span>
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import AnimatedSubmitButton from './AnimatedSubmitButton.vue';
import BadgeLaunchModal from './BadgeLaunchModal.vue';
import FreeLaunchBadgePreview from './FreeLaunchBadgePreview.vue';
import LaunchPlanCard from './LaunchPlanCard.vue';
import LaunchChecklistSchedulePicker from './LaunchChecklistSchedulePicker.vue';
import LaunchChecklistValidationSummary from './LaunchChecklistValidationSummary.vue';

const props = defineProps({
  adminActionLabel: {
    type: String,
    default: '',
  },
  adminCreateActionLabel: {
    type: String,
    default: '',
  },
  adminCreateDescription: {
    type: String,
    default: '',
  },
  adminDescription: {
    type: String,
    default: '',
  },
  badgeActionLabel: {
    type: String,
    default: '',
  },
  badgePageHost: {
    type: String,
    default: '',
  },
  badgePlacementUrlReady: {
    type: Boolean,
    default: false,
  },
  badgeSnippet: {
    type: String,
    default: '',
  },
  badgeStatusMessage: {
    type: String,
    default: '',
  },
  badgeStatusTone: {
    type: String,
    default: '',
  },
  badgeVerificationMessage: {
    type: String,
    default: '',
  },
  badgeVerificationSuccess: {
    type: Boolean,
    default: false,
  },
  cardButtonDisabled: {
    type: Boolean,
    default: false,
  },
  freeButtonLabel: {
    type: String,
    default: '',
  },
  freeButtonState: {
    type: String,
    default: 'idle',
  },
  freeLaunchFeatures: {
    type: Array,
    default: () => [],
  },
  freeLaunchQueueHelperText: {
    type: String,
    default: '',
  },
  freeScheduleDropdownRef: {
    type: Object,
    default: null,
  },
  freeScheduleMessageDateLabel: {
    type: String,
    default: '',
  },
  freeScheduleOptions: {
    type: Array,
    default: () => [],
  },
  generalErrorMessage: {
    type: String,
    default: '',
  },
  hasCopiedBadgeSnippet: {
    type: Boolean,
    default: false,
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
  isAllRequiredFilled: {
    type: Boolean,
    default: false,
  },
  isBadgeModalOpen: {
    type: Boolean,
    default: false,
  },
  isFreeScheduleDropdownOpen: {
    type: Boolean,
    default: false,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  isPaidScheduleDropdownOpen: {
    type: Boolean,
    default: false,
  },
  isSandboxAvailable: {
    type: Boolean,
    default: false,
  },
  isVerifyingBadge: {
    type: Boolean,
    default: false,
  },
  launchWeekOptions: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Object,
    required: true,
  },
  paidLaunchFeatures: {
    type: Array,
    default: () => [],
  },
  paidScheduleDropdownRef: {
    type: Object,
    default: null,
  },
  paidScheduleMessageDateLabel: {
    type: String,
    default: '',
  },
  paidScheduleOptions: {
    type: Array,
    default: () => [],
  },
  premiumButtonLabel: {
    type: String,
    default: '',
  },
  premiumButtonState: {
    type: String,
    default: 'idle',
  },
  premiumLaunchPriceLabel: {
    type: String,
    default: '',
  },
  progress: {
    type: Object,
    required: true,
  },
  publishTimeLabel: {
    type: String,
    default: '',
  },
  selectedFreeScheduleDate: {
    type: String,
    default: '',
  },
  selectedFreeScheduleOption: {
    type: Object,
    required: true,
  },
  selectedLaunchWeekLabel: {
    type: String,
    default: '',
  },
  selectedPaidScheduleDate: {
    type: String,
    default: '',
  },
  selectedPaidScheduleOption: {
    type: Object,
    required: true,
  },
  selectedSubmissionCard: {
    type: String,
    default: 'free',
  },
  submitButtonVisualState: {
    type: String,
    default: 'idle',
  },
  validationErrors: {
    type: Object,
    default: () => ({}),
  },
  validationSummary: {
    type: Array,
    default: () => [],
  },
  wantsBadgeLaunch: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'admin-submit',
  'close-badge-modal',
  'close-free-schedule-dropdown',
  'close-paid-schedule-dropdown',
  'copy-badge',
  'focus-validation-field',
  'open-badge-modal',
  'open-free-schedule-dropdown',
  'open-paid-schedule-dropdown',
  'reset-badge-flow',
  'select-free-schedule-option',
  'select-free-submission',
  'select-paid-schedule-option',
  'select-paid-submission',
  'submit',
  'submit-free-card',
  'submit-paid-card',
  'toggle-free-schedule-dropdown',
  'toggle-paid-schedule-dropdown',
  'update-badge-url',
  'update-badge-week-start',
  'update:modelValue',
  'verify-badge',
]);

const activatePaidSchedule = (event) => {
  if (props.selectedSubmissionCard !== 'paid') emit('select-paid-submission');
  emit(event);
};

const emitFieldUpdate = (field, value) => {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
};
</script>

<style scoped>
.launch-card-action {
  width: 100%;
  min-height: 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.75rem;
  background: white;
  padding: 0.5rem 1rem;
  color: var(--color-site-text, #111827);
  font-size: 0.875rem;
  font-weight: 500;
}
.launch-card-action:hover:not(:disabled) {
  border-color: var(--color-primary-400, #60a5fa);
  background: var(--color-primary-50, #eff6ff);
}
.launch-card-action:focus-visible {
  outline: 2px solid var(--color-primary-500, #3b82f6);
  outline-offset: 2px;
}
.launch-card-action:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>

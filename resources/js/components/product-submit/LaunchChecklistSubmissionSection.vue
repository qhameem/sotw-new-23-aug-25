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

      <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="grid items-stretch gap-6 lg:grid-cols-2">
          <FreeSubmissionCard
            :features="freeLaunchFeatures"
            :selected="selectedSubmissionCard === 'free'"
            @select="$emit('select-free-submission')"
            @open-badge-modal="$emit('open-badge-modal')"
          />

          <PaidSubmissionCard
            :features="paidLaunchFeatures"
            :price-label="premiumLaunchPriceLabel"
            :selected="selectedSubmissionCard === 'paid'"
            @select="$emit('select-paid-submission')"
          />
        </div>

        <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50/70 p-4 lg:min-h-[232px]">
          <div v-if="selectedSubmissionCard === 'paid'" class="flex flex-col gap-4">
            <div class="space-y-1">
              <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                <p class="text-sm font-medium text-gray-900"><span class="text-primary-700">[Premium]</span> Choose the launch date</p>
                <p class="text-[11px] text-gray-500">Default: next Monday. Maximum: 60 days ahead.</p>
              </div>
            </div>

            <LaunchChecklistSchedulePicker
              dropdown-id="paid-schedule-date"
              :dropdown-ref="paidScheduleDropdownRef"
              :selected-value="selectedPaidScheduleDate"
              :selected-option="selectedPaidScheduleOption"
              :options="paidScheduleOptions"
              :is-open="isPaidScheduleDropdownOpen"
              :scheduled-date-label="paidScheduleMessageDateLabel"
              :publish-time-label="publishTimeLabel"
              :error="validationErrors.paid_schedule_date"
              :action-label="premiumButtonLabel"
              :action-state="premiumButtonState"
              :action-disabled="cardButtonDisabled"
              variant="paid"
              @toggle="$emit('toggle-paid-schedule-dropdown')"
              @open="$emit('open-paid-schedule-dropdown')"
              @close="$emit('close-paid-schedule-dropdown')"
              @select="$emit('select-paid-schedule-option', $event)"
              @submit="$emit('submit-paid-card')"
            />
          </div>

          <div v-else class="flex flex-col gap-4">
            <div class="space-y-2">
              <div class="space-y-1">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                  <p class="text-sm font-medium text-gray-900"><span class="text-primary-700">[Free]</span> Choose the launch date</p>
                  <p class="text-[11px] text-gray-500">{{ freeLaunchQueueHelperText }}</p>
                  <button
                    type="button"
                    class="text-[11px] font-medium text-primary-700 underline underline-offset-4 transition hover:text-primary-800"
                    @click="$emit('open-badge-modal')"
                  >
                    {{ badgeActionLabel }}
                  </button>
                </div>
              </div>

              <div v-if="wantsBadgeLaunch || badgeStatusMessage || selectedLaunchWeekLabel" class="flex flex-wrap gap-2">
                <span
                  v-if="wantsBadgeLaunch"
                  class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium"
                  :class="modelValue.badge_verified ? 'ring-1 ring-primary-200 bg-primary-50 text-primary-700' : 'ring-1 ring-amber-200 bg-amber-50 text-amber-800'"
                >
                  {{ modelValue.badge_verified ? 'Badge verified' : 'Badge setup in progress' }}
                </span>
                <span
                  v-if="selectedLaunchWeekLabel"
                  class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-gray-700 ring-1 ring-gray-200"
                >
                  {{ selectedLaunchWeekLabel }}
                </span>
                <span
                  v-if="badgePageHost"
                  class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-gray-600 ring-1 ring-gray-200"
                >
                  {{ badgePageHost }}
                </span>
              </div>

              <p v-if="badgeStatusMessage" class="text-[11px]" :class="badgeStatusTone">
                {{ badgeStatusMessage }}
              </p>
            </div>

            <LaunchChecklistSchedulePicker
              dropdown-id="free-schedule-date"
              :dropdown-ref="freeScheduleDropdownRef"
              :selected-value="selectedFreeScheduleDate"
              :selected-option="selectedFreeScheduleOption"
              :options="freeScheduleOptions"
              :is-open="isFreeScheduleDropdownOpen"
              :scheduled-date-label="freeScheduleMessageDateLabel"
              :publish-time-label="publishTimeLabel"
              :action-label="freeButtonLabel"
              :action-state="freeButtonState"
              :action-disabled="cardButtonDisabled"
              variant="free"
              @toggle="$emit('toggle-free-schedule-dropdown')"
              @open="$emit('open-free-schedule-dropdown')"
              @close="$emit('close-free-schedule-dropdown')"
              @select="$emit('select-free-schedule-option', $event)"
              @submit="$emit('submit-free-card')"
            />
          </div>
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
            Finish badge setup to skip the queue.
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
import FreeSubmissionCard from './FreeSubmissionCard.vue';
import LaunchChecklistSchedulePicker from './LaunchChecklistSchedulePicker.vue';
import LaunchChecklistValidationSummary from './LaunchChecklistValidationSummary.vue';
import PaidSubmissionCard from './PaidSubmissionCard.vue';

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

const emitFieldUpdate = (field, value) => {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
};
</script>

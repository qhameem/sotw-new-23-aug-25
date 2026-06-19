<template>
  <div class="space-y-6">
    <div class="mb-2"></div>

    <LaunchChecklistExtrasSection
      :model-value="modelValue"
      :all-tech-stacks="allTechStacks"
      @update:model-value="emit('update:modelValue', $event)"
    />

    <hr class="my-6 border-t border-gray-200">

    <LaunchChecklistSubmissionSection
      :model-value="modelValue"
      :is-admin="isAdmin"
      :is-loading="isLoading"
      :progress="progress"
      :is-all-required-filled="isAllRequiredFilled"
      :selected-submission-card="selectedSubmissionCard"
      :free-launch-features="freeLaunchFeatures"
      :paid-launch-features="paidLaunchFeatures"
      :premium-launch-price-label="premiumLaunchPriceLabel"
      :free-launch-queue-helper-text="freeLaunchQueueHelperText"
      :wants-badge-launch="wantsBadgeLaunch"
      :badge-status-message="badgeStatusMessage"
      :badge-status-tone="badgeStatusTone"
      :badge-action-label="badgeActionLabel"
      :badge-page-host="badgePageHost"
      :selected-launch-week-label="selectedLaunchWeekLabel"
      :selected-free-schedule-option="selectedFreeScheduleOption"
      :selected-free-schedule-date="selectedFreeScheduleDate"
      :free-schedule-options="freeScheduleOptions"
      :is-free-schedule-dropdown-open="isFreeScheduleDropdownOpen"
      :free-schedule-dropdown-ref="freeScheduleDropdownRef"
      :free-schedule-message-date-label="freeScheduleMessageDateLabel"
      :selected-paid-schedule-option="selectedPaidScheduleOption"
      :selected-paid-schedule-date="selectedPaidScheduleDate"
      :paid-schedule-options="paidScheduleOptions"
      :is-paid-schedule-dropdown-open="isPaidScheduleDropdownOpen"
      :paid-schedule-dropdown-ref="paidScheduleDropdownRef"
      :paid-schedule-message-date-label="paidScheduleMessageDateLabel"
      :publish-time-label="publishTimeLabel"
      :validation-errors="validationErrors"
      :validation-summary="validationSummary"
      :general-error-message="generalErrorMessage"
      :free-button-label="freeButtonLabel"
      :free-button-state="freeButtonState"
      :premium-button-label="premiumButtonLabel"
      :premium-button-state="premiumButtonState"
      :card-button-disabled="cardButtonDisabled"
      :is-badge-modal-open="isBadgeModalOpen"
      :badge-snippet="badgeSnippet"
      :has-copied-badge-snippet="hasCopiedBadgeSnippet"
      :badge-verification-message="badgeVerificationMessage"
      :badge-verification-success="badgeVerificationSuccess"
      :launch-week-options="launchWeekOptions"
      :is-verifying-badge="isVerifyingBadge"
      :badge-placement-url-ready="badgePlacementUrlReady"
      :is-sandbox-available="isSandboxAvailable"
      :admin-description="adminDescription"
      :admin-action-label="adminActionLabel"
      :admin-create-action-label="adminCreateActionLabel"
      :admin-create-description="adminCreateDescription"
      :submit-button-visual-state="submitButtonVisualState"
      @update:model-value="emit('update:modelValue', $event)"
      @submit="emit('submit')"
      @open-badge-modal="openBadgeModal"
      @close-badge-modal="closeBadgeModal"
      @copy-badge="copyBadgeSnippet"
      @update-badge-url="handleBadgeUrlInput"
      @verify-badge="verifyBadgePlacement"
      @update-badge-week-start="updateField('badge_week_start', $event)"
      @reset-badge-flow="resetBadgeLaunch"
      @select-free-submission="selectFreeSubmission"
      @select-paid-submission="selectPaidSubmission"
      @toggle-free-schedule-dropdown="toggleFreeScheduleDropdown"
      @open-free-schedule-dropdown="openFreeScheduleDropdown"
      @close-free-schedule-dropdown="closeFreeScheduleDropdown"
      @select-free-schedule-option="selectFreeScheduleOption"
      @submit-free-card="handleFreeCardSubmission"
      @toggle-paid-schedule-dropdown="togglePaidScheduleDropdown"
      @open-paid-schedule-dropdown="openPaidScheduleDropdown"
      @close-paid-schedule-dropdown="closePaidScheduleDropdown"
      @select-paid-schedule-option="selectPaidScheduleOption"
      @submit-paid-card="handlePaidCardSubmission"
      @focus-validation-field="handleValidationItemClick"
      @admin-submit="emitAdminSubmit"
    />
  </div>
</template>

<script setup>
import LaunchChecklistExtrasSection from './LaunchChecklistExtrasSection.vue';
import LaunchChecklistSubmissionSection from './LaunchChecklistSubmissionSection.vue';
import { useLaunchChecklistSubmission } from './composables/useLaunchChecklistSubmission';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  logoPreview: {
    type: String,
    default: null,
  },
  allTechStacks: {
    type: Array,
    default: () => [],
  },
  premiumLaunchPriceCents: {
    type: Number,
    default: 1200,
  },
  freeLaunchQueueMonths: {
    type: Number,
    default: 6,
  },
  productPublishTime: {
    type: String,
    default: '07:00',
  },
  isAdmin: Boolean,
  adminSandboxEnabled: {
    type: Boolean,
    default: true,
  },
  isLoading: Boolean,
  submitState: {
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
  generalErrorMessage: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'submit', 'focus-field']);

const {
  adminActionLabel,
  adminCreateActionLabel,
  adminCreateDescription,
  adminDescription,
  badgeActionLabel,
  badgePageHost,
  badgePlacementUrlReady,
  badgeSnippet,
  badgeStatusMessage,
  badgeStatusTone,
  badgeVerificationMessage,
  badgeVerificationSuccess,
  cardButtonDisabled,
  closeBadgeModal,
  closeFreeScheduleDropdown,
  closePaidScheduleDropdown,
  copyBadgeSnippet,
  emitAdminSubmit,
  freeButtonLabel,
  freeButtonState,
  freeLaunchFeatures,
  freeLaunchQueueHelperText,
  freeScheduleDropdownRef,
  freeScheduleMessageDateLabel,
  freeScheduleOptions,
  handleBadgeUrlInput,
  handleFreeCardSubmission,
  handlePaidCardSubmission,
  handleValidationItemClick,
  hasCopiedBadgeSnippet,
  isAllRequiredFilled,
  isBadgeModalOpen,
  isFreeScheduleDropdownOpen,
  isPaidScheduleDropdownOpen,
  isSandboxAvailable,
  isVerifyingBadge,
  launchWeekOptions,
  openBadgeModal,
  openFreeScheduleDropdown,
  openPaidScheduleDropdown,
  paidLaunchFeatures,
  paidScheduleDropdownRef,
  paidScheduleMessageDateLabel,
  paidScheduleOptions,
  premiumButtonLabel,
  premiumButtonState,
  premiumLaunchPriceLabel,
  progress,
  publishTimeLabel,
  resetBadgeLaunch,
  selectFreeScheduleOption,
  selectFreeSubmission,
  selectedFreeScheduleDate,
  selectedFreeScheduleOption,
  selectedLaunchWeekLabel,
  selectedPaidScheduleDate,
  selectedPaidScheduleOption,
  selectedSubmissionCard,
  selectPaidScheduleOption,
  selectPaidSubmission,
  submitButtonVisualState,
  toggleFreeScheduleDropdown,
  togglePaidScheduleDropdown,
  updateField,
  verifyBadgePlacement,
  wantsBadgeLaunch,
} = useLaunchChecklistSubmission(props, emit);
</script>

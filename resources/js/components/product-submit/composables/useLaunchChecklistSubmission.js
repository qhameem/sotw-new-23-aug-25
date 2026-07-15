import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { formatPriceFromCents, getTabProgress } from '../../../services/productFormService';

export function useLaunchChecklistSubmission(props, emit) {
  const progress = computed(() => getTabProgress('launchChecklist', props.modelValue, props.logoPreview));
  const wantsBadgeLaunch = ref(!!props.modelValue.badge_opt_in || props.modelValue.submission_type === 'badge');
  const selectedSubmissionCard = ref(
    props.modelValue.submission_type === 'free' || props.modelValue.submission_type === 'badge'
      ? 'free'
      : 'paid'
  );
  const isBadgeModalOpen = ref(false);
  const activeSubmissionCard = ref(null);
  const isVerifyingBadge = ref(false);
  const badgeVerificationMessage = ref('');
  const badgeVerificationSuccess = ref(false);
  const badgeSnippet = ref('');
  const hasCopiedBadgeSnippet = ref(false);
  const isFreeScheduleDropdownOpen = ref(false);
  const freeScheduleDropdownRef = ref(null);
  const isPaidScheduleDropdownOpen = ref(false);
  const paidScheduleDropdownRef = ref(null);

  const updateField = (field, value) => {
    emit('update:modelValue', { ...props.modelValue, [field]: value });
  };

  watch(
    () => [props.modelValue.badge_opt_in, props.modelValue.submission_type],
    ([badgeOptIn, submissionType]) => {
      wantsBadgeLaunch.value = !!badgeOptIn || submissionType === 'badge';
      selectedSubmissionCard.value = submissionType === 'free' || submissionType === 'badge'
        ? 'free'
        : 'paid';

      if (submissionType === 'paid') {
        isBadgeModalOpen.value = false;
        isFreeScheduleDropdownOpen.value = false;
        return;
      }

      isPaidScheduleDropdownOpen.value = false;
    },
    { immediate: true }
  );

  watch(
    () => props.modelValue.badge_verified,
    (value) => {
      if (value) {
        badgeVerificationSuccess.value = true;
        if (!badgeVerificationMessage.value) {
          badgeVerificationMessage.value = 'Badge verified. Choose your launch date.';
        }
        return;
      }

      if (badgeVerificationSuccess.value) {
        badgeVerificationSuccess.value = false;
        badgeVerificationMessage.value = 'Badge URL changed. Verify again to unlock date selection.';
      }
    }
  );

  const isAllRequiredFilled = computed(() => {
    const { link, name, tagline, description, categories, pricing, logo, logos } = props.modelValue;
    const categoriesCustom = props.modelValue.categories_custom || [];
    const actualPricingCategories = (pricing || []).filter((id) => id !== null && id !== undefined && id !== '' && !isNaN(id));

    const requiredFields = [
      link,
      name,
      tagline,
      description,
      (categories && Array.isArray(categories) && categories.length > 0) || categoriesCustom.length > 0,
      actualPricingCategories.length > 0,
      logo || (logos && Array.isArray(logos) && logos.length > 0) || props.logoPreview,
    ];

    return requiredFields.every((field) => field);
  });

  const freeLaunchFeatures = [
    '9 slots/day',
    'Standard visibility',
    'Up to 365 days scheduling',
    {
      textBefore: 'Display our badge to launch free.',
      linkText: '',
      action: 'badge-modal',
    },
  ];

  const paidLaunchFeatures = [
    'Scheduled premium launch',
    'Guaranteed permanent listing',
    {
      text: 'Guaranteed do-follow backlink',
      underline: true,
    },
    'Up to 60 days scheduling',
  ];

  const badgePlacementUrlReady = computed(() => {
    const value = (props.modelValue.badge_placement_url || '').trim();
    return /^https?:\/\//i.test(value);
  });

  const addMonths = (date, months) => {
    const nextDate = new Date(date);
    nextDate.setMonth(nextDate.getMonth() + months);
    nextDate.setHours(0, 0, 0, 0);
    return nextDate;
  };

  const getNextMonday = (date) => {
    const nextMonday = new Date(date);
    const daysUntilNextMonday = ((8 - nextMonday.getDay()) % 7) || 7;
    nextMonday.setDate(nextMonday.getDate() + daysUntilNextMonday);
    nextMonday.setHours(0, 0, 0, 0);
    return nextMonday;
  };

  const getMondayOnOrAfter = (date) => {
    const monday = new Date(date);
    const daysUntilMonday = (8 - monday.getDay()) % 7;
    monday.setDate(monday.getDate() + daysUntilMonday);
    monday.setHours(0, 0, 0, 0);
    return monday;
  };

  const formatLocalDateValue = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const parseLocalDateValue = (value) => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));

    if (!match) {
      return null;
    }

    const [, year, month, day] = match;
    return new Date(Number(year), Number(month) - 1, Number(day));
  };

  const formatScheduledPillDate = (value) => {
    const parsedDate = parseLocalDateValue(value);

    if (!parsedDate) {
      return '';
    }

    return parsedDate.toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  const formatScheduleOption = (date, availabilityLabel) => ({
    dateLabel: date.toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
    }),
    availabilityLabel,
  });

  const launchWeekOptions = computed(() => {
    const dates = [];
    const nextMonday = getNextMonday(new Date());
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 365);
    maxDate.setHours(0, 0, 0, 0);

    for (let current = new Date(nextMonday); current <= maxDate; current.setDate(current.getDate() + 1)) {
      const launchDate = new Date(current);
      dates.push({
        value: formatLocalDateValue(launchDate),
        label: launchDate.toLocaleDateString('en-US', {
          weekday: 'long',
          month: 'long',
          day: 'numeric',
          year: 'numeric',
        }),
      });
    }

    return dates;
  });

  const paidScheduleOptions = computed(() => {
    const options = [];
    const nextMonday = getNextMonday(new Date());
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 60);
    maxDate.setHours(0, 0, 0, 0);

    for (let current = new Date(nextMonday); current <= maxDate; current.setDate(current.getDate() + 7)) {
      const optionDate = new Date(current);

      options.push({
        value: formatLocalDateValue(optionDate),
        ...formatScheduleOption(optionDate, 'premium slot available'),
      });
    }

    return options;
  });

  const normalizedFreeLaunchQueueMonths = computed(() => {
    const value = Number(props.freeLaunchQueueMonths);

    if (!Number.isFinite(value)) {
      return 6;
    }

    return Math.max(0, Math.floor(value));
  });

  const freeScheduleOptions = computed(() => {
    const options = [];
    const firstAvailableDate = getMondayOnOrAfter(addMonths(new Date(), normalizedFreeLaunchQueueMonths.value));
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 365);
    maxDate.setHours(0, 0, 0, 0);

    for (let current = new Date(firstAvailableDate); current <= maxDate; current.setDate(current.getDate() + 7)) {
      const optionDate = new Date(current);

      options.push({
        value: formatLocalDateValue(optionDate),
        ...formatScheduleOption(optionDate, 'standard queue availability'),
      });
    }

    return options;
  });

  const freeLaunchQueueHelperText = computed(() => {
    if (normalizedFreeLaunchQueueMonths.value === 0) {
      return 'Default: current queue timing. Standard queue availability.';
    }

    if (normalizedFreeLaunchQueueMonths.value === 1) {
      return 'Default: about 1 month out. Standard queue availability.';
    }

    return `Default: about ${normalizedFreeLaunchQueueMonths.value} months out. Standard queue availability.`;
  });

  const freeScheduleMinDate = computed(() => freeScheduleOptions.value[0]?.value || '');
  const paidScheduleMinDate = computed(() => paidScheduleOptions.value[0]?.value || '');
  const isFreeScheduleDateAvailable = (value) => freeScheduleOptions.value.some((option) => option.value === value);
  const isPaidScheduleDateAvailable = (value) => paidScheduleOptions.value.some((option) => option.value === value);

  const selectedFreeScheduleDate = computed(() => (
    isFreeScheduleDateAvailable(props.modelValue.free_schedule_date)
      ? props.modelValue.free_schedule_date
      : freeScheduleMinDate.value
  ));

  const selectedFreeScheduleOption = computed(() => (
    freeScheduleOptions.value.find((option) => option.value === selectedFreeScheduleDate.value) || {
      value: '',
      dateLabel: 'Select a launch date',
      availabilityLabel: '',
    }
  ));

  const selectedPaidScheduleDate = computed(() => (
    isPaidScheduleDateAvailable(props.modelValue.paid_schedule_date)
      ? props.modelValue.paid_schedule_date
      : paidScheduleMinDate.value
  ));

  const selectedPaidScheduleOption = computed(() => (
    paidScheduleOptions.value.find((option) => option.value === selectedPaidScheduleDate.value) || {
      value: '',
      dateLabel: 'Select a launch date',
      availabilityLabel: '',
    }
  ));

  const normalizedProductPublishTime = computed(() => (
    /^\d{2}:\d{2}$/.test(props.productPublishTime || '') ? props.productPublishTime : '07:00'
  ));

  const publishTimeLabel = computed(() => {
    const [hourPart = '07', minutePart = '00'] = normalizedProductPublishTime.value.split(':');
    const hour = Number.parseInt(hourPart, 10);
    const minute = Number.parseInt(minutePart, 10);

    if (!Number.isFinite(hour) || !Number.isFinite(minute)) {
      return '7:00 UTC';
    }

    return `${hour}:${String(minute).padStart(2, '0')} UTC`;
  });

  const submitButtonVisualState = computed(() => {
    if (props.submitState === 'loading' || props.submitState === 'success') {
      return props.submitState;
    }

    return 'idle';
  });

  const freeButtonState = computed(() => (
    activeSubmissionCard.value === 'free' ? submitButtonVisualState.value : 'idle'
  ));

  const premiumButtonState = computed(() => (
    activeSubmissionCard.value === 'paid' ? submitButtonVisualState.value : 'idle'
  ));

  const cardButtonDisabled = computed(() => props.isLoading || isVerifyingBadge.value);
  const isSandboxAvailable = computed(() => !props.modelValue.id && props.isAdmin && props.adminSandboxEnabled);

  const adminDescription = computed(() => {
    if (isSandboxAvailable.value) {
      return 'As an admin, you can save directly here, and Sandbox mode can be controlled from the top of the page.';
    }

    return 'As an admin, you can save your edits directly without selecting a pricing option.';
  });

  const adminActionLabel = computed(() => {
    if (props.modelValue.sandbox_mode) {
      return props.modelValue.id ? 'Run Sandbox Save' : 'Run Sandbox Submit';
    }

    return 'Save All Changes';
  });

  const adminCreateActionLabel = computed(() => (
    props.modelValue.sandbox_mode ? 'Run Sandbox Submit' : 'Submit Product'
  ));

  const adminCreateDescription = computed(() => {
    if (isSandboxAvailable.value) {
      return 'Submit the product from the bottom of the form. Use Sandbox mode from the top of the page if you only want to test button states.';
    }

    return 'Submit the product from the bottom of the form. Sandbox mode is currently disabled in admin settings.';
  });

  const freeButtonLabel = 'Proceed to Free Launch';
  const premiumLaunchPriceLabel = computed(() => formatPriceFromCents(props.premiumLaunchPriceCents));
  const premiumButtonLabel = 'Proceed to Premium Launch';
  const paidScheduleMessageDateLabel = computed(() => formatScheduledPillDate(selectedPaidScheduleDate.value));
  const freeScheduleMessageDateLabel = computed(() => formatScheduledPillDate(selectedFreeScheduleDate.value));

  const selectedLaunchWeekLabel = computed(() => (
    launchWeekOptions.value.find((week) => week.value === props.modelValue.badge_week_start)?.label || ''
  ));

  const badgePageHost = computed(() => {
    const value = String(props.modelValue.badge_placement_url || '').trim();

    if (!value) {
      return '';
    }

    try {
      return new URL(value).host;
    } catch {
      return value;
    }
  });

  const badgeStatusMessage = computed(() => (
    props.validationErrors.badge_week_start
      || props.validationErrors.badge_verified
      || props.validationErrors.badge_placement_url
      || badgeVerificationMessage.value
      || ''
  ));

  const badgeStatusTone = computed(() => (
    props.validationErrors.badge_week_start || props.validationErrors.badge_verified || props.validationErrors.badge_placement_url
      ? 'text-amber-700'
      : badgeVerificationSuccess.value
        ? 'text-primary-700'
        : 'text-gray-500'
  ));

  const badgeActionLabel = computed(() => {
    if (!wantsBadgeLaunch.value) {
      return 'Set up the required badge';
    }

    if (props.modelValue.badge_verified) {
      return 'Edit badge setup';
    }

    return 'Continue badge setup';
  });

  const loadBadgeSnippet = async () => {
    if (badgeSnippet.value) {
      return;
    }

    try {
      const response = await axios.get('/api/badge-snippet-preview');
      badgeSnippet.value = response.data?.snippet || '';
    } catch (error) {
      console.error('Failed to load badge snippet preview:', error);
    }
  };

  const copyBadgeSnippet = async () => {
    if (!badgeSnippet.value) {
      return;
    }

    try {
      await navigator.clipboard.writeText(badgeSnippet.value);
      hasCopiedBadgeSnippet.value = true;
      badgeVerificationSuccess.value = true;
      badgeVerificationMessage.value = 'Code copied. Add it to your site, then verify the page URL.';
      window.setTimeout(() => {
        hasCopiedBadgeSnippet.value = false;
      }, 2000);
    } catch (error) {
      console.error('Failed to copy badge snippet:', error);
    }
  };

  const openBadgeModal = async () => {
    selectedSubmissionCard.value = 'free';
    wantsBadgeLaunch.value = true;
    closePaidScheduleDropdown();
    closeFreeScheduleDropdown();

    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: true,
      submissionOption: props.modelValue.badge_verified ? 'badge' : 'free',
      submission_type: props.modelValue.badge_verified ? 'badge' : 'free',
      badge_placement_url: props.modelValue.badge_placement_url || props.modelValue.link || '',
    });

    isBadgeModalOpen.value = true;
    await loadBadgeSnippet();
  };

  const resetBadgeLaunch = () => {
    isBadgeModalOpen.value = false;
    activeSubmissionCard.value = null;
    hasCopiedBadgeSnippet.value = false;
    badgeVerificationSuccess.value = false;
    badgeVerificationMessage.value = '';

    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: false,
      submissionOption: 'free',
      submission_type: 'free',
      badge_placement_url: '',
      badge_week_start: '',
      badge_verified: false,
    });
  };

  const closeBadgeModal = () => {
    if (!props.modelValue.badge_verified) {
      resetBadgeLaunch();
      return;
    }

    isBadgeModalOpen.value = false;
  };

  const handleBadgeUrlInput = (value) => {
    const badgeUrlChanged = (props.modelValue.badge_placement_url || '') !== value;
    const isVerifiedBadgeSubmission = !badgeUrlChanged && props.modelValue.badge_verified;

    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: true,
      badge_placement_url: value,
      badge_verified: isVerifiedBadgeSubmission,
      badge_week_start: badgeUrlChanged ? '' : props.modelValue.badge_week_start,
      submissionOption: isVerifiedBadgeSubmission ? 'badge' : 'free',
      submission_type: isVerifiedBadgeSubmission ? 'badge' : 'free',
    });

    if (badgeUrlChanged) {
      badgeVerificationSuccess.value = false;
      badgeVerificationMessage.value = '';
    }
  };

  const verifyBadgePlacement = async () => {
    if (!badgePlacementUrlReady.value) {
      badgeVerificationSuccess.value = false;
      badgeVerificationMessage.value = 'Enter the full badge page URL, including https://.';
      return;
    }

    isVerifyingBadge.value = true;
    badgeVerificationSuccess.value = false;
    badgeVerificationMessage.value = '';

    try {
      const response = await axios.post('/api/verify-badge-placement', {
        url: props.modelValue.badge_placement_url,
      });

      badgeVerificationSuccess.value = true;
      badgeVerificationMessage.value = response.data?.message || 'Badge verified. Choose your launch date.';

      emit('update:modelValue', {
        ...props.modelValue,
        badge_opt_in: true,
        submissionOption: 'badge',
        submission_type: 'badge',
        badge_placement_url: response.data?.checked_url || props.modelValue.badge_placement_url,
        badge_verified: true,
      });
    } catch (error) {
      badgeVerificationSuccess.value = false;
      badgeVerificationMessage.value = error.response?.data?.message || 'We could not verify the badge on that page yet.';

      emit('update:modelValue', {
        ...props.modelValue,
        badge_opt_in: true,
        submissionOption: 'free',
        submission_type: 'free',
        badge_verified: false,
        badge_week_start: '',
      });
    } finally {
      isVerifyingBadge.value = false;
    }
  };

  const handleSubmission = () => {
    const isBadgeSubmission = wantsBadgeLaunch.value && props.modelValue.badge_verified;

    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: wantsBadgeLaunch.value,
      submissionOption: isBadgeSubmission ? 'badge' : 'free',
      submission_type: isBadgeSubmission ? 'badge' : 'free',
      badge_week_start: isBadgeSubmission ? props.modelValue.badge_week_start : '',
      badge_placement_url: wantsBadgeLaunch.value ? props.modelValue.badge_placement_url : '',
      badge_verified: isBadgeSubmission,
      tech_stack_custom: props.modelValue.tech_stack_custom,
    });

    emit('submit');
  };

  const selectPaidSubmission = () => {
    selectedSubmissionCard.value = 'paid';
    const nextAvailablePaidDate = isPaidScheduleDateAvailable(props.modelValue.paid_schedule_date)
      ? props.modelValue.paid_schedule_date
      : paidScheduleMinDate.value;

    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: false,
      submissionOption: 'paid',
      submission_type: 'paid',
      badge_verified: false,
      badge_week_start: '',
      badge_placement_url: '',
      paid_schedule_date: nextAvailablePaidDate,
      tech_stack_custom: props.modelValue.tech_stack_custom,
    });

    wantsBadgeLaunch.value = false;
    isBadgeModalOpen.value = false;
    isFreeScheduleDropdownOpen.value = false;
    isPaidScheduleDropdownOpen.value = false;
    badgeVerificationSuccess.value = false;
    badgeVerificationMessage.value = '';
  };

  const selectFreeSubmission = () => {
    selectedSubmissionCard.value = 'free';
    closePaidScheduleDropdown();
    closeFreeScheduleDropdown();
    openBadgeModal();
  };

  const handleFreeScheduleDateInput = (value) => {
    selectedSubmissionCard.value = 'free';

    emit('update:modelValue', {
      ...props.modelValue,
      submissionOption: wantsBadgeLaunch.value && props.modelValue.badge_verified ? 'badge' : 'free',
      submission_type: wantsBadgeLaunch.value && props.modelValue.badge_verified ? 'badge' : 'free',
      free_schedule_date: value,
      tech_stack_custom: props.modelValue.tech_stack_custom,
    });

    closeFreeScheduleDropdown();
  };

  const handlePaidScheduleDateInput = (value) => {
    selectedSubmissionCard.value = 'paid';

    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: false,
      submissionOption: 'paid',
      submission_type: 'paid',
      badge_verified: false,
      badge_week_start: '',
      badge_placement_url: '',
      paid_schedule_date: value,
      tech_stack_custom: props.modelValue.tech_stack_custom,
    });

    wantsBadgeLaunch.value = false;
    isBadgeModalOpen.value = false;
    isPaidScheduleDropdownOpen.value = false;
    badgeVerificationSuccess.value = false;
    badgeVerificationMessage.value = '';
  };

  const openPaidScheduleDropdown = () => {
    closeFreeScheduleDropdown();
    isPaidScheduleDropdownOpen.value = true;
  };

  const closePaidScheduleDropdown = () => {
    isPaidScheduleDropdownOpen.value = false;
  };

  const togglePaidScheduleDropdown = () => {
    isPaidScheduleDropdownOpen.value = !isPaidScheduleDropdownOpen.value;
  };

  const selectPaidScheduleOption = (value) => {
    handlePaidScheduleDateInput(value);
  };

  const openFreeScheduleDropdown = () => {
    closePaidScheduleDropdown();
    isFreeScheduleDropdownOpen.value = true;
  };

  const closeFreeScheduleDropdown = () => {
    isFreeScheduleDropdownOpen.value = false;
  };

  const toggleFreeScheduleDropdown = () => {
    isFreeScheduleDropdownOpen.value = !isFreeScheduleDropdownOpen.value;
  };

  const selectFreeScheduleOption = (value) => {
    handleFreeScheduleDateInput(value);
  };

  const handleScheduleDropdownClickOutside = (event) => {
    if (!paidScheduleDropdownRef.value?.contains(event.target)) {
      closePaidScheduleDropdown();
    }

    if (!freeScheduleDropdownRef.value?.contains(event.target)) {
      closeFreeScheduleDropdown();
    }
  };

  const handleFreeCardSubmission = () => {
    activeSubmissionCard.value = 'free';

    if (!props.modelValue.badge_verified) {
      openBadgeModal();
      emit('focus-field', 'badge_verified');
      activeSubmissionCard.value = null;
      return;
    }

    if (!props.modelValue.badge_week_start) {
      openBadgeModal();
      emit('focus-field', 'badge_week_start');
      activeSubmissionCard.value = null;
      return;
    }

    selectFreeSubmission();
    handleSubmission();

    window.setTimeout(() => {
      if (!props.isLoading && props.submitState === 'idle') {
        activeSubmissionCard.value = null;
      }
    }, 0);
  };

  const handlePaidCardSubmission = () => {
    activeSubmissionCard.value = 'paid';
    selectPaidSubmission();
    emit('submit', 'paid');

    window.setTimeout(() => {
      if (!props.isLoading && props.submitState === 'idle') {
        activeSubmissionCard.value = null;
      }
    }, 0);
  };

  const handleValidationItemClick = (fieldKey) => {
    const badgeFields = ['badge_placement_url', 'badge_verified', 'badge_week_start'];

    if (badgeFields.includes(fieldKey)) {
      openBadgeModal();
      window.setTimeout(() => {
        emit('focus-field', fieldKey);
      }, 180);
      return;
    }

    emit('focus-field', fieldKey);
  };

  watch(
    () => [props.submitState, props.isLoading],
    ([submitState, isLoading]) => {
      if (!isLoading && submitState === 'idle') {
        activeSubmissionCard.value = null;
      }
    }
  );

  const emitAdminSubmit = () => {
    emit('submit');
  };

  onMounted(() => {
    document.addEventListener('mousedown', handleScheduleDropdownClickOutside);

    if (wantsBadgeLaunch.value) {
      loadBadgeSnippet();
    }

    if (selectedSubmissionCard.value === 'paid' && !isPaidScheduleDateAvailable(props.modelValue.paid_schedule_date)) {
      emit('update:modelValue', {
        ...props.modelValue,
        submissionOption: 'paid',
        submission_type: 'paid',
        paid_schedule_date: selectedPaidScheduleDate.value,
      });
    }

    if (selectedSubmissionCard.value === 'free' && !isFreeScheduleDateAvailable(props.modelValue.free_schedule_date)) {
      emit('update:modelValue', {
        ...props.modelValue,
        submissionOption: props.modelValue.badge_verified ? 'badge' : 'free',
        submission_type: props.modelValue.badge_verified ? 'badge' : 'free',
        free_schedule_date: selectedFreeScheduleDate.value,
      });
    }
  });

  onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleScheduleDropdownClickOutside);
  });

  return {
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
  };
}

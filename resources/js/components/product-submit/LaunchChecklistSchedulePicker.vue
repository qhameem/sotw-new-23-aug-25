<template>
  <div class="flex w-full flex-col gap-3">
    <div :ref="setDropdownRef" class="relative w-full">
      <label :for="dropdownId" class="mb-1.5 block text-sm font-medium text-gray-700">
        Launch Date <span class="text-rose-500">*</span>
      </label>
      <button
        :id="dropdownId"
        type="button"
        class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-700 shadow-sm transition duration-200 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
        :class="isOpen ? 'border-primary-400 ring-2 ring-primary-100' : 'hover:border-gray-400'"
        aria-haspopup="listbox"
        :aria-expanded="isOpen"
        @click="$emit('toggle')"
        @keydown.esc.prevent="$emit('close')"
        @keydown.down.prevent="$emit('open')"
      >
        <span class="min-w-0 truncate">
          <span class="font-semibold text-gray-900">{{ selectedOption.dateLabel }}</span>
          <span class="ml-2 text-xs text-gray-400">{{ selectedOption.availabilityLabel }}</span>
        </span>
        <svg
          class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
          :class="isOpen ? 'rotate-180 text-primary-500' : ''"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
      </button>

      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 scale-95 opacity-0"
        enter-to-class="translate-y-0 scale-100 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 scale-100 opacity-100"
        leave-to-class="translate-y-2 scale-95 opacity-0"
      >
        <div
          v-if="isOpen"
          role="listbox"
          class="absolute bottom-full left-0 right-0 z-30 mb-2 origin-bottom overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_20px_50px_rgba(15,23,42,0.14)] ring-1 ring-slate-200/70"
        >
          <div class="max-h-64 overflow-y-auto p-2">
            <button
              v-for="option in options"
              :key="option.value"
              type="button"
              role="option"
              class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm transition-colors duration-150"
              :class="optionClasses(option.value)"
              :aria-selected="option.value === selectedValue"
              @click="$emit('select', option.value)"
            >
              <span class="min-w-0 truncate">
                <span :class="selectedValueClass(option.value)">{{ option.dateLabel }}</span>
                <span class="ml-2 text-xs" :class="availabilityClass(option.value)">{{ option.availabilityLabel }}</span>
              </span>
              <svg
                v-if="option.value === selectedValue"
                class="ml-3 h-4 w-4 shrink-0"
                :class="checkIconClass"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.312a1 1 0 0 1-1.42 0L3.29 9.268a1 1 0 0 1 1.414-1.415l4.046 4.047 6.54-6.604a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </Transition>

      <div class="mt-2 flex w-1/2 flex-wrap items-center gap-1 rounded-lg border border-primary-200 bg-primary-50 px-2 py-2 text-xs">
        <svg class="h-5 w-5 shrink-0 text-primary-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M6 2a1 1 0 1 0-2 0v1H3a2 2 0 0 0-2 2v2h18V5a2 2 0 0 0-2-2h-1V2a1 1 0 1 0-2 0v1H6V2Zm13 7H1v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9ZM5 12a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
        </svg>
        <span class="text-gray-500">Scheduled for</span>
        <span class="font-semibold text-gray-900">{{ scheduledDateLabel }}</span>
        <span class="text-gray-300">&bull;</span>
        <span class="text-gray-400">{{ publishTimeLabel }}</span>
      </div>

      <p v-if="error" class="mt-1 text-[11px] text-rose-600">{{ error }}</p>
    </div>

    <div class="my-1 border-t border-gray-200"></div>

    <AnimatedSubmitButton
      :label="actionLabel"
      :state="actionState"
      :disabled="actionDisabled"
      full-width
      @click="$emit('submit')"
    />
  </div>
</template>

<script setup>
import AnimatedSubmitButton from './AnimatedSubmitButton.vue';

const props = defineProps({
  actionDisabled: {
    type: Boolean,
    default: false,
  },
  actionLabel: {
    type: String,
    required: true,
  },
  actionState: {
    type: String,
    default: 'idle',
  },
  dropdownId: {
    type: String,
    required: true,
  },
  dropdownRef: {
    type: Object,
    default: null,
  },
  error: {
    type: String,
    default: '',
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
  options: {
    type: Array,
    default: () => [],
  },
  publishTimeLabel: {
    type: String,
    default: '',
  },
  scheduledDateLabel: {
    type: String,
    default: '',
  },
  selectedOption: {
    type: Object,
    default: () => ({
      dateLabel: 'Select a launch date',
      availabilityLabel: '',
    }),
  },
  selectedValue: {
    type: String,
    default: '',
  },
  variant: {
    type: String,
    default: 'free',
  },
});

defineEmits(['close', 'open', 'select', 'submit', 'toggle']);

const setDropdownRef = (element) => {
  if (props.dropdownRef) {
    props.dropdownRef.value = element;
  }
};

const optionClasses = (value) => {
  if (props.variant === 'paid') {
    return value === props.selectedValue
      ? 'bg-primary-500 text-white shadow-sm'
      : 'text-gray-700 hover:bg-gray-100';
  }

  return value === props.selectedValue
    ? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200 shadow-sm'
    : 'text-gray-700 hover:bg-gray-100';
};

const selectedValueClass = (value) => {
  if (props.variant === 'paid' && value === props.selectedValue) {
    return 'font-semibold text-white';
  }

  return 'font-semibold text-gray-900';
};

const availabilityClass = (value) => {
  if (props.variant === 'paid' && value === props.selectedValue) {
    return 'text-primary-100';
  }

  return 'text-gray-400';
};

const checkIconClass = props.variant === 'paid' ? '' : 'text-gray-500';
</script>

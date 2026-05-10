<template>
  <button
    type="button"
    :disabled="isDisabled"
    :aria-busy="state === 'loading' ? 'true' : 'false'"
    :aria-label="ariaLabel"
    :class="buttonClasses"
    @click="$emit('click')"
  >
    <span class="absolute inset-0 flex items-center justify-center overflow-hidden">
      <span
        :class="labelClasses"
        class="pointer-events-none whitespace-nowrap text-sm font-bold tracking-[0.01em] transition-all duration-200"
      >
        {{ label }}
      </span>

      <span
        :class="spinnerWrapperClasses"
        class="pointer-events-none absolute flex items-center justify-center transition-all duration-200"
        aria-hidden="true"
      >
        <span class="h-8 w-8 rounded-full border-[3px] border-primary-500/20 border-t-primary-500 animate-spin"></span>
      </span>

      <span
        :class="checkWrapperClasses"
        class="pointer-events-none absolute flex items-center justify-center transition-all duration-200"
        aria-hidden="true"
      >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 13l4 4L19 7" />
        </svg>
      </span>
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  state: {
    type: String,
    default: 'idle',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['click']);

const isDisabled = computed(() => props.disabled || props.state !== 'idle');

const ariaLabel = computed(() => {
  if (props.state === 'loading') {
    return `${props.label}. Processing`;
  }

  if (props.state === 'success') {
    return 'Submission complete';
  }

  return props.label;
});

const buttonClasses = computed(() => {
  const baseClasses = [
    'relative',
    'inline-flex',
    'h-12',
    'items-center',
    'justify-center',
    'overflow-hidden',
    'rounded-full',
    'border',
    'px-6',
    'focus:outline-none',
    'focus:ring-2',
    'focus:ring-primary-500',
    'focus:ring-offset-2',
    'transition-[width,background-color,border-color,box-shadow,transform]',
    'duration-300',
    'ease-[cubic-bezier(0.22,1,0.36,1)]',
  ];

  if (props.state === 'loading') {
    return [
      ...baseClasses,
      'w-[3.25rem]',
      'cursor-wait',
      'border-primary-500/30',
      'bg-white',
      'text-primary-500',
      'shadow-none',
    ];
  }

  if (props.state === 'success') {
    return [
      ...baseClasses,
      'w-full',
      'sm:w-[240px]',
      'border-emerald-500',
      'bg-emerald-500',
      'text-white',
      'shadow-sm',
    ];
  }

  return [
    ...baseClasses,
    'w-full',
    'sm:w-[240px]',
    props.disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:-translate-y-0.5 hover:bg-primary-600 hover:border-primary-600 hover:shadow-md',
    'border-primary-500',
    'bg-primary-500',
    'text-white',
    'shadow-sm',
  ];
});

const labelClasses = computed(() => {
  if (props.state === 'idle') {
    return 'translate-y-0 scale-100 opacity-100';
  }

  return '-translate-y-1 scale-95 opacity-0';
});

const spinnerWrapperClasses = computed(() => {
  if (props.state === 'loading') {
    return 'scale-100 opacity-100';
  }

  return 'scale-75 opacity-0';
});

const checkWrapperClasses = computed(() => {
  if (props.state === 'success') {
    return 'scale-100 opacity-100';
  }

  return 'scale-75 opacity-0';
});
</script>

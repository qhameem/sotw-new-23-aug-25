<template>
  <div
    :class="optionClass"
    class="border rounded-lg p-4 flex-1 min-w-[300px] relative transition-colors duration-200 h-full flex flex-col min-h-[300px] border-t-4 border-emerald-500"
  >
    <!-- Recommended badge -->
    <div class="absolute -top-2 -right-2 z-10">

    </div>
    <div>
      <h4 class="font-semibold text-gray-900 mb-2">{{ title }} <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
        Recommended
      </span></h4>
      <p class="text-sm text-gray-700 font-medium mb-3">{{ description }}</p>
      <p class="text-sm text-gray-600 mb-3">{{ price }}</p>
      <ul class="text-sm text-gray-600 mb-4 space-y-1">
        <li v-for="(feature, index) in features" :key="index" class="flex items-start">
          <svg class="h-5 w-5 text-emerald-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ feature }}</span>
        </li>
      </ul>
    </div>
    <div class="mt-auto pt-4">
      <button
        type="button"
        @click="handleSubmit"
        :disabled="!isAllRequiredFilled || isLoading"
        :class="{
          'opacity-50 cursor-not-allowed': !isAllRequiredFilled && !isLoading,
          'cursor-wait': isLoading,
          'hover:bg-emerald-600': isAllRequiredFilled && !isLoading
        }"
        class="relative inline-flex min-h-11 w-full items-center justify-center rounded-md border border-transparent bg-emerald-500 px-4 py-2.5 text-sm font-medium text-white transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-50 focus:ring-offset-2"
      >
        <span
          class="whitespace-nowrap transition-opacity duration-150"
          :class="isLoading ? 'opacity-0' : 'opacity-100'"
        >
          {{ buttonLabel }}
        </span>
        <span
          v-if="isLoading"
          class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current"
          aria-live="polite"
        >
          <span class="flex items-center gap-1.5" aria-hidden="true">
            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.3s]"></span>
            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.15s]"></span>
            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
          </span>
          <span>Processing</span>
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  name: {
    type: String,
    required: true
  },
  value: {
    type: String,
    required: true
  },
  modelValue: {
    type: String,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  price: {
    type: String,
    required: false,
    default: ''
  },
  features: {
    type: Array,
    required: true
  },
  isAllRequiredFilled: {
    type: Boolean,
    required: false,
    default: true
  },
  isEditMode: {
    type: Boolean,
    default: false
  },
  isLoading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'submit']);

const buttonLabel = computed(() => (props.isEditMode ? 'Save changes' : 'Submit with Badge'));

const handleSubmit = () => {
  // Update the selected option when the button is clicked
  emit('update:modelValue', props.value);

  if (props.isAllRequiredFilled) {
    emit('submit', props.value);
  } else {
    alert('Please fill out all required fields before submitting.');
  }
};

const optionClass = computed(() => ({
  'border-gray-200': true,
  'hover:border-emerald-500': true
}));
</script>

<template>
  <div 
    :class="optionClass" 
    class="border rounded-lg p-4 flex-1 min-w-[300px] relative transition-colors duration-200 h-full flex flex-col min-h-[300px]"
  >
    <div>
      <h4 class="font-medium text-gray-900 mb-2">{{ title }}</h4>
      <p class="text-sm text-gray-700 font-medium mb-3">{{ description }}</p>
      <p class="text-sm text-gray-600 mb-3">{{ price }}</p>
      <ul class="text-sm text-gray-600 mb-4 space-y-1">
        <li v-for="(feature, index) in features" :key="index" class="flex items-start">
          <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ feature }}</span>
        </li>
      </ul>
    </div>
    <div class="mt-auto pt-4">
      <button
        @click="handleSubmit"
        :disabled="!isAllRequiredFilled || isLoading"
        :class="{
          'opacity-50 cursor-not-allowed': !isAllRequiredFilled || isLoading,
          'hover:bg-rose-600': isAllRequiredFilled && !isLoading
        }"
        class="w-full flex justify-center py-2 border border-transparent text-sm font-medium rounded-md text-white bg-rose-500 opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-50"
      >
        <span v-if="!isLoading">{{ isEditMode ? 'Save changes' : 'Submit for Free' }}</span>
        <span v-else class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Processing...
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

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
  }
});

const emit = defineEmits(['update:modelValue', 'submit']);

const isLoading = ref(false);

const handleSubmit = () => {
 // Update the selected option when the button is clicked
  emit('update:modelValue', props.value);
  
  if (props.isAllRequiredFilled) {
    // Set loading state to true
    isLoading.value = true;
    // Set the pricing to 'free' before submitting
    emit('submit', props.value);
  } else {
    // Show error message if not all required fields are filled
    alert('Please fill out all required fields before submitting.');
  }
};

// Function to be called when submission is complete
const submissionComplete = () => {
  isLoading.value = false;
};

// Expose the submissionComplete function to parent components
defineExpose({
  submissionComplete
});

const optionClass = computed(() => ({
  'border-gray-200': true,
 'hover:border-gray-300': true
}));
</script>
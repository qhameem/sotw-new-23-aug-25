<template>
  <div
    :class="optionClass"
    class="border rounded-lg p-4 flex-1 min-w-[300px] relative transition-colors duration-200 h-full flex flex-col min-h-[300px] border-t-4 border-rose-500"
  >
    <!-- Recommended badge -->
    <div class="absolute -top-2 -right-2 z-10">
      
    </div>
    <div>
      <h4 class="font-semibold text-gray-900 mb-2">{{ title }} <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
        Popular
      </span></h4>
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
        :disabled="!isAllRequiredFilled"
        :class="{
          'opacity-50 cursor-not-allowed': !isAllRequiredFilled,
          'hover:bg-rose-600': isAllRequiredFilled
        }"
        class="w-full flex justify-center py-2 border border-transparent text-sm font-medium rounded-md text-white bg-rose-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-50"
      >
        Schedule Priority Launch – $29
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
  }
});

const emit = defineEmits(['update:modelValue', 'submit']);

const handleSubmit = () => {
  // Update the selected option when the button is clicked
  emit('update:modelValue', props.value);
  
  if (props.isAllRequiredFilled) {
    // Set the pricing to 'paid' before submitting
    emit('submit', props.value);
  } else {
    console.warn('Please fill out all required fields before submitting.');
  }
};

const optionClass = computed(() => ({
  'border-gray-200': true,
 'hover:border-rose-500': true
}));
</script>
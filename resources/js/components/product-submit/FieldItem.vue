<template>
  <div 
    class="flex items-center transition-all duration-200" 
    :class="[fieldClass, clickedClass]" 
    @click="handleClick" 
    style="cursor: pointer;"
  >
    <svg v-if="filled" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    <svg v-else class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
    <span class="text-sm">{{ label }}</span>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  filled: {
    type: Boolean,
    required: true
  },
  label: {
    type: String,
    required: true
  },
  fieldKey: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['field-clicked']);

// Reactive property to track clicked state for visual feedback
const isClicked = ref(false);

// Computed class for clicked state visual feedback
const clickedClass = computed(() => ({
  'bg-blue-100': isClicked.value,
  'p-1': isClicked.value,
  'rounded': isClicked.value,
  'scale-105': isClicked.value,
}));

const fieldClass = computed(() => ({
  'text-green-600': props.filled,
  'text-gray-500': !props.filled
}));

const handleClick = () => {
  // Set clicked state for visual feedback
  isClicked.value = true;
  
  // Emit the event
  emit('field-clicked', props.fieldKey);
  
  // Reset clicked state after a short delay
  setTimeout(() => {
    isClicked.value = false;
  }, 200);
};
</script>
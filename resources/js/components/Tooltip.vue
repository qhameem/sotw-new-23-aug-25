<template>
  <div class="relative inline-block group">
    <slot>
      <button class="text-gray-400 hover:text-gray-600 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </button>
    </slot>
    
    <div
      class="absolute z-10 w-64 p-3 text-sm text-white bg-gray-800 rounded-lg shadow-lg bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-opacity duration-300 pointer-events-none"
      role="tooltip"
    >
      <div class="relative">
        {{ content }}
        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  content: {
    type: String,
    required: true
  },
  position: {
    type: String,
    default: 'top' // top, bottom, left, right
  }
});
</script>

<style scoped>
/* Additional styles can be added here if needed */
</style>
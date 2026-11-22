<template>
  <button
    type="button"
    :aria-pressed="selected"
    @click="$emit('toggle')"
    class="group relative inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border transition-colors duration-200"
    :class="selected
      ? 'bg-sky-50 border-sky-600 text-sky-700 shadow-sm'
      : 'bg-white border-gray-400 text-gray-600 hover:border-gray-500 hover:bg-gray-50'"
  >
    {{ name }}
    <span v-if="selected" aria-hidden="true" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs font-medium !text-white shadow group-hover:block group-focus-visible:block">
      Click to deselect
    </span>
    <span v-if="selected" aria-hidden="true" class="pointer-events-none absolute right-1 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full bg-sky-900/80 text-white opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
      <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-width="2" d="M6 6l12 12M18 6L6 18" />
      </svg>
    </span>
  </button>
</template>

<script setup>
defineProps({ name: { type: String, required: true }, selected: Boolean });
defineEmits(['toggle']);
</script>

<template>
  <div v-if="validationSummary.length || generalErrorMessage" class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
    <p class="text-xs font-semibold !text-amber-800">Please fix these before submitting:</p>
    <ul class="mt-2 space-y-1.5 !text-[11px] !text-amber-700">
      <li v-for="item in validationSummary" :key="item.field">
        <button
          type="button"
          class="block w-full rounded-xl border border-amber-300 bg-amber-100 px-3 py-2 text-left !text-[11px] font-medium !text-amber-800 shadow-sm transition-colors hover:bg-amber-200"
          @click="$emit('focus-field', item.field)"
        >
          {{ item.message }}
        </button>
      </li>
      <li v-if="generalErrorMessage">{{ generalErrorMessage }}</li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  validationSummary: {
    type: Array,
    default: () => [],
  },
  generalErrorMessage: {
    type: String,
    default: '',
  },
});

defineEmits(['focus-field']);
</script>

<template>
  <button
    type="button"
    class="relative flex h-full w-full cursor-pointer flex-col rounded-lg border p-5 text-left transition-all duration-200 hover:-translate-y-1 hover:shadow-md md:p-6"
    :class="selected ? 'border-primary-400 bg-primary-50 shadow-sm hover:bg-primary-50 hover:shadow-lg' : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50'"
    @click="$emit('select')"
  >
    <div
      v-if="selected"
      class="absolute -right-2 -top-2 inline-flex items-center rounded-lg bg-primary-500 px-3 py-1 text-xs font-medium text-white"
    >
      Selected
    </div>

    <div class="flex items-center gap-2.5 text-gray-900">
      <svg class="h-5 w-5 shrink-0 text-primary-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.45 4.966 5.482.797-3.966 3.866.936 5.46L12 15.51l-4.902 2.579.936-5.46-3.966-3.866 5.482-.797L12 3Z" />
      </svg>
      <h4 class="text-[18px] font-semibold leading-none">Premium Launch</h4>
    </div>

    <p class="mt-5 text-4xl font-semibold leading-none tracking-[-0.04em] text-gray-950">{{ priceLabel }}</p>

    <ul class="mt-6 space-y-3 text-[13px] text-gray-500">
      <li
        v-for="(feature, index) in normalizedFeatures"
        :key="index"
        class="flex items-start gap-2.5"
      >
        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white">
          <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.312a1 1 0 0 1-1.42 0L3.29 9.268a1 1 0 0 1 1.414-1.415l4.046 4.047 6.54-6.604a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
          </svg>
        </span>
        <p
          class="leading-5 text-gray-500"
          :class="feature.underline ? 'underline underline-offset-4' : ''"
        >
          {{ feature.text }}
        </p>
      </li>
    </ul>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  features: {
    type: Array,
    default: () => [],
  },
  priceLabel: {
    type: String,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
});

const normalizedFeatures = computed(() => props.features.map((feature) => (
  typeof feature === 'string'
    ? { text: feature, underline: false }
    : {
        text: feature?.text || '',
        underline: Boolean(feature?.underline),
      }
)));

defineEmits(['select']);
</script>

<template>
  <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-start gap-4">
      <div class="relative group">
        <button
          type="button"
          class="relative flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-gray-50 transition hover:border-gray-200 hover:bg-gray-100/70"
          @click="emit('open-logo-picker')"
        >
          <img
            v-if="logoPreview || form.favicon"
            :src="logoPreview || form.favicon"
            alt="Project Logo"
            class="h-full w-full object-contain transition duration-200 group-hover:scale-[0.96] group-hover:opacity-20"
          >

          <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-500">
            <svg
              v-if="logoPreview || form.favicon"
              class="h-5 w-5 opacity-0 transition duration-200 group-hover:opacity-100"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M3 12h18M3 17h18" />
            </svg>
            <span
              v-if="logoPreview || form.favicon"
              class="mt-1 text-[10px] font-medium uppercase tracking-[0.12em] text-gray-500 opacity-0 transition duration-200 group-hover:opacity-100"
            >
              Browse
            </span>
          </div>

          <div v-if="!logoPreview && !form.favicon" class="text-center text-gray-400">
            <svg class="mx-auto h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4" />
            </svg>
            <span class="mt-1 block text-[10px] font-medium uppercase tracking-[0.12em]">Choose</span>
          </div>
        </button>

        <button
          v-if="logoPreview || form.favicon"
          type="button"
          class="absolute -top-2 -right-2 z-10 rounded-full border-2 border-white bg-rose-500 p-0.5 text-white shadow-sm transition hover:bg-rose-600"
          title="Remove Logo"
          @click.stop="emit('remove-logo')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div>
        <h3 class="text-lg font-bold leading-tight text-gray-900">{{ form.name || 'Project Name' }}</h3>
        <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ form.tagline || 'Your catchy tagline will appear here.' }}</p>
      </div>
    </div>

    <div class="relative mb-4 flex aspect-video items-center justify-center overflow-hidden rounded-lg border border-gray-100 bg-gray-50 group">
      <img v-if="galleryPreviews && galleryPreviews[0]" :src="galleryPreviews[0]" class="h-full w-full object-cover">
      <div v-else class="text-center p-4">
        <div class="mx-auto mb-2 h-8 w-12 rounded bg-gray-200"></div>
        <div class="mx-auto mb-1 h-2 w-20 rounded bg-gray-200"></div>
        <div class="mx-auto h-2 w-12 rounded bg-gray-200"></div>
      </div>
    </div>

    <div class="flex flex-wrap gap-2">
      <span
        v-for="catId in (form.categories || []).slice(0, 3)"
        :key="catId"
        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800"
      >
        {{ allCategories.find(c => c.id === catId)?.name || 'Category' }}
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  form: {
    type: Object,
    required: true,
  },
  logoPreview: {
    type: String,
    default: '',
  },
  galleryPreviews: {
    type: Array,
    default: () => [],
  },
  allCategories: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['open-logo-picker', 'remove-logo']);
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[80] flex items-center justify-center bg-gray-950/45 p-3 sm:p-4"
      @click.self="emit('close')"
    >
      <div class="flex max-h-[calc(100vh-1.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]">
        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-4 py-3 sm:px-5 sm:py-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Logo Picker</p>
            <h2 class="mt-1 text-lg font-semibold text-gray-900">Choose the best logo for {{ productName }}</h2>
            <p class="mt-1 text-sm text-gray-500">Choose an extracted logo or upload your own file.</p>
          </div>
          <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-gray-300 hover:text-gray-700"
            @click="emit('close')"
          >
            <span class="sr-only">Close</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="grid flex-1 gap-4 overflow-y-auto px-4 py-4 sm:px-5 sm:py-5 lg:grid-cols-[200px,1fr]">
          <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-medium uppercase tracking-[0.14em] text-gray-400">Selected</p>
            <div class="mt-3 flex h-24 w-24 items-center justify-center rounded-2xl border border-gray-200 bg-white shadow-sm">
              <img
                v-if="currentLogo"
                :src="currentLogo"
                :alt="`${productName} selected logo`"
                class="h-16 w-16 object-contain"
              >
              <div v-else class="text-center text-gray-400">
                <svg class="mx-auto h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2 1.586-1.586a2 2 0 012.828 0L20 14m-9-4h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-2 text-xs">No logo selected</p>
              </div>
            </div>

            <div class="mt-4 space-y-2">
              <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                @click="openFilePicker"
              >
                Upload Logo
              </button>
              <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="isLoading"
                @click="emit('refresh-logos')"
              >
                {{ isLoading ? 'Looking for logos...' : 'Find More Logos' }}
              </button>
              <button
                v-if="favicon"
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                @click="emit('restore-favicon')"
              >
                Use Site Icon
              </button>
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between gap-3">
              <p class="text-sm font-medium text-gray-700">Extracted logos</p>
              <span class="text-xs text-gray-400">{{ logoOptions.length }} found</span>
            </div>

            <div
              v-if="isLoading && logoOptions.length === 0"
              class="mt-3 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center"
            >
              <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-sky-500"></div>
              <p class="mt-3 text-sm text-gray-500">Searching for additional logos...</p>
            </div>

            <div
              v-else-if="logoOptions.length === 0"
              class="mt-3 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center"
            >
              <p class="text-sm font-medium text-gray-700">No extracted alternatives yet</p>
              <p class="mt-1 text-sm text-gray-500">You can upload a logo or run extraction again.</p>
            </div>

            <div v-else class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
              <button
                v-for="option in logoOptions"
                :key="option.url"
                type="button"
                class="group rounded-2xl border bg-white p-2.5 text-left transition"
                :class="option.url === currentLogo ? 'border-sky-500 shadow-sm shadow-sky-100' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                @click="emit('select-logo', option.url)"
              >
                <div class="flex h-16 items-center justify-center rounded-xl bg-gray-50">
                  <img
                    :src="option.url"
                    :alt="`${productName} logo option`"
                    class="h-12 w-12 object-contain"
                  >
                </div>
                <div class="mt-2.5 flex items-center justify-between gap-2">
                  <div>
                    <p class="text-sm font-medium text-gray-800">{{ option.label }}</p>
                    <p class="text-xs text-gray-400">{{ option.helper }}</p>
                  </div>
                  <span
                    v-if="option.url === currentLogo"
                    class="inline-flex items-center rounded-full bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700"
                  >
                    Selected
                  </span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>

      <input
        ref="fileInput"
        type="file"
        accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp,image/avif,.jpg,.jpeg,.png,.gif,.svg,.webp,.avif"
        class="hidden"
        @change="handleFileChange"
      >
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  currentLogo: {
    type: String,
    default: '',
  },
  logos: {
    type: Array,
    default: () => [],
  },
  favicon: {
    type: String,
    default: '',
  },
  productName: {
    type: String,
    default: 'this product',
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'select-logo', 'upload-logo', 'refresh-logos', 'restore-favicon']);
const fileInput = ref(null);

const logoOptions = computed(() => {
  const seen = new Set();
  const options = [];
  const rawOptions = [...props.logos, ...(props.favicon ? [props.favicon] : [])].filter(Boolean);

  rawOptions.forEach((url, index) => {
    if (seen.has(url)) {
      return;
    }

    seen.add(url);

    const isSiteIcon = url === props.favicon;
    options.push({
      url,
      label: isSiteIcon ? 'Site icon' : `Option ${options.length + 1}`,
      helper: isSiteIcon ? 'Fallback favicon' : index === 0 ? 'Auto-selected best match' : 'Extracted alternative',
    });
  });

  return options;
});

const handleEscape = (event) => {
  if (event.key === 'Escape') {
    emit('close');
  }
};

watch(
  () => props.show,
  (isOpen) => {
    document.body.classList.toggle('overflow-hidden', isOpen);

    if (isOpen) {
      window.addEventListener('keydown', handleEscape);
    } else {
      window.removeEventListener('keydown', handleEscape);
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-hidden');
  window.removeEventListener('keydown', handleEscape);
});

function openFilePicker() {
  fileInput.value?.click();
}

function handleFileChange(event) {
  const [file] = event.target.files || [];
  if (file) {
    emit('upload-logo', file);
  }

  event.target.value = '';
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[80] flex items-center justify-center bg-gray-950/45 p-3 sm:p-4"
      @click.self="emit('close')"
    >
      <div class="flex w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-4 py-3 sm:px-5 sm:py-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Screenshot</p>
            <h2 class="mt-1 text-lg font-semibold text-gray-900">Update product screenshot</h2>
            <p class="mt-1 text-sm text-gray-500">Upload a new screenshot or remove the current one.</p>
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

        <div class="space-y-5 px-4 py-4 sm:px-5 sm:py-5">
          <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
            <div class="flex aspect-video items-center justify-center">
              <img
                v-if="currentScreenshot"
                :src="currentScreenshot"
                alt="Current product screenshot"
                class="h-full w-full object-cover"
              >
              <div v-else class="px-6 text-center text-gray-400">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14m-9-8h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                </svg>
                <p class="mt-3 text-sm font-medium text-gray-500">No screenshot selected</p>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap gap-3">
            <div class="relative min-w-[220px]">
              <div class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold leading-none text-white transition hover:bg-slate-800">
                Upload Screenshot
              </div>
              <input
                type="file"
                accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp,image/avif,.jpg,.jpeg,.png,.gif,.svg,.webp,.avif"
                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                aria-label="Upload Screenshot"
                @change="handleFileChange"
                @input="handleFileChange"
              >
            </div>
            <button
              v-if="currentScreenshot"
              type="button"
              class="inline-flex min-w-[220px] items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold leading-none text-slate-700 transition hover:border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-200"
              @click="emit('remove-screenshot')"
            >
              Remove Screenshot
            </button>
          </div>

          <p v-if="uploadError" class="text-sm text-red-600">{{ uploadError }}</p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  currentScreenshot: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close', 'upload-screenshot', 'remove-screenshot']);

const uploadError = ref('');
const supportedImageMimeTypes = [
  'image/jpeg',
  'image/png',
  'image/gif',
  'image/svg+xml',
  'image/webp',
  'image/avif',
  'image/avif-sequence',
];
const supportedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif'];

const handleEscape = (event) => {
  if (event.key === 'Escape') {
    emit('close');
  }
};

watch(
  () => props.show,
  (isOpen) => {
    uploadError.value = '';
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

function handleFileChange(event) {
  const [file] = event.target.files || [];
  uploadError.value = '';

  if (!file) {
    return;
  }

  const normalizedMimeType = (file.type || '').toLowerCase();
  const fileName = file.name || '';
  const normalizedExtension = fileName.includes('.') ? fileName.split('.').pop().toLowerCase() : '';
  const isSupportedFile = supportedImageMimeTypes.includes(normalizedMimeType)
    || supportedImageExtensions.includes(normalizedExtension);

  if (!isSupportedFile) {
    uploadError.value = 'Unsupported image format. Please upload JPG, PNG, GIF, SVG, WEBP, or AVIF.';
    event.target.value = '';
    return;
  }

  emit('upload-screenshot', file);
  event.target.value = '';
}
</script>

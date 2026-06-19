<template>
  <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="mb-3 flex items-start justify-between gap-4">
      <p class="text-xs font-bold text-gray-900">Logo <span class="text-red-500">*</span></p>
      <p v-if="validationErrors.logo" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.logo }}</p>
    </div>
    <div class="mb-4 flex items-start gap-4">
      <div id="field-logo" class="relative group">
        <button
          type="button"
          :class="logoPreview || form.favicon
            ? 'border border-gray-100 bg-gray-50 hover:border-gray-200 hover:bg-gray-100/70'
            : 'border-2 border-dashed border-gray-300 bg-transparent hover:border-gray-400'"
          class="relative flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl transition"
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
            <svg class="mx-auto h-5 w-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4" />
            </svg>
            <span class="mt-1 block text-[10px] font-medium uppercase tracking-[0.12em] text-gray-400">Choose</span>
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
    <div class="mb-4">
      <input
        ref="screenshotInput"
        type="file"
        :accept="supportedImageAcceptList"
        class="hidden"
        @change="onScreenshotChange"
      >

      <div class="relative group">
        <button
          type="button"
          :class="screenshotPreview
            ? 'border border-gray-100 bg-gray-50 hover:border-gray-200 hover:bg-gray-100/70'
            : 'min-h-[220px] border-2 border-dashed border-gray-300 bg-transparent hover:border-gray-400'"
          class="relative flex aspect-video w-full items-center justify-center overflow-hidden rounded-xl transition"
          @click="openScreenshotPicker"
        >
          <img
            v-if="screenshotPreview"
            :src="screenshotPreview"
            alt="Product screenshot"
            class="h-full w-full object-cover transition duration-200 group-hover:scale-[0.985] group-hover:opacity-20"
          >

          <div
            v-if="screenshotPreview"
            class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 opacity-0 transition duration-200 group-hover:opacity-100"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M3 12h18M3 17h18" />
            </svg>
            <span class="mt-1 text-[10px] font-medium uppercase tracking-[0.12em]">Change</span>
          </div>

          <div
            v-if="!screenshotPreview"
            class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center text-gray-400"
          >
            <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2l1.586-1.586a2 2 0 0 1 2.828 0L20 14m-9-8h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
            </svg>
            <p class="mt-4 text-sm font-medium text-gray-500">
              We’ll try to auto-fetch a screenshot first, or
              <span class="text-gray-500 underline underline-offset-2">browse</span>
            </p>
            <p class="mt-2 text-xs text-gray-400">
              Supports JPG, JPEG, PNG, GIF, SVG, WEBP, and AVIF
            </p>
          </div>
        </button>

      </div>

      <p v-if="screenshotUploadError" class="mt-3 text-sm text-red-600">{{ screenshotUploadError }}</p>
    </div>

    <div class="mb-4 border-t border-gray-100 pt-4">
      <label for="sidebar-video-url" class="block text-xs font-bold text-gray-900">Video URL</label>
      <p class="mt-1 text-xs text-gray-500">Add a YouTube video link to showcase your product</p>
      <input
        id="sidebar-video-url"
        type="url"
        :value="displayVideoUrl"
        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm placeholder-gray-400 focus:border-sky-400 focus:outline-none focus:ring-sky-400"
        placeholder="https://youtube.com/watch?v=..."
        @input="updateField('video_url', $event.target.value)"
      >
      <div v-if="videoThumbnailUrl" class="mt-3 overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
        <img :src="videoThumbnailUrl" alt="Video thumbnail preview" class="h-auto w-full object-contain">
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
import { computed, ref } from 'vue';

const props = defineProps({
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
  validationErrors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['open-logo-picker', 'remove-logo', 'upload-screenshot', 'update:modelValue']);

const screenshotInput = ref(null);
const screenshotUploadError = ref('');
const supportedImageAcceptList = 'image/jpeg,image/png,image/gif,image/svg+xml,image/webp,image/avif,.jpg,.jpeg,.png,.gif,.svg,.webp,.avif';
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

const screenshotPreview = computed(() => props.galleryPreviews?.[0] || '');
const displayVideoUrl = computed(() => {
  let url = props.form.video_url;
  if (!url) return '';

  if (typeof url === 'string' && (url.startsWith('{') || url.startsWith('"'))) {
    try {
      if (url.startsWith('"')) {
        url = JSON.parse(url);
      }

      const parsed = typeof url === 'string' ? JSON.parse(url) : url;

      if (parsed && typeof parsed === 'object') {
        if (parsed.embed_url) {
          return parsed.embed_url;
        }

        if (parsed.url) {
          return parsed.url;
        }
      }

      return url;
    } catch (error) {
      console.error('Error parsing video URL JSON:', error);
      return url;
    }
  }

  return url;
});

const videoThumbnailUrl = computed(() => {
  const url = displayVideoUrl.value;
  if (!url) return '';

  let videoId = '';
  if (url.includes('youtube.com/watch')) {
    const params = new URLSearchParams(url.split('?')[1] || '');
    videoId = params.get('v') || '';
  } else if (url.includes('youtu.be/')) {
    videoId = url.split('youtu.be/')[1]?.split('?')[0] || '';
  }

  return videoId ? `https://img.youtube.com/vi/${videoId}/hqdefault.jpg` : '';
});

const openScreenshotPicker = () => {
  screenshotInput.value?.click();
};

const onScreenshotChange = (event) => {
  const file = event.target.files?.[0];
  screenshotUploadError.value = '';

  if (!file) {
    return;
  }

  const normalizedMimeType = (file.type || '').toLowerCase();
  const fileName = file.name || '';
  const normalizedExtension = fileName.includes('.') ? fileName.split('.').pop().toLowerCase() : '';
  const isSupportedFile =
    supportedImageMimeTypes.includes(normalizedMimeType) ||
    supportedImageExtensions.includes(normalizedExtension);

  if (!isSupportedFile) {
    screenshotUploadError.value = 'Unsupported image format. Please upload JPG, PNG, GIF, SVG, WEBP, or AVIF.';
    event.target.value = '';
    return;
  }

  emit('upload-screenshot', file);
  event.target.value = '';
};

const updateField = (field, value) => {
  emit('update:modelValue', { ...props.form, [field]: value });
};
</script>

<template>
  <div>
    <div class="space-y-6">
      <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-4">
            <button
              type="button"
              class="flex h-16 w-16 flex-shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 transition hover:border-gray-300 hover:bg-gray-100"
              @click="emit('open-logo-picker')"
            >
              <img
                v-if="logoPreview || modelValue.favicon"
                :src="logoPreview || modelValue.favicon"
                alt="Selected logo"
                class="h-12 w-12 object-contain"
              >
              <svg v-else class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4" />
              </svg>
            </button>

            <div>
              <label class="block text-sm font-semibold text-gray-700">Logo</label>
              <p class="mt-1 text-xs text-gray-500">
                We auto-pick the best match. Click the logo to browse other extracted options or upload your own.
              </p>
              <p v-if="loadingStates?.logos" class="mt-2 text-xs font-medium text-sky-600">
                Searching for additional logos...
              </p>
              <p v-else-if="availableLogoCount > 0" class="mt-2 text-xs text-gray-400">
                {{ availableLogoCount }} extracted {{ availableLogoCount === 1 ? 'logo' : 'logos' }} available.
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
              @click="emit('open-logo-picker')"
            >
              Browse logos
            </button>
            <button
              v-if="logoPreview || modelValue.favicon"
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 transition hover:border-gray-300 hover:bg-gray-50"
              @click="removeLogo"
            >
              Remove
            </button>
          </div>
        </div>
      </div>

      <div>
        <label for="video-url" class="block text-sm font-semibold text-gray-700">Video URL</label>
        <p class="mb-2 text-xs text-gray-500">Add a YouTube video link to showcase your product.</p>
        <input
          id="video-url"
          type="url"
          :value="getDisplayVideoUrl"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-gray-400 focus:border-sky-400 focus:outline-none focus:ring-sky-400"
          placeholder="https://youtube.com/watch?v=..."
          @input="updateField('video_url', $event.target.value)"
        >
        <div v-if="videoThumbnailUrl" class="mt-4">
          <img :src="videoThumbnailUrl" class="h-auto w-full rounded-md object-contain">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Gallery</label>
        <p class="mb-2 text-xs text-gray-500">
          The first image will be used as the social preview when your link is shared online. We recommend at least 3 or more images.
        </p>
        <p v-if="galleryUploadError" class="mb-2 text-sm text-red-600">{{ galleryUploadError }}</p>

        <div v-if="largePreview" class="my-4">
          <img :src="largePreview" class="h-auto w-full rounded-md object-contain">
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="relative">
            <div class="flex h-32 items-center justify-center rounded-md border-2 border-dashed border-gray-300">
              <input
                type="file"
                :accept="supportedImageAcceptList"
                class="hidden"
                :ref="el => { if (el) galleryInputs[i - 1] = el }"
                @change="onGalleryChange($event, i - 1)"
              >

              <div
                v-if="galleryPreviews[i - 1]"
                class="h-full w-full cursor-pointer"
                @click="showLargePreview(galleryPreviews[i - 1])"
              >
                <img :src="galleryPreviews[i - 1]" class="h-full w-full rounded-md object-cover">
                <button
                  type="button"
                  class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded-full bg-gray-500 p-1 text-xs text-white"
                  @click.stop="removeGalleryImage(i - 1)"
                >
                  &times;
                </button>
              </div>

              <button
                v-else
                type="button"
                class="text-gray-500 transition hover:text-gray-600"
                @click="() => galleryInputs[i - 1] && galleryInputs[i - 1].click()"
              >
                <svg class="mx-auto h-12 w-12" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                  <path d="M28 8H12a4 4 0 0 0 -4 4v20m32-12v8m0 0v8a4 4 0 0 1 -4 4H12a4 4 0 0 1 -4 -4v-4m32-4l-3.172-3.172a4 4 0 0 0 -5.656 0L28 28M8 32l9.172-9.172a4 4 0 0 1 5.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span class="mt-2 block text-sm font-medium">Upload image</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  modelValue: Object,
  logoPreview: String,
  galleryPreviews: Array,
  loadingStates: Object,
});

const emit = defineEmits(['update:modelValue', 'update:logoPreview', 'update:galleryPreviews', 'open-logo-picker']);

onMounted(() => {
  if (!props.logoPreview && !props.modelValue.logo && props.modelValue.favicon) {
    setFaviconAsLogo();
  }
});

const galleryInputs = ref([]);
const largePreview = ref(null);
const galleryUploadError = ref('');
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

const availableLogoCount = computed(() => {
  const uniqueLogos = new Set([...(props.modelValue?.logos || []), props.modelValue?.favicon].filter(Boolean));
  return uniqueLogos.size;
});

const videoThumbnailUrl = computed(() => {
  const displayUrl = getDisplayVideoUrl.value;
  if (!displayUrl) return '';

  let videoId;
  if (displayUrl.includes('youtube.com/watch')) {
    const params = new URLSearchParams(displayUrl.split('?')[1]);
    videoId = params.get('v');
  } else if (displayUrl.includes('youtu.be/')) {
    videoId = displayUrl.split('youtu.be/')[1].split('?')[0];
  }

  return videoId ? `https://img.youtube.com/vi/${videoId}/hqdefault.jpg` : '';
});

const getDisplayVideoUrl = computed(() => {
  let url = props.modelValue.video_url;
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

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}

function removeLogo() {
  emit('update:logoPreview', null);
  updateField('logo', null);
  updateField('favicon', null);
}

function setFaviconAsLogo() {
  emit('update:logoPreview', props.modelValue.favicon);
}

function onGalleryChange(event, index) {
  const file = event.target.files[0];
  galleryUploadError.value = '';

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
    galleryUploadError.value = 'Unsupported image format. Please upload JPG, PNG, GIF, SVG, WEBP, or AVIF.';
    event.target.value = '';
    return;
  }

  const gallery = [...props.modelValue.gallery];
  gallery[index] = file;
  updateField('gallery', gallery);

  const reader = new FileReader();
  reader.onload = (loadEvent) => {
    const previews = [...props.galleryPreviews];
    previews[index] = loadEvent.target.result;
    emit('update:galleryPreviews', previews);
  };
  reader.readAsDataURL(file);
}

function removeGalleryImage(index) {
  if (largePreview.value === props.galleryPreviews[index]) {
    largePreview.value = null;
  }

  const gallery = [...props.modelValue.gallery];
  gallery[index] = null;
  updateField('gallery', gallery);

  const previews = [...props.galleryPreviews];
  previews[index] = null;
  emit('update:galleryPreviews', previews);
}

function showLargePreview(image) {
  largePreview.value = image;
}
</script>

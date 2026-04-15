<template>
  <div>
    <!-- <div class="mb-4">
      
    </div> -->
    <div class="space-y-6">
      <!-- Video URL section (Full width now) -->
      <div>
        <label for="video-url" class="block text-sm font-semibold text-gray-700">Video URL</label>
        <p class="text-xs text-gray-500 mb-2">Add a YouTube video link to showcase your product.</p>
        <input type="url" id="video-url" :value="getDisplayVideoUrl" @input="updateField('video_url', $event.target.value)" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm" placeholder="https://youtube.com/watch?v=...">
        <div v-if="videoThumbnailUrl" class="mt-4">
          <img :src="videoThumbnailUrl" class="w-full h-auto object-contain rounded-md">
        </div>
      </div>


      <!-- Gallery Upload -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Gallery</label>
        <p class="text-xs text-gray-500 mb-2">The first image will be used as the social preview when your link is shared online. We recommend at least 3 or more images.</p>
        <p v-if="galleryUploadError" class="text-sm text-red-600 mb-2">{{ galleryUploadError }}</p>
        <div v-if="largePreview" class="my-4">
          <img :src="largePreview" class="w-full h-auto object-contain rounded-md">
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="relative">
            <div class="flex items-center justify-center h-32 border-2 border-dashed border-gray-300 rounded-md">
              <input type="file" @change="onGalleryChange($event, i - 1)" :accept="supportedImageAcceptList" class="hidden" :ref="el => { if (el) galleryInputs[i - 1] = el }">
              <div v-if="galleryPreviews[i - 1]" class="w-full h-full cursor-pointer" @click="showLargePreview(galleryPreviews[i - 1])">
                <img :src="galleryPreviews[i - 1]" class="h-full w-full object-cover rounded-md">
                <button type="button" @click.stop="removeGalleryImage(i - 1)" class="absolute top-1 right-1 bg-gray-500 text-white rounded-full p-1 text-xs w-6 h-6 flex items-center justify-center">&times;</button>
              </div>
              <button type="button" v-else @click="() => galleryInputs[i - 1] && galleryInputs[i - 1].click()" class="text-gray-500 hover:text-gray-600">
                <svg class="mx-auto h-12 w-12" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 0 0 -4 4v20m32-12v8m0 0v8a4 4 0 0 1 -4 4H12a4 4 0 0 1 -4 -4v-4m32-4l-3.172-3.172a4 4 0 0 0 -5.656 0L28 28M8 32l9.172-9.172a4 4 0 0 1 5.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                <span class="mt-2 block text-sm font-medium">Upload image</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    <!-- Navigation Buttons Removed for Single Step Form -->
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { getTabProgress } from '../../services/productFormService';

const props = defineProps({
  modelValue: Object,
 logoPreview: String,
  galleryPreviews: Array,
 loadingStates: Object,
});

const emit = defineEmits(['update:modelValue', 'update:logoPreview', 'update:galleryPreviews', 'back', 'next', 'extractLogos']);

const progress = computed(() => getTabProgress('imagesAndMedia', props.modelValue, props.logoPreview));

onMounted(() => {
  // If no logo is currently set, and a favicon exists, set the favicon as the default logo
  if (!props.logoPreview && !props.modelValue.logo && props.modelValue.favicon) {
    setFaviconAsLogo();
  }
});

// Computed property for dynamic logo extraction status message
const logoExtractionStatusMessage = computed(() => {
  const messages = [
    "Extracting possible logo images...",
    "Scanning for logos...",
    "Found one logo...",
    "Looking for more logos...",
    "Processing logos..."
  ];
  
  // Use a simple timer to cycle through messages every 2 seconds
  const messageIndex = Math.floor(Date.now() / 2000) % messages.length;
  return messages[messageIndex];
});

// Function to trigger logo extraction
function extractLogos() {
  // Emit an event to trigger logo extraction in the parent component
  emit('extractLogos');
}

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

const videoThumbnailUrl = computed(() => {
  // Get the display URL using our helper function
  const displayUrl = getDisplayVideoUrl.value;
  if (!displayUrl) return '';

  let videoId;
  if (displayUrl.includes('youtube.com/watch')) {
    const params = new URLSearchParams(displayUrl.split('?')[1]);
    videoId = params.get('v');
  } else if (displayUrl.includes('youtu.be/')) {
    videoId = displayUrl.split('youtu.be/')[1].split('?')[0];
  }

  if (videoId) {
    return `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
  }
  return '';
});

const getDisplayVideoUrl = computed(() => {
  let url = props.modelValue.video_url;
  if (!url) return '';

  // Handle JSON encoded video_url
  if (typeof url === 'string' && (url.startsWith('{') || url.startsWith('"'))) {
    try {
      // If it starts with a quote, it might be double-encoded JSON
      if (url.startsWith('"')) {
        url = JSON.parse(url);
      }
      
      const parsed = typeof url === 'string' ? JSON.parse(url) : url;
      
      if (parsed && typeof parsed === 'object') {
        if (parsed.embed_url) {
          return parsed.embed_url;
        } else if (parsed.url) {
          return parsed.url;
        }
      }
      return url; // Return parsed string or original if no specific field found
    } catch (e) {
      // If parsing fails, return the original value
      console.error('Error parsing video URL JSON:', e);
      return url;
    }
  }

  // If it's not a JSON string, return as is
  return url;
});

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}

function onLogoChange(e) {
  const file = e.target.files[0];
  if (file) {
    updateField('logo', file);
    const reader = new FileReader();
    reader.onload = (e) => {
      emit('update:logoPreview', e.target.result);
    };
    reader.readAsDataURL(file);
  }
}

function setLogo(logo) {
  emit('update:logoPreview', logo);
  updateField('logo', logo);
}

function removeLogo() {
  emit('update:logoPreview', null);
  updateField('logo', null);
  updateField('favicon', null);
}

function setFaviconAsLogo() {
  emit('update:logoPreview', props.modelValue.favicon);
}

function onGalleryChange(e, index) {
  const file = e.target.files[0];
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
    e.target.value = '';
    return;
  }

  const gallery = [...props.modelValue.gallery];
  gallery[index] = file;
  updateField('gallery', gallery);

  const reader = new FileReader();
  reader.onload = (e) => {
    const previews = [...props.galleryPreviews];
    previews[index] = e.target.result;
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

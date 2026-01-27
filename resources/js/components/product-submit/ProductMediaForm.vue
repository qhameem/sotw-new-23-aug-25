<template>
  <div>
    <div class="flex justify-between items-center mb-2">
      <h1 class="text-2xl font-bold">Images and Media</h1>
      <button @click="$emit('back')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
        &larr; Back to Main Info
      </button>
    </div>
    <div class="space-y-6 mt-8">
      <!-- Logo Upload and Video URL side by side on desktop -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-semibold text-gray-700">Logo <span class="text-red-500">*</span></label>
          <p class="text-xs text-gray-500 mb-2">Recommended size: 240x240. JPG, PNG, GIF, SVG, WEBP, AVIF allowed.</p>
          <div class="mt-1">
            <div class="relative w-32 h-32">
              <div class="flex items-center justify-center h-full w-full border-2 border-dashed border-gray-30 rounded-md">
                <input type="file" @change="onLogoChange" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp,image/avif" class="hidden" ref="logoInput">
                <div v-if="logoPreview" class="w-full h-full relative group">
                  <img :src="logoPreview" class="h-full w-full object-contain rounded-md p-2 bg-gray-50">
                  <!-- Hover overlay with actions -->
                  <div class="absolute inset-0 bg-black/50 rounded-md opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <button @click.stop="showLargePreview(logoPreview)" class="p-1.5 bg-white text-gray-700 rounded-full hover:bg-gray-100 shadow-sm" title="View bigger">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                      </svg>
                    </button>
                    <button @click.stop="removeLogo" class="p-1.5 bg-white text-rose-500 rounded-full hover:bg-rose-50 shadow-sm" title="Remove logo">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1 -1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
                <button v-else @click="$refs.logoInput.click()" class="text-gray-500 hover:text-gray-600">
                  <svg class="mx-auto h-12 w-12" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 0 0 -4 4v20m32-12v8m0 0v8a4 4 0 0 1 -4 4H12a4 4 0 0 1 -4 -4v-4m32-4l-3.172-3.172a4 4 0 0 0 -5.656 0L28 28M8 32l9.172-9.172a4 4 0 0 1 5.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                  <span class="mt-2 block text-sm font-medium">Upload logo</span>
                </button>
              </div>
            </div>
          </div>
          <div v-if="!logoPreview && modelValue.favicon" class="mt-2">
            <button @click="setFaviconAsLogo" class="text-sm text-rose-500 hover:underline">Set favicon as the logo</button>
          </div>
        </div>
        <!-- Video URL section in second column -->
        <div>
          <label for="video-url" class="block text-sm font-semibold text-gray-70">Video URL</label>
          <input type="url" id="video-url" :value="getDisplayVideoUrl" @input="updateField('video_url', $event.target.value)" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm" placeholder="https://youtube.com/watch?v=...">
          <div v-if="videoThumbnailUrl" class="mt-4">
            <img :src="videoThumbnailUrl" class="w-full h-auto object-contain rounded-md">
          </div>
        </div>
      </div>

      <!-- Suggested Logos -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Suggested Logos</label>
        <div v-if="loadingStates && loadingStates.logos" class="mt-2 flex items-center">
          <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-rose-50 mr-2"></div>
          <span class="text-sm text-gray-600">{{ logoExtractionStatusMessage }}</span>
        </div>
        <div v-else-if="modelValue.logos && modelValue.logos.length > 0" class="mt-2 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          <div v-for="logo in modelValue.logos" :key="logo" @click="setLogo(logo)" class="cursor-pointer border border-gray-200 hover:border-rose-500 rounded-md p-2 flex items-center justify-center h-24 bg-white">
            <img :src="logo" class="max-h-full max-w-full object-contain">
          </div>
          <!-- New Option: Upload a logo -->
          <div @click="$refs.logoInput.click()" class="cursor-pointer border border-dashed border-gray-300 hover:border-rose-500 hover:bg-gray-50 rounded-md p-2 flex flex-col items-center justify-center h-24 text-gray-500 hover:text-rose-500 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span class="text-xs font-medium">Upload Logo</span>
          </div>
        </div>
        <div v-else-if="!loadingStates || !loadingStates.logos" class="mt-2 text-center py-4 border border-dashed border-gray-200 rounded-lg">
          <p class="text-sm text-gray-500 mb-2">No logos found or extracted yet.</p>
          <div class="flex items-center justify-center gap-4">
            <button
              @click="extractLogos"
              class="text-sm text-rose-500 hover:underline font-medium"
            >
              Extract Logos
            </button>
            <span class="text-gray-300">|</span>
            <button
              @click="$refs.logoInput.click()"
              class="text-sm text-gray-600 hover:text-gray-900 font-medium"
            >
              Upload Manually
            </button>
          </div>
        </div>
      </div>

      <!-- Homepage Preview -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Homepage Preview</label>
        <div class="mt-2 p-4 border border-gray-200 rounded-lg bg-gray-50">
          <div class="flex items-center">
            <img :src="logoPreview || modelValue.favicon" class="w-20 h-20 rounded-lg mr-4">
            <div>
              <h3 class="font-bold text-lg">{{ modelValue.name }}</h3>
              <p class="text-gray-600">{{ modelValue.tagline }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Gallery Upload -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Gallery</label>
        <p class="text-xs text-gray-500 mb-2">The first image will be used as the social preview when your link is shared online. We recommend at least 3 or more images.</p>
        <div v-if="largePreview" class="my-4">
          <img :src="largePreview" class="w-full h-auto object-contain rounded-md">
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="relative">
            <div class="flex items-center justify-center h-32 border-2 border-dashed border-gray-300 rounded-md">
              <input type="file" @change="onGalleryChange($event, i - 1)" accept="image/*" class="hidden" :ref="el => { if (el) galleryInputs[i - 1] = el }">
              <div v-if="galleryPreviews[i - 1]" class="w-full h-full cursor-pointer" @click="showLargePreview(galleryPreviews[i - 1])">
                <img :src="galleryPreviews[i - 1]" class="h-full w-full object-cover rounded-md">
                <button @click.stop="removeGalleryImage(i - 1)" class="absolute top-1 right-1 bg-gray-500 text-white rounded-full p-1 text-xs w-6 h-6 flex items-center justify-center">&times;</button>
              </div>
              <button v-else @click="() => galleryInputs[i - 1] && galleryInputs[i - 1].click()" class="text-gray-500 hover:text-gray-600">
                <svg class="mx-auto h-12 w-12" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 0 0 -4 4v20m32-12v8m0 0v8a4 4 0 0 1 -4 4H12a4 4 0 0 1 -4 -4v-4m32-4l-3.172-3.172a4 4 0 0 0 -5.656 0L28 28M8 32l9.172-9.172a4 4 0 0 1 5.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                <span class="mt-2 block text-sm font-medium">Upload image</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="pt-4">
        <div v-if="progress.completed < progress.total" class="text-xs font-semibold text-gray-400 mb-2 transition-all duration-300">
          {{ progress.completed }} of {{ progress.total }} required fields filled
        </div>
        <div v-else class="text-xs font-bold text-green-600 mb-2 flex items-center transition-all duration-300 animate-bounce">
          <span class="mr-1">✓</span> All required media ready!
        </div>

        <button 
          @click="$emit('next')" 
          :class="['group relative w-1/2 flex justify-center py-2 border border-transparent text-sm font-medium rounded-md text-white transition-all duration-300',
            progress.completed === progress.total 
              ? 'bg-rose-600 hover:bg-rose-700 shadow-[0_0_15px_rgba(225,29,72,0.4)] scale-[1.02]' 
              : 'bg-rose-500 hover:bg-rose-600']"
        >
          <span v-if="progress.completed === progress.total" class="flex items-center">
            Ready for Next Step! &nbsp; &#8594;
          </span>
          <span v-else>
            Next Step: Launch &nbsp; &#8594;
          </span>
        </button>
      </div>
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
  if (file) {
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
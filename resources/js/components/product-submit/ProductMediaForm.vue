<template>
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
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: Object,
});

const emit = defineEmits(['update:modelValue']);

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
</script>

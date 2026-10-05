<template>
  <div class="space-y-3 rounded-xl bg-gray-50 p-3">
    <p class="flex items-center gap-2 text-xs font-medium text-gray-600"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 text-primary-700">1</span> Copy the badge</p>
    <div class="flex gap-2" aria-label="Badge preview background">
      <button v-for="theme in ['light', 'dark']" :key="theme" type="button" :aria-pressed="previewTheme === theme" class="rounded-lg border px-3 py-1 text-xs font-medium capitalize focus-visible:ring-2 focus-visible:ring-primary-500" :class="previewTheme === theme ? 'border-primary-400 bg-primary-100 text-primary-700' : 'border-gray-200 bg-white text-gray-600'" @click="previewTheme = theme">{{ theme }}</button>
    </div>
    <div class="flex min-h-24 items-center justify-center rounded-lg border border-dashed border-gray-300 p-4" :class="previewTheme === 'dark' ? 'bg-gray-950' : 'bg-white'">
      <img v-if="previewSrc" :src="previewSrc" alt="Featured on Software on the Web badge" class="max-h-16 max-w-full" @error="imageFailed = true">
      <p v-else class="text-xs" :class="previewTheme === 'dark' ? '!text-gray-300' : '!text-gray-500'">{{ loading ? 'Loading badge…' : 'Badge preview unavailable' }}</p>
    </div>
    <div class="flex min-w-0 items-stretch rounded-lg border border-gray-200 bg-white">
    <input type="text" readonly :value="resolvedSnippet" aria-label="Badge embed code" class="block w-full min-w-0 flex-1 rounded-l-lg border-0 bg-transparent px-3 py-2 font-mono !text-[10px] text-gray-600" placeholder="Loading badge code…" @focus="$event.target.select()">
    <button type="button" :disabled="!resolvedSnippet || loading" aria-label="Copy badge code" class="flex shrink-0 items-center justify-center gap-1.5 rounded-md border border-primary-500 bg-white px-2 py-1 !text-[11px] font-medium text-gray-900 hover:border-primary-400 focus-visible:ring-2 focus-visible:ring-primary-500 disabled:opacity-50" @click="copyCode">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2" /><path d="M16 8V4H4v12h4" /></svg>
      {{ copied ? 'Copied' : 'Copy badge code' }}
    </button>
    </div>
    <p v-if="message" role="status" class="text-xs text-gray-500">{{ message }}</p>
    <slot />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({ snippet: { type: String, default: '' } });
const previewTheme = ref('light');
const fetchedSnippet = ref('');
const imageUrl = ref('');
const imageFailed = ref(false);
const loading = ref(false);
const copied = ref(false);
const message = ref('');
const resolvedSnippet = computed(() => props.snippet || fetchedSnippet.value);
const previewSrc = computed(() => imageFailed.value ? '' : resolvedSnippet.value.match(/<img[^>]+src=["']([^"']+)["']/i)?.[1]?.replace(/&amp;/g, '&') || imageUrl.value);

onMounted(async () => {
  if (props.snippet) return;
  loading.value = true;
  try {
    const { data } = await axios.get('/api/badge-snippet-preview');
    fetchedSnippet.value = data.snippet || '';
    imageUrl.value = data.badge_image_url || '';
  } catch {
    message.value = 'Badge unavailable. Try again later.';
  } finally {
    loading.value = false;
  }
});

const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(resolvedSnippet.value);
    copied.value = true;
    message.value = '';
  } catch {
    message.value = 'Select and copy the code above.';
  }
};
</script>

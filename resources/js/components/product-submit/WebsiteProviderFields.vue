<template>
  <section class="space-y-3 border-t border-gray-100 pt-4">
    <div class="flex items-center justify-between gap-3">
      <h3 class="text-xs font-bold text-gray-900">Website providers <span class="font-normal text-gray-400">(Optional)</span></h3>
      <button type="button" :disabled="loading || !modelValue.link" @click="lookup" class="text-xs text-sky-700 disabled:opacity-50">
        {{ loading ? 'Looking up…' : 'Look up from URL' }}
      </button>
    </div>
    <p class="text-[11px] text-gray-500">Best-effort DNS, IP registration, ASN, reverse DNS and HTTP-header lookup. Verify detected values or enter them manually. CDNs can hide the host. Lookup shares the domain/IP with Google DNS, registration services and RIPEstat, and requests public website headers.</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div v-for="field in fields" :key="field.key">
        <label :for="field.key" class="mb-1 block text-xs font-bold text-gray-900">{{ field.label }}
          <span v-if="field.key === 'hosting_provider'" class="ml-2 rounded bg-slate-100 px-2 py-1 font-normal text-slate-600">{{ hostingStatus }}</span>
        </label>
        <input :id="field.key" :value="modelValue[field.key] || ''" @input="edit(field.key, $event.target.value)" type="text" maxlength="255"
          class="block w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-xs text-gray-900 focus:border-sky-500 focus:ring-sky-500"
          placeholder="Unknown / optional">
      </div>
    </div>
    <p v-if="modelValue.hosting_details?.cdn_providers?.length" class="text-xs text-amber-700">
      CDN / proxy: {{ modelValue.hosting_details.cdn_providers.join(', ') }}. This does not identify the origin host.
    </p>
    <details v-if="modelValue.hosting_details?.evidence?.length" class="text-xs text-gray-600">
      <summary class="cursor-pointer">Detection evidence</summary>
      <ul class="mt-2 space-y-1 pl-4 list-disc">
        <li v-for="(item, index) in modelValue.hosting_details.evidence" :key="index" class="break-words">
          {{ item.type.toUpperCase() }}: {{ item.value }}<span v-if="item.provider"> — {{ item.provider }}</span>
        </li>
      </ul>
    </details>
    <p v-if="message" role="status" aria-live="polite" class="text-[11px] text-gray-500">{{ message }}</p>
  </section>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({ modelValue: { type: Object, required: true } });
const emit = defineEmits(['update:modelValue']);
const fields = [{ key: 'hosting_provider', label: 'Hosting provider' }, { key: 'domain_registrar', label: 'Domain registrar' }];
const loading = ref(false);
const message = ref('');
const edited = new Set();
const hostingStatus = computed(() => {
  if (!props.modelValue.hosting_provider) return 'Unknown';
  return { inferred: 'Inferred', user_provided: 'User-provided' }[props.modelValue.hosting_details?.status] || 'Unknown';
});
const currentHost = () => {
  try { return new URL(props.modelValue.link).hostname.toLowerCase().replace(/\.$/, ''); } catch { return ''; }
};
let controller;
let timer;
let generation = 0;
let detected = {};

function edit(key, value) {
  edited.add(key);
  const patch = { [key]: value };
  if (key === 'hosting_provider') {
    message.value = '';
    patch.hosting_details = { ...props.modelValue.hosting_details, host: currentHost(), provider: value || null, status: value.trim() ? 'user_provided' : 'unknown' };
  }
  emit('update:modelValue', { ...props.modelValue, ...patch });
}

async function lookup() {
  clearTimeout(timer);
  controller?.abort();
  const current = ++generation;
  const url = props.modelValue.link;
  if (!/^https?:\/\//i.test(url || '')) {
    loading.value = false;
    message.value = url ? 'Enter a website URL starting with https:// or http://.' : '';
    return;
  }
  controller = new AbortController();
  loading.value = true;
  message.value = '';
  try {
    const { data } = await axios.post('/api/website-providers', { url }, { signal: controller.signal, timeout: 90000 });
    if (current !== generation || props.modelValue.link !== url) return;
    const patch = {};
    fields.forEach(({ key }) => {
      if (!edited.has(key) && !props.modelValue[key] && data[key]) {
        patch[key] = data[key];
        detected[key] = data[key];
      }
    });
    const selectedHost = patch.hosting_provider || props.modelValue.hosting_provider || null;
    const manual = edited.has('hosting_provider') || props.modelValue.hosting_details?.status === 'user_provided';
    patch.hosting_details = {
      ...data.hosting_details,
      provider: selectedHost,
      status: !selectedHost ? 'unknown' : manual ? 'user_provided'
        : data.hosting_provider === selectedHost ? 'inferred' : 'unknown',
    };
    emit('update:modelValue', { ...props.modelValue, ...patch });
    message.value = [data.hosting_note, data.domain_registrar ? '' : 'Registrar unavailable.', !selectedHost ? 'Enter the host manually if known.' : manual ? 'Manual hosting entry retained.' : 'Detected values can be edited.'].filter(Boolean).join(' ');
  } catch (error) {
    if (current === generation && !axios.isCancel(error)) message.value = 'Lookup unavailable. You can enter both fields manually or leave them blank.';
  } finally {
    if (current === generation) loading.value = false;
  }
}

watch(() => props.modelValue.link, (url, previousUrl) => {
  clearTimeout(timer);
  controller?.abort();
  generation++;
  loading.value = false;
  message.value = '';
  const patch = {};
  fields.forEach(({ key }) => {
    if (!edited.has(key) && detected[key] && props.modelValue[key] === detected[key]) patch[key] = '';
  });
  if (previousUrl !== undefined || (props.modelValue.hosting_details?.host && props.modelValue.hosting_details.host !== currentHost())) {
    if (props.modelValue.hosting_details?.status === 'inferred' && !edited.has('hosting_provider')) patch.hosting_provider = '';
    const retained = patch.hosting_provider ?? props.modelValue.hosting_provider;
    patch.hosting_details = { host: currentHost(), provider: retained || null, status: retained ? 'user_provided' : 'unknown', cdn_providers: [], evidence: [] };
  }
  detected = {};
  if (Object.keys(patch).length) emit('update:modelValue', { ...props.modelValue, ...patch });
  timer = setTimeout(lookup, 800);
}, { immediate: true });

onBeforeUnmount(() => { clearTimeout(timer); generation++; controller?.abort(); });
</script>

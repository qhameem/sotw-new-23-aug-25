<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <div class="space-y-8">
      <!-- Project Info -->
      <div>
        <div class="flex items-center gap-3 mb-4">
          <h3 class="font-bold text-gray-900">Project Info</h3>
        </div>
        
        <ul class="grid grid-cols-2 gap-x-4 gap-y-3">
          <li v-for="item in step1Items" :key="item.label" class="flex items-center gap-2 text-sm">
             <svg class="w-4 h-4 flex-shrink-0" :class="item.complete ? 'text-sky-500' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
               <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
             </svg>
             <span :class="item.complete ? 'text-gray-700' : 'text-gray-400'">{{ item.label }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  logoPreview: {
    type: String,
    default: ''
  },
  galleryPreviews: {
    type: Array,
    default: () => []
  }
});

const step1Items = computed(() => [
  { label: 'Name', complete: !!props.form.name },
  { label: 'URL', complete: !!props.form.link },
  { label: 'Description', complete: !!props.form.description },
  { label: 'Logo', complete: !!(props.logoPreview || props.form.favicon) },
  { label: 'Product image', complete: !!(props.galleryPreviews && props.galleryPreviews.some(p => !!p)) },
  { label: 'Categories', complete: !!((props.form.categories && props.form.categories.length > 0) || (props.form.categories_custom && props.form.categories_custom.length > 0)) },
  { label: 'Pricing', complete: !!(props.form.pricing && props.form.pricing.length > 0) },
]);
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-2">
      <h1 class="text-2xl font-bold">Makers & Extras</h1>
      <button @click="$emit('back')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
        &larr; Back to Images and Media
      </button>
    </div>
    <p class="mb-8 text-gray-600 text-base">Add links to the builders of this product and additional information.</p>
    
    <div class="space-y-6">
      <!-- Makers' Links Section -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Makers' Links</h3>
        
        <!-- Dynamic maker links -->
        <div v-for="(link, index) in makerLinks" :key="index" class="flex items-center mb-4">
          <input
            type="url"
            :id="`maker-link-${index}`"
            :value="link"
            @input="updateMakerLink(index, $event.target.value)"
            :placeholder="`Link to maker ${index + 1} (e.g., https://twitter.com/username)`"
            class="flex-1 px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
          >
          <button
            v-if="makerLinks.length > 1"
            type="button"
            @click="removeMakerLink(index)"
            class="ml-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-800"
          >
            Remove
          </button>
        </div>
        
        <!-- Add more links button (visible if less than 10 links) -->
        <div v-if="makerLinks.length < 10">
          <button
            type="button"
            @click="addMakerLink"
            class="mt-2 text-sm font-medium text-sky-600 hover:text-sky-800 flex items-center"
          >
            <span>+ Add more links</span>
          </button>
          <p v-if="makerLinks.length >= 9" class="text-xs text-gray-500 mt-1">Maximum 10 links allowed</p>
        </div>
      </div>
      
      <!-- Tech Stack Section -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Tech Stack</h3>
        <SearchableDropdown
          :items="allTechStacks"
          :modelValue="modelValue.tech_stack"
          @update:modelValue="updateField('tech_stack', $event)"
          placeholder="Select technologies..."
          :max="5"
        >
          <template #description>
            <p class="text-xs text-gray-400 mt-1">Select the technologies that were used to develop the product.</p>
          </template>
        </SearchableDropdown>
      </div>
      
      <!-- Sell Product Option -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Product Sale</h3>
        <div class="flex items-center">
          <input
            type="checkbox"
            id="sell-product"
            :checked="modelValue.sell_product || false"
            @change="updateField('sell_product', $event.target.checked)"
            class="h-4 w-4 text-rose-600 border-gray-30 rounded focus:ring-sky-400"
          >
          <label for="sell-product" class="ml-2 block text-sm text-gray-900">I am looking to sell this product</label>
        </div>
        
        <!-- Asking Price Input (shown only if sell_product is true) -->
        <div v-if="modelValue.sell_product" class="mt-4 ml-6">
          <label for="asking-price" class="block text-sm font-semibold text-gray-700 mb-2">Asking Price (USD)</label>
          <input
            type="number"
            id="asking-price"
            :value="modelValue.asking_price || ''"
            @input="updateField('asking_price', $event.target.value)"
            placeholder="Enter price in USD"
            min="0"
            step="0.01"
            class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-40 sm:text-sm"
          >
        </div>
      </div>
      
      <div class="pt-4">
        <button @click="$emit('next')" class="group relative w-1/2 flex justify-center py-2 border border-transparent text-sm font-medium rounded-md text-white bg-rose-500 hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
          Next Step: Launch checklist &rarr;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import SearchableDropdown from '../SearchableDropdown.vue';

const props = defineProps({
  modelValue: Object,
  allTechStacks: Array,
});

const emit = defineEmits(['update:modelValue', 'next', 'back']);

// Initialize maker links from modelValue or start with one empty field
const makerLinks = ref(props.modelValue.maker_links || ['']);

// Watch for changes to makerLinks and update the modelValue
watch(makerLinks, (newLinks) => {
  emit('update:modelValue', { 
    ...props.modelValue, 
    maker_links: newLinks.filter(link => link.trim() !== '') // Only include non-empty links
  });
}, { deep: true });

function addMakerLink() {
  if (makerLinks.value.length < 10) {
    makerLinks.value.push('');
  }
}

function removeMakerLink(index) {
  if (makerLinks.value.length > 1) {
    makerLinks.value.splice(index, 1);
  }
}

function updateMakerLink(index, value) {
  makerLinks.value[index] = value;
}

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}
</script>
<template>
  <div>
    <div class="flex justify-between items-center mb-2">
      <h1 class="text-2xl font-bold">Extras</h1>
      <button @click="$emit('back')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
        &larr; Back to Makers
      </button>
    </div>
    <div class="space-y-6 mt-8">
      <SearchableDropdown
        label="Tech Stack"
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
      
      <!-- Sell Product Option -->
      <div>
        <div class="flex items-center">
          <input
            type="checkbox"
            id="sell-product"
            :checked="modelValue.sell_product || false"
            @change="updateField('sell_product', $event.target.checked)"
            class="h-4 w-4 text-rose-600 border-gray-300 rounded focus:ring-sky-400"
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
    </div>
    
    <div class="pt-4">
      <button @click="$emit('next')" class="group relative w-1/2 flex justify-center py-2 border border-transparent text-sm font-medium rounded-md text-white bg-rose-500 hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
        Next Step: Launch checklist &rarr;
      </button>
    </div>
  </div>
</template>

<script setup>
import SearchableDropdown from '../SearchableDropdown.vue';

const props = defineProps({
  modelValue: Object,
  allTechStacks: Array,
});

const emit = defineEmits(['update:modelValue', 'next', 'back']);

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}
</script>
<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-gray-800">Product Preview</h2>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-70">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="flex space-x-4 mb-4">
          <button 
            @click="currentView = 'homepage'" 
            :class="{'bg-rose-500 text-white': currentView === 'homepage', 'bg-gray-200 text-gray-700': currentView !== 'homepage'}"
            class="px-4 py-2 rounded-md font-medium"
          >
            Homepage View
          </button>
          <button 
            @click="currentView = 'details'" 
            :class="{'bg-rose-500 text-white': currentView === 'details', 'bg-gray-200 text-gray-700': currentView !== 'details'}"
            class="px-4 py-2 rounded-md font-medium"
          >
            Details Page View
          </button>
        </div>
        
        <div class="border rounded-lg p-4 bg-gray-50">
          <template v-if="currentView === 'homepage'">
            <!-- Homepage preview matching the actual site design -->
            <div class="product-card p-4 flex items-center gap-2 md:gap-1 transition relative group hover:bg-stone-50 rounded-lg" data-product-id="1">
              <div class="flex items-center gap-3 flex-1">
                <a href="#" class="flex items-start md:items-center gap-2">
                  <span class="hidden md:block text-xs text-gray-500">1.</span>
                  <img
                    :src="product.logoPreview || (product.logos && product.logos.length > 0 ? product.logos[0] : '')"
                    :alt="product.name + ' logo'"
                    class="size-16 rounded-xl object-cover flex-shrink-0"
                  >
                  <div class="flex flex-col space-y-1">
                    <h2 class="text-base font-semibold flex items-center leading-none">
                      <span class="text-left text-black mt-1">{{ product.name }}</span>
                      <a href="#"
                         @click.stop
                         class="ml-2 p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-full hover:bg-gray-100"
                         aria-label="Open product link in new tab">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                      </a>
                    </h2>
                    
                    <p class="text-gray-900 text-sm line-clamp-2">{{ product.tagline }}</p>
                    
                    <div class="flex flex-wrap gap-2 items-center">
                      <template v-for="(category, index) in product.categories.slice(0, 3)" :key="category">
                        <a href="#"
                           @click.stop
                           class="hidden sm:block inline-flex items-center text-gray-600 hover:text-gray-80 rounded text-xs">
                          <span class="hover:underline">{{ category }}</span>
                        </a>
                        <span v-if="index < product.categories.slice(0, 3).length - 1" class="hidden sm:inline text-gray-400">•</span>
                      </template>
                    </div>
                  </div>
                </a>
              </div>
              <div class="flex-shrink-0 flex items-center gap-2">
                <div class="text-xs text-gray-500">0 impressions</div>
                <button class="text-gray-600 hover:text-rose-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 0 1 5.656 0L10 6.343l1.172-1.171a4 4 0 1 1 5.656 5.656L10 17.657l-6.828-6.829a4 4 0 0 1 0-5.656z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </template>
          <template v-else-if="currentView === 'details'">
            <!-- Details page preview matching the actual site design -->
            <div class="space-y-6">
              <div class="bg-white rounded-lg p-6">
              <div class="flex items-center mb-4">
                <a href="#" class="flex items-start gap-2">
                  <img
                    :src="product.logoPreview || (product.logos && product.logos.length > 0 ? product.logos[0] : '')"
                    :alt="product.name + ' logo'"
                    class="size-20 object-contain rounded-lg mr-3"
                  >
                  <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ product.name }}</h1>
                    <p class="text-gray-800 text-base">
                      <strong>Tagline:</strong> {{ product.tagline }}
                    </p>
                    <p class="text-gray-800 text-base">
                      <strong>Product Page Tagline:</strong> {{ product.tagline_detailed }}
                    </p>
                    <div class="flex flex-wrap items-center mt-1">
                      <template v-for="(category, index) in product.categories.filter(cat => !isPricingCategory(cat) && !isBestForCategory(cat))" :key="category">
                        <a href="#" class="text-xs text-gray-500 hover:underline">{{ category }}</a>
                        <span v-if="index < product.categories.filter(cat => !isPricingCategory(cat) && !isBestForCategory(cat)).length - 1" class="text-gray-400 mx-2">&middot;</span>
                      </template>
                    </div>
                  </div>
                </a>
              </div>
            
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                  <div class="flex flex-col gap-1">
                    <div class="text-xs font-medium">
                      Publisher
                    </div>
                    <div class="flex flex-row">
                      <div>
                        <img src="https://ui-avatars.com/api/?name=Anonymous&color=7F9CF5&background=EBF4FF" alt="Anonymous" class="size-5 rounded-full mr-1 border">
                      </div>
                      <div class="text-gray-700 text-xs content-center">
                        Anonymous
                      </div>
                    </div>
                  </div>
                <div class="flex items-center space-x-4">
                  <a href="#"
                     class="inline-flex items-center px-4 py-1.5 text-sm font-semibold text-gray-70 bg-white border border-gray-30 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Visit Website &nbsp;
                    <svg class="size-4 stroke-gray-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M7 17L17 7M17 7H8M17 7V16" stroke="" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                  </a>
                  <button class="text-gray-600 hover:text-rose-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 0 1 5.656 0L10 6.343l1.172-1.171a4 4 0 1 1 5.656 5.656L10 17.657l-6.828-6.829a4 4 0 0 1 0-5.656z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
              </div>
            
              <div class="prose max-w-none text-sm ql-editor-content" v-html="product.description"></div>
              
              <!-- Best for section -->
              <div v-if="product.bestFor && product.bestFor.length > 0" class="mt-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Best for</h3>
                <div class="flex flex-wrap gap-2">
                  <template v-for="bestFor in product.bestFor" :key="bestFor">
                    <span class="inline-flex items-center px-2.5 py-1 bg-gray-10 text-gray-70 text-xs font-medium rounded-full hover:bg-gray-200">
                      {{ bestFor }}
                    </span>
                  </template>
                </div>
              </div>
              
              <!-- Pricing Model section -->
              <div v-if="product.pricing && product.pricing.length > 0" class="mt-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Pricing Model</h3>
                <p class="text-sm text-gray-600">{{ getPricingLabel(product.pricing[0]) }}</p>
              </div>
              
              <!-- Tech Stack section -->
              <div v-if="product.tech_stack && product.tech_stack.length > 0" class="mt-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Tech Stack</h3>
                <div class="flex flex-wrap gap-2">
                  <template v-for="techStack in product.tech_stack" :key="techStack">
                    <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                      {{ techStack }}
                    </span>
                  </template>
                </div>
              
            </div>
            </div>
            
            <!-- Makers' links -->
              <div v-if="product.maker_links && product.maker_links.length > 0" class="mt-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-2">Makers</h3>
                <ul class="mt-2 space-y-2">
                  <li v-for="(link, index) in product.maker_links" :key="index" class="text-gray-700">
                    <a :href="link" target="_blank" class="text-sky-600 hover:text-sky-800 underline break-all">{{ link }}</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </template>
        
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            @click="closeModal" 
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            Close
          </button>
          <button 
            @click="confirmSubmit" 
            class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-rose-500 hover:bg-rose-600"
          >
            Confirm & Submit
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  show: Boolean,
  product: Object,
 allPricing: Array,
});

const emit = defineEmits(['close', 'confirm']);

const currentView = ref('homepage');

const closeModal = () => {
  currentView.value = 'homepage';
  emit('close');
};

const confirmSubmit = () => {
  emit('confirm');
};

const getPricingLabel = (pricingId) => {
  const pricingOption = props.allPricing.find(p => p.id === pricingId);
  return pricingOption ? pricingOption.name : `Pricing ${pricingId}`;
};

const isPricingCategory = (category) => {
  // This would check if the category is a pricing category
  // For now, we'll just return false since we don't have the full category data
 // In a real implementation, this would check against the category types
  return false;
};

const isBestForCategory = (category) => {
 // This would check if the category is a best for category
  // For now, we'll just return false since we don't have the full category data
 // In a real implementation, this would check against the category types
  return false;
};
</script>
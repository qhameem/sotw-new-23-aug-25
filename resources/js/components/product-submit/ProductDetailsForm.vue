<template>
  <div>
    <div class="flex justify-between items-center mb-2">
      <h1 class="text-2xl font-bold">Tell us more about this product</h1>
      <button @click="$emit('back')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
        &larr; Back
      </button>
    </div>
    <p class="mb-8 text-gray-600 text-base">We'll need its name, tagline, links, launch tags, and description.</p>
    <div class="space-y-6">
      <!-- Basic Information Section -->
      <div>
        <div class="mb-6">
          <div class="flex justify-between">
            <div class="flex items-center">
              <label for="name" class="block text-sm font-semibold text-gray-700">Name of the product <span class="text-red-500">*</span></label>
              <Tooltip content="Enter the official name of your product. This will be displayed prominently on the product page." />
            </div>
            <span class="text-sm text-gray-400">{{ (modelValue.name || '').length }}/40</span>
          </div>
          <div class="relative">
            <input ref="nameInput" type="text" id="name" :value="modelValue.name" @input="updateProductName($event.target.value)" maxlength="40" class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm" :class="{'opacity-50 pointer-events-none': loadingStates.name}">
            <div v-if="loadingStates.name" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="animate-spin h-5 w-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
          <p v-if="extractionErrors.name" class="mt-1 text-xs text-red-500">{{ extractionErrors.name }}</p>
          <!-- URL slug displayed directly below the name field -->
          <div class="group relative mt-2 text-xs text-gray-600 inline-block">
            softwareontheweb.com/{{ generatedSlug }}
            <span class="absolute hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 ml-2 -mt-1 z-10">
              URL slug is automatically generated from the product name
            </span>
          </div>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative z-10">
          <div class="flex justify-between">
            <div class="flex items-center">
                <label for="tagline" class="block text-sm font-semibold text-gray-700">Tagline <span class="text-red-500">*</span></label>
                <Tooltip content="Enter a short, memorable phrase that describes your product. This appears on the main listing page." />
              </div>
              <span class="text-sm text-gray-400">{{ (modelValue.tagline || '').length }}/60</span>
          </div>
          <div class="relative">
            <textarea id="tagline" :value="modelValue.tagline" @input="updateField('tagline', $event.target.value)" maxlength="60" rows="3" class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm min-h-[5em]" :class="{'opacity-50 pointer-events-none': loadingStates.name}"></textarea>
            <div v-if="loadingStates.name" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="animate-spin h-5 w-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
          <p v-if="extractionErrors.tagline" class="mt-1 text-xs text-red-500">{{ extractionErrors.tagline }}</p>
        </div>
        <div class="relative z-10">
          <div class="flex justify-between items-center">
            <div class="flex items-center">
              <label for="tagline_detailed" class="block text-sm font-semibold text-gray-700">Tagline for product details page <span class="text-red-500">*</span></label>
              <Tooltip content="A more detailed tagline that appears on the product's detail page. This should expand on the main tagline." />
            </div>
            <span class="text-sm text-gray-400">{{ (modelValue.tagline_detailed || '').length }}/160</span>
          </div>
          <div class="relative">
            <textarea id="tagline_detailed" data-field="tagline_detailed" :value="modelValue.tagline_detailed" @input="updateField('tagline_detailed', $event.target.value)" maxlength="160" rows="3" class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm min-h-[6em]" :class="{'opacity-50 pointer-events-none': loadingStates.name}"></textarea>
            <div v-if="loadingStates.name" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="animate-spin h-5 w-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
          <p v-if="extractionErrors.tagline" class="mt-1 text-xs text-red-500">{{ extractionErrors.tagline }}</p>
        </div>
      </div>
      
      <div class="relative shadow-sm focus-within:ring-1 focus-within:ring-sky-400 focus-within:ring-offset-0 rounded-md" :class="{'opacity-50 pointer-events-none': loadingStates.description}">
        <div class="flex items-center">
          <label for="description" class="block text-sm font-semibold text-gray-700">Description <span class="text-red-500">*</span></label>
          <Tooltip content="Provide a detailed description of your product. This will appear on the product page and help users understand what your product does." />
          <svg v-if="loadingStates.description" class="animate-spin h-4 w-4 text-sky-500 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
        <WysiwygEditor :modelValue="modelValue.description" @update:modelValue="updateField('description', $event)" :maxLength="1200" />
        <p v-if="extractionErrors.description" class="mt-1 text-xs text-red-500">{{ extractionErrors.description }}</p>
      </div>
      
      <hr class="border-t border-gray-200 my-6">
      
      <!-- Links Section -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Links</h3>
        
        <!-- Link to the product (disabled input) -->
        <div class="mb-4">
          <label for="product-link" class="block text-sm font-semibold text-gray-700 mb-2">Link to the product</label>
          <div class="relative">
            <input
              type="url"
              id="product-link"
              ref="productLinkRef"
              :value="modelValue.link"
              disabled
              class="mt-1 block w-full px-3 py-2 bg-gray-100 text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm pr-10"
            >
            <button
              type="button"
              @click="copyToClipboard(modelValue.link)"
              ref="copyButtonRef"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
              title="Copy link to clipboard"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Additional links section -->
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <button
              type="button"
              @click="addMoreLink"
              class="text-sm font-medium text-sky-600 hover:text-sky-800 flex items-center"
            >
              <span>+ Add more links</span>
            </button>
          </div>
          
          <!-- Dynamic additional links -->
          <div v-for="(link, index) in modelValue.additionalLinks || []" :key="index" class="flex items-center mb-2">
            <input
              type="url"
              :id="`additional-link-${index}`"
              :value="link"
              @input="updateAdditionalLink(index, $event.target.value)"
              placeholder="https://example.com"
              class="flex-1 px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
            >
            <button
              type="button"
              @click="removeLink(index)"
              class="ml-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-800"
            >
              Remove
            </button>
          </div>
        </div>
        
        <!-- X account of the product -->
        <div>
          <label for="x-account" class="block text-sm font-semibold text-gray-700 mb-2">X account of the product</label>
          <input
            type="url"
            id="x-account"
            :value="modelValue.x_account || ''"
            @input="updateField('x_account', $event.target.value)"
            placeholder="https://twitter.com/username or https://x.com/username"
            class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
          >
        </div>
      </div>
      
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative">
          <div class="flex justify-between items-center mb-1">
           <div class="flex items-center">
              <label class="block text-sm font-semibold text-gray-700">Category <span class="text-red-500">*</span></label>
              <Tooltip content="Select the main categories that best describe your product. This helps users find your product through filtering." />
              <svg v-if="loadingStates.categories" class="animate-spin h-4 w-4 text-sky-500 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
          <SearchableDropdown :items="allCategories" :modelValue="modelValue.categories" @update:modelValue="updateField('categories', $event)" placeholder="Select categories..." :min="1" :disabled="loadingStates.categories" />
          <p v-if="extractionErrors.categories" class="mt-1 text-xs text-red-500">{{ extractionErrors.categories }}</p>
        </div>
        <div class="relative">
          <div class="flex justify-between items-center mb-1">
            <div class="flex items-center">
              <label class="block text-sm font-semibold text-gray-700">Best for <span class="text-red-500">*</span></label>
              <Tooltip content="Select the groups or individuals who would benefit most from your product. This helps with targeting." />
              <svg v-if="loadingStates.bestFor" class="animate-spin h-4 w-4 text-sky-500 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
          <SearchableDropdown :items="allBestFor" :modelValue="modelValue.bestFor" @update:modelValue="updateField('bestFor', $event)" placeholder="Select who this is best for..." :min="1" :disabled="loadingStates.bestFor" />
          <p v-if="extractionErrors.bestFor" class="mt-1 text-xs text-red-500">{{ extractionErrors.bestFor }}</p>
        </div>
      </div>
      <div>
        <div class="flex items-center mb-4">
          <label class="block text-sm font-semibold text-gray-700">Pricing model of the product <span class="text-red-500">*</span><span class="text-gray-400 text-xs font-light"> (Select minimum one)</span></label>
          <Tooltip content="Select the pricing models that apply to your product. This helps users understand how your product is priced." />
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="price in allPricing" :key="price.id" class="flex items-center">
            <input :id="`price-${price.id}`" type="checkbox" :value="price.id" :checked="modelValue.pricing.includes(price.id)" @change="updatePricing" class="h-4 w-4 text-rose-600 border-gray-300 rounded focus:ring-sky-400">
            <label :for="`price-${price.id}`" class="ml-2 block text-sm text-gray-900">{{ price.name }}</label>
          </div>
        </div>
      </div>
    </div>
    <div class="pt-4">
        <div v-if="progress.completed < progress.total" class="text-xs font-semibold text-gray-400 mb-2 transition-all duration-300">
          {{ progress.completed }} of {{ progress.total }} required fields filled
        </div>
        <div v-else class="text-xs font-bold text-green-600 mb-2 flex items-center transition-all duration-300 animate-bounce">
          <span class="mr-1">✓</span> All required fields filled!
        </div>
        
        <button 
          @click="$emit('next')" 
          :class="['group relative w-1/2 flex justify-center items-center py-2 border border-transparent text-base font-medium rounded-md text-white transition-all duration-300',
            progress.completed === progress.total 
              ? 'bg-rose-600 hover:bg-rose-700 shadow-[0_0_15px_rgba(225,29,72,0.4)] scale-[1.02]' 
              : 'bg-rose-500 hover:bg-rose-600']"
        > 
          <span v-if="progress.completed === progress.total" class="flex items-center">
            Ready for Next Step! &nbsp; &#8594;
          </span>
          <span v-else>
            Next Step: Images and media &nbsp; &#8594;
          </span>
        </button>
      </div>
  </div>
</template>

<script setup>
import SearchableDropdown from '../SearchableDropdown.vue';
import WysiwygEditor from '../WysiwygEditor.vue';
import Tooltip from '../Tooltip.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { getTabProgress } from '../../services/productFormService';

const props = defineProps({
  modelValue: Object,
 allCategories: Array,
 allBestFor: Array,
  allPricing: Array,
  loadingStates: {
    type: Object,
    default: () => ({})
  },
  extractionErrors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:modelValue', 'next', 'back']);

const nameInput = ref(null);
const productLinkRef = ref(null);
const copyButtonRef = ref(null);







onMounted(() => {
  nameInput.value?.focus();
});

const progress = computed(() => getTabProgress('mainInfo', props.modelValue, null));

// Function to generate slug from product name
function generateSlug(name) {
  if (!name) return '';
  return name
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '') // Remove special characters
    .replace(/[\s_-]+/g, '-') // Replace spaces, underscores, and multiple hyphens with a single hyphen
    .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
}

// Computed property for the generated slug
const generatedSlug = computed(() => {
  return generateSlug(props.modelValue.name);
});

// Function to update product name and slug
function updateProductName(value) {
  // Generate slug from the new name
  const slug = generateSlug(value);
  emit('update:modelValue', {
    ...props.modelValue,
    name: value,
    slug: slug // Also update the slug in the model
 });
}

function updateField(field, value) {
  console.log('[ProductDetailsForm] updateField called:', { field, value, currentValue: props.modelValue[field] });
  const updatedModel = { ...props.modelValue, [field]: value };
  console.log('[ProductDetailsForm] Emitting update:', updatedModel);
  emit('update:modelValue', updatedModel);
}

function updatePricing(event) {
  const { value, checked } = event.target;
  const pricing = [...props.modelValue.pricing];
  if (checked) {
    pricing.push(parseInt(value));
  } else {
    const index = pricing.indexOf(parseInt(value));
    if (index > -1) {
      pricing.splice(index, 1);
    }
  }
  updateField('pricing', pricing);
}

// Additional links functionality
const addMoreLink = () => {
  const currentLinks = props.modelValue.additionalLinks || [];
  const newLinks = [...currentLinks, ''];
  updateField('additionalLinks', newLinks);
};

const removeLink = (index) => {
  const currentLinks = props.modelValue.additionalLinks || [];
  const newLinks = [...currentLinks];
  newLinks.splice(index, 1);
  updateField('additionalLinks', newLinks);
};

const updateAdditionalLink = (index, value) => {
  const currentLinks = props.modelValue.additionalLinks || [];
  const newLinks = [...currentLinks];
  newLinks[index] = value;
  updateField('additionalLinks', newLinks);
};

// Function to copy text to clipboard
function copyToClipboard(text) {
  if (text) {
    navigator.clipboard.writeText(text).then(() => {
      // Optional: Show a temporary success indicator
      const button = copyButtonRef.value;
      if (button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        `;
        
        // Revert back to original icon after 2 seconds
        setTimeout(() => {
          button.innerHTML = originalHTML;
        }, 2000);
      }
    }).catch(err => {
      console.error('Failed to copy: ', err);
    });
  }
}
</script>
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
      <div>
        <div class="flex justify-between">
          <div class="flex items-center">
            <label for="name" class="block text-sm font-semibold text-gray-700">Name of the product</label>
            <Tooltip content="Enter the official name of your product. This will be displayed prominently on the product page." />
          </div>
          <span class="text-sm text-gray-400">{{ modelValue.name.length }}/40</span>
        </div>
        <div class="relative">
          <input type="text" id="name" :value="modelValue.name" @input="updateProductName($event.target.value)" maxlength="40" class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-40 sm:text-sm">
        </div>
      </div>
    <div>
      <div class="flex justify-between">
        <div class="flex items-center">
          <label for="slug" class="block text-sm font-semibold text-gray-700">URL slug</label>
          <Tooltip content="This will be automatically generated from the product name. This appears in the URL for your product page." />
        </div>
      </div>
      <div class="relative">
        <input type="text" id="slug" :value="generatedSlug" readonly class="mt-1 block w-full px-3 py-2 bg-gray-100 text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm">
      </div>
    </div>
    <div>
      <div class="flex justify-between">
        <div class="flex items-center">
            <label for="tagline" class="block text-sm font-semibold text-gray-70">Tagline</label>
            <Tooltip content="Enter a short, memorable phrase that describes your product. This appears on the main listing page." />
          </div>
          <span class="text-sm text-gray-400">{{ modelValue.tagline.length }}/60</span>
        </div>
        <div class="relative">
          <input type="text" id="tagline" :value="modelValue.tagline" @input="updateField('tagline', $event.target.value)" maxlength="60" class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm">
        </div>
      </div>
      <div>
        <div class="flex justify-between items-center">
          <div class="flex items-center">
            <label for="tagline_detailed" class="block text-sm font-semibold text-gray-700">Tagline for product details page</label>
            <Tooltip content="A more detailed tagline that appears on the product's detail page. This should expand on the main tagline." />
          </div>
          <span class="text-sm text-gray-400">{{ modelValue.tagline_detailed.length }}/160</span>
        </div>
        <div class="relative">
          <textarea id="tagline_detailed" :value="modelValue.tagline_detailed" @input="updateField('tagline_detailed', $event.target.value)" maxlength="160" rows="3" class="mt-1 block w-full px-3 py-2 bg-white border text-gray-600 border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm min-h-[6em]"></textarea>
        </div>
      </div>
      
      <hr class="border-t border-gray-200 my-6">
      
      <!-- Links Section -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Links</h3>
        
        <!-- Link to the product (disabled input) -->
        <div class="mb-4">
          <label for="product-link" class="block text-sm font-semibold text-gray-700 mb-2">Link to the product</label>
          <input
            type="url"
            id="product-link"
            :value="modelValue.link"
            disabled
            class="mt-1 block w-full px-3 py-2 bg-gray-100 text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
          >
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
      
      <hr class="border-t border-gray-200 my-6">
      
      <div class="relative shadow-sm focus-within:outline-sky-40">
        <div class="flex items-center">
          <label for="description" class="block text-sm font-semibold text-gray-700">Description</label>
          <Tooltip content="Provide a detailed description of your product. This will appear on the product page and help users understand what your product does." />
        </div>
        <WysiwygEditor :modelValue="modelValue.description" @update:modelValue="updateField('description', $event)" :maxLength="500" />
      </div>
      
      <hr class="border-t border-gray-200 my-6">
      
      <div class="relative">
        <div>
          <div class="flex items-center">
            <label class="block text-sm font-semibold text-gray-70">Category</label>
            <Tooltip content="Select the main categories that best describe your product. This helps users find your product through filtering." />
          </div>
          <SearchableDropdown :items="allCategories" :modelValue="modelValue.categories" @update:modelValue="updateField('categories', $event)" placeholder="Select categories..." :min="1" :max="3" />
       </div>
      </div>
      <div class="relative">
        <div class="flex items-center">
          <SearchableDropdown label="Best for" :items="allBestFor" :modelValue="modelValue.bestFor" @update:modelValue="updateField('bestFor', $event)" placeholder="Select who this is best for..." :min="1" :max="3" />
          <Tooltip content="Select the groups or individuals who would benefit most from your product. This helps with targeting." />
        </div>
      </div>
      <div>
        <div class="flex items-center mb-4">
          <label class="block text-sm font-semibold text-gray-700">Pricing model of the product<span class="text-gray-400 text-xs font-light"> (Select minimum one)</span></label>
          <Tooltip content="Select the pricing models that apply to your product. This helps users understand how your product is priced." />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div v-for="price in allPricing" :key="price.id" class="flex items-center">
            <input :id="`price-${price.id}`" type="checkbox" :value="price.id" :checked="modelValue.pricing.includes(price.id)" @change="updatePricing" class="h-4 w-4 text-rose-600 border-gray-300 rounded focus:ring-sky-400">
            <label :for="`price-${price.id}`" class="ml-2 block text-sm text-gray-900">{{ price.name }}</label>
          </div>
        </div>
      </div>
      <div class="pt-4">
        <button @click="$emit('next')" class="group relative w-3/4 flex justify-center items-center py-2 border border-transparent text-base font-medium rounded-md text-white bg-rose-500 hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500"> 
          Next Step: Images and media &nbsp; &#8594;

        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import SearchableDropdown from '../SearchableDropdown.vue';
import WysiwygEditor from '../WysiwygEditor.vue';
import Tooltip from '../Tooltip.vue';
import { computed } from 'vue';

const props = defineProps({
  modelValue: Object,
 allCategories: Array,
  allBestFor: Array,
  allPricing: Array,
});

const emit = defineEmits(['update:modelValue', 'next', 'back']);

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
  emit('update:modelValue', { ...props.modelValue, [field]: value });
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
</script>
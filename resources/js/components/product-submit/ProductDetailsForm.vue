<template>
  <div class="space-y-8 mt-4">
    
    <!-- Project Name -->
    <div>
      <div class="flex justify-between mb-1">
        <label for="name" class="block text-xs font-bold text-gray-900">Project Name <span class="text-red-500">*</span></label>
        <span class="text-xs text-gray-400">{{ (modelValue.name || '').length }}/40</span>
      </div>
      <input 
        ref="nameInput" 
        type="text" 
        id="name" 
        :value="modelValue.name" 
        @input="updateProductName($event.target.value)" 
        maxlength="40" 
        placeholder="e.g. Smooth Capture"
        class="block w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
        :class="{'opacity-50 pointer-events-none': loadingStates.name}"
      >
      <!-- Slug preview -->
      <div v-if="generatedSlug" class="mt-2 text-xs text-gray-500 flex items-center gap-1">
        <span class="text-gray-400">softwareontheweb.com/product/</span>
        <span class="font-medium text-gray-700">{{ generatedSlug }}</span>
      </div>
      <p v-if="extractionErrors.name" class="mt-1 text-xs text-red-500">{{ extractionErrors.name }}</p>
    </div>

    <!-- Taglines (Grouped) -->
     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
           <div class="flex justify-between mb-1">
             <label for="tagline" class="block text-xs font-bold text-gray-900">Tagline <span class="text-red-500">*</span></label>
             <span class="text-xs text-gray-400">{{ (modelValue.tagline || '').length }}/140</span>
           </div>
           <textarea 
             id="tagline" 
             :value="modelValue.tagline" 
             @input="updateField('tagline', $event.target.value)" 
             maxlength="140" 
             rows="2" 
             class="block w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
           ></textarea>
        </div>
        <div>
           <div class="flex justify-between mb-1">
             <label for="tagline_detailed" class="block text-xs font-bold text-gray-900">Detailed Tagline <span class="text-red-500">*</span></label>
             <span class="text-xs text-gray-400">{{ (modelValue.tagline_detailed || '').length }}/160</span>
           </div>
           <textarea 
             id="tagline_detailed" 
             :value="modelValue.tagline_detailed" 
             @input="updateField('tagline_detailed', $event.target.value)" 
             maxlength="160" 
             rows="2" 
             class="block w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
           ></textarea>
        </div>
     </div>

    <!-- Description -->
    <div class="relative" :class="{'opacity-50 pointer-events-none': loadingStates.description}">
        <div class="flex items-center justify-between mb-2">
          <label class="block text-xs font-bold text-gray-900">Description <span class="text-red-500">*</span></label>
          <div v-if="loadingStates.description" class="flex items-center text-xs text-sky-600">
             <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
             Generating...
          </div>
        </div>
        <div class="prose-editor-wrapper border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-sky-500 focus-within:border-transparent transition-all">
           <WysiwygEditor :modelValue="modelValue.description" @update:modelValue="updateField('description', $event)" />
        </div>
        <p v-if="extractionErrors.description" class="mt-1 text-xs text-red-500">{{ extractionErrors.description }}</p>
    </div>

    <!-- Categories (Chip Selection) -->
    <div>
       <div class="flex items-center justify-between mb-3">
          <label class="block text-xs font-bold text-gray-900">Categories <span class="text-red-500">*</span> <span class="text-gray-400 font-normal text-xs ml-1">(Max 3)</span></label>
          <div v-if="loadingStates.categories" class="animate-pulse h-2 w-20 bg-gray-200 rounded"></div>
       </div>

       <!-- Category Search -->
       <div class="relative mb-3">
         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
           <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
           </svg>
         </div>
         <input 
           type="text" 
           v-model="categorySearch" 
           placeholder="Search categories..." 
           class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
           :class="{'pr-32': showAddCategoryButton, 'pr-8': !showAddCategoryButton && categorySearch.length >= 2}"
         >
         <button
           v-if="categorySearch.length >= 2 && !showAddCategoryButton"
           type="button"
           @click="categorySearch = ''"
           class="absolute inset-y-0 right-0 px-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
         >
           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
         </button>
         <button
           v-if="showAddCategoryButton"
           type="button"
           @click="addCustomCategoryFromSearch"
           class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
         >
           + Add "{{ categorySearch.trim() }}"
         </button>
       </div>
       
       <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
          <button 
            v-for="category in filteredCategories" 
            :key="category.id"
            type="button"
            @click="toggleCategory(category.id)"
            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
            :class="modelValue.categories.includes(category.id) 
              ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm' 
              : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
          >
            {{ category.name }}
            <svg v-if="modelValue.categories.includes(category.id)" class="ml-1.5 h-3 w-3 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
          <div v-if="filteredCategories.length === 0 && !showAddCategoryButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
             No categories found matching "{{ categorySearch }}"
          </div>
       </div>
       <p v-if="modelValue.categories.length === 0 && (!modelValue.categories_custom || modelValue.categories_custom.length === 0)" class="mt-2 text-xs text-gray-400">Please select at least one category or add a custom category.</p>
       
       <!-- Display selected custom categories -->
       <div v-if="modelValue.categories_custom && modelValue.categories_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
         <span
           v-for="customCat in modelValue.categories_custom"
           :key="customCat.id"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
         >
           {{ customCat.name }} (pending)
           <button
             type="button"
             @click="removeCustomCategory(customCat.id)"
             class="ml-2 text-purple-500 hover:text-purple-700"
           >
             &times;
           </button>
         </span>
       </div>
    </div>



    <!-- Best For / Tags (Chip Selection) -->
    <div>
       <div class="flex items-center justify-between mb-3">
          <label class="block text-xs font-bold text-gray-900">Tags / Best For <span class="text-gray-400 font-normal text-xs ml-1">(Max 5)</span></label>
       </div>

       <!-- Tag Search -->
       <div class="relative mb-3">
         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
           <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
           </svg>
         </div>
         <input
           type="text"
           v-model="tagSearch"
           placeholder="Search tags..."
           class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
           :class="{'pr-32': showAddTagButton, 'pr-8': !showAddTagButton && tagSearch.length >= 2}"
         >
         <button
           v-if="tagSearch.length >= 2 && !showAddTagButton"
           type="button"
           @click="tagSearch = ''"
           class="absolute inset-y-0 right-0 px-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
         >
           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
         </button>
         <button
           v-if="showAddTagButton"
           type="button"
           @click="addCustomTagFromSearch"
           class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
         >
           + Add "{{ tagSearch.trim() }}"
         </button>
       </div>
       
       <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
          <button
            v-for="item in filteredBestFor"
            :key="item.id"
            type="button"
            @click="toggleBestFor(item.id)"
            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
            :class="modelValue.bestFor.includes(item.id)
              ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm'
              : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
          >
            {{ item.name }}
             <span v-if="modelValue.bestFor.includes(item.id)" class="ml-1.5 text-sky-600 font-bold">&times;</span>
          </button>
          <div v-if="filteredBestFor.length === 0 && !showAddTagButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
             No tags found matching "{{ tagSearch }}"
          </div>
       </div>
       
       <!-- Display selected custom tags -->
       <div v-if="modelValue.bestFor_custom && modelValue.bestFor_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
         <span
           v-for="customTag in modelValue.bestFor_custom"
           :key="customTag.id"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
         >
           {{ customTag.name }} (pending)
           <button
             type="button"
             @click="removeCustomTag(customTag.id)"
             class="ml-2 text-purple-500 hover:text-purple-700"
           >
             &times;
           </button>
         </span>
       </div>
    </div>

    <!-- Pricing (Cards) -->
    <div>
       <label class="block text-xs font-bold text-gray-900 mb-3">Pricing <span class="text-red-500">*</span></label>
       <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div 
            v-for="price in allPricing" 
            :key="price.id"
            @click="togglePricing(price.id)"
            class="cursor-pointer relative rounded-full border px-4 py-2 transition-all duration-200 hover:shadow-md flex flex-col justify-between h-full"
            :class="modelValue.pricing.includes(price.id)
              ? 'bg-sky-50 border-sky-500'
              : 'bg-white border-gray-200 hover:border-sky-300'"
          >
             <div class="flex items-start justify-between">
                <span class="font-medium text-xs text-gray-700">{{ price.name }}</span>
                <div class="h-4 w-4 rounded-full border flex items-center justify-center"
                     :class="modelValue.pricing.includes(price.id) ? 'bg-sky-500 border-sky-500' : 'border-gray-300'"
                >
                   <svg v-if="modelValue.pricing.includes(price.id)" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                   </svg>
                </div>
             </div>
             <!-- Optional description for pricing if we had it -->
             <!-- <p class="text-xs text-gray-500">Description here...</p> -->
          </div>
       </div>
    </div>

    <!-- Pricing Page URL -->
    <div>
      <div class="flex justify-between mb-1">
        <label for="pricing_page_url" class="block text-xs font-bold text-gray-900">Pricing Page URL <span class="text-gray-400 font-normal text-xs ml-1">(Optional)</span></label>
      </div>
      <input 
        type="url" 
        id="pricing_page_url" 
        :value="modelValue.pricing_page_url || ''" 
        @input="updateField('pricing_page_url', $event.target.value)" 
        placeholder="https://example.com/pricing"
        class="block w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
      >
      <p class="mt-1 text-xs text-gray-500">Provide a direct link to your pricing page for better visibility.</p>
    </div>

    <!-- Social Links -->
    <div class="pt-4 border-t border-gray-100">
        <label class="block text-xs font-bold text-gray-900 mb-4">Social Links</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                 <label class="block text-xs font-bold text-gray-900 mb-1">Twitter / X</label>
                 <input 
                    type="url" 
                    :value="modelValue.x_account || ''"
                    @input="updateField('x_account', $event.target.value)"
                    placeholder="https://x.com/username"
                    class="block w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
                 >
            </div>
            <!-- Maker Links / GitHub etc could go here if we expand the model -->
        </div>
         
         <!-- Dynamic Maker Links (Existing functionality preserved but styled) -->
         <div class="mt-4">
             <div class="flex justify-between items-center mb-2">
                 <label class="block text-xs font-bold text-gray-900">Other Links (GitHub, LinkedIn, etc.)</label>
                 <button type="button" @click="addMoreLink" class="text-xs font-bold text-sky-600 hover:text-sky-700 flex items-center">
                    <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Link
                 </button>
             </div>
             <div class="space-y-2">
                 <div v-for="(link, index) in makerLinks" :key="index" class="flex items-center gap-2">
                    <input
                      type="url"
                      :value="link"
                      @input="updateMakerLink(index, $event.target.value)"
                      placeholder="https://..."
                      class="block flex-1 px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
                    >
                    <button type="button" @click="removeLink(index)" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1 -1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" /></svg>
                    </button>
                 </div>
             </div>
         </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import WysiwygEditor from '../WysiwygEditor.vue';
import { getTabProgress } from '../../services/productFormService';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
    default: () => ({
      name: '',
      tagline: '',
      tagline_detailed: '',
      description: '',
      categories: [],
      categories_custom: [], // Custom categories
      bestFor: [],
      bestFor_custom: [], // Custom bestFor
      pricing: [],
      pricing_page_url: '',
      link: '',
      maker_links: [],
      x_account: ''
    })
  },
  allCategories: { type: Array, default: () => [] },
  allBestFor: { type: Array, default: () => [] },
  allPricing: { type: Array, default: () => [] },
  loadingStates: { type: Object, default: () => ({}) },
  extractionErrors: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['update:modelValue']);

onMounted(() => {
  console.log('[ProductDetailsForm] Mounted. Initial modelValue:', props.modelValue);
  if (props.allCategories.length > 0) {
     console.log('[ProductDetailsForm] First category ID type:', typeof props.allCategories[0].id, props.allCategories[0].id);
  }
});

watch(() => props.modelValue, (newVal) => {
  console.log('[ProductDetailsForm] modelValue updated:', newVal);
  if (newVal.categories.length > 0) {
      console.log('[ProductDetailsForm] modelValue.categories[0] type:', typeof newVal.categories[0], newVal.categories[0]);
  }
}, { deep: true });

// Local refs
const nameInput = ref(null);
const makerLinks = ref(props.modelValue.maker_links?.length ? [...props.modelValue.maker_links] : ['']);
const categorySearch = ref('');
const tagSearch = ref('');
const techStackSearch = ref(''); // For tech stack search
// Computed: show "Add as custom" button when search text has no exact match
const showAddCategoryButton = computed(() => {
  const search = categorySearch.value.trim();
  if (!search) return false;
  // Don't show if already added as custom
  if (props.modelValue.categories_custom?.some(c => c.name.toLowerCase() === search.toLowerCase())) return false;
  // Don't show if limit reached
  if ((props.modelValue.categories_custom?.length || 0) >= 3) return false;
  // Show if no existing category matches exactly
  return !props.allCategories?.some(cat => cat.name.toLowerCase() === search.toLowerCase());
});

const showAddTagButton = computed(() => {
  const search = tagSearch.value.trim();
  if (!search) return false;
  if (props.modelValue.bestFor_custom?.some(t => t.name.toLowerCase() === search.toLowerCase())) return false;
  if ((props.modelValue.bestFor_custom?.length || 0) >= 5) return false;
  return !props.allBestFor?.some(item => item.name.toLowerCase() === search.toLowerCase());
});

// Computed filtered categories
const filteredCategories = computed(() => {
  if (!props.allCategories) return [];
  
  const search = categorySearch.value.toLowerCase().trim();
  
  // Filter out already selected custom categories
  const existingCustomCategories = props.modelValue.categories_custom.map(c => c.name.toLowerCase());
  
  // Sort and filter: Selected items should be boosted but also match search
  return props.allCategories
    .filter(cat => cat.name.toLowerCase().includes(search) && !existingCustomCategories.includes(cat.name.toLowerCase()))
    .sort((a, b) => {
      const aSelected = props.modelValue.categories.includes(a.id);
      const bSelected = props.modelValue.categories.includes(b.id);
      if (aSelected && !bSelected) return -1;
      if (!aSelected && bSelected) return 1;
      return a.name.localeCompare(b.name);
    });
});

// Computed filtered tags
const filteredBestFor = computed(() => {
  if (!props.allBestFor) return [];
  
  const search = tagSearch.value.toLowerCase().trim();
  
  // Filter out already selected custom tags
  const existingCustomTags = props.modelValue.bestFor_custom.map(t => t.name.toLowerCase());
  
  // Sort and filter: Selected items should be boosted but also match search
  return props.allBestFor
    .filter(item => item.name.toLowerCase().includes(search) && !existingCustomTags.includes(item.name.toLowerCase()))
    .sort((a, b) => {
      const aSelected = props.modelValue.bestFor.includes(a.id);
      const bSelected = props.modelValue.bestFor.includes(b.id);
      if (aSelected && !bSelected) return -1;
      if (!aSelected && bSelected) return 1;
      return a.name.localeCompare(b.name);
    });
});

// Sync makerLinks with props
watch(() => props.modelValue.maker_links, (newVal) => {
  if (JSON.stringify(newVal) !== JSON.stringify(makerLinks.value.filter(l => l.trim() !== ''))) {
    makerLinks.value = newVal?.length ? [...newVal] : (makerLinks.value.length ? makerLinks.value : ['']);
  }
}, { deep: true });

// Sync local makerLinks to model
watch(makerLinks, (newVal) => {
  const filtered = newVal.filter(link => link.trim() !== '');
  if (JSON.stringify(filtered) !== JSON.stringify(props.modelValue.maker_links)) {
    updateField('maker_links', filtered);
  }
}, { deep: true });


const generatedSlug = computed(() => {
  return props.modelValue.name ? generateSlug(props.modelValue.name) : '';
});

function generateSlug(text) {
  return text.toString().toLowerCase().trim()
    .replace(/\s+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-');
}

function updateProductName(value) {
  emit('update:modelValue', {
    ...props.modelValue,
    name: value,
    slug: generateSlug(value)
 });
}

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}

// Chip Logic for Categories
function toggleCategory(id) {
    const current = [...props.modelValue.categories];
    const index = current.indexOf(id);
    if (index === -1) {
        if (current.length >= 3) return; // Max 3 limit
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('categories', current);
    categorySearch.value = ''; // Clear search after selection
}

// Checkbox/Chip Logic for Best For (Tags)
function toggleBestFor(id) {
    const current = [...props.modelValue.bestFor];
    const index = current.indexOf(id);
    if (index === -1) {
        if (current.length >= 5) return; // Max 5 limit
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('bestFor', current);
    tagSearch.value = ''; // Clear search after selection
}

// Card Logic for Pricing
function togglePricing(id) {
    const current = [...props.modelValue.pricing];
    const index = current.indexOf(id);
    if (index === -1) {
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('pricing', current);
}

function addMoreLink() {
   makerLinks.value.push('');
}
function removeLink(index) {
   makerLinks.value.splice(index, 1);
}
function updateMakerLink(index, value) {
   makerLinks.value[index] = value;
}

// Functions to handle custom categories (triggered from search inline button)
function addCustomCategoryFromSearch() {
  const name = categorySearch.value.trim();
  if (!name) return;
  if (props.modelValue.categories_custom?.length >= 3) return;
  
  const newCustomCategory = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };
  
  const updatedCustomCategories = [...(props.modelValue.categories_custom || []), newCustomCategory];
  emit('update:modelValue', { ...props.modelValue, categories_custom: updatedCustomCategories });
  categorySearch.value = '';
}

function removeCustomCategory(customCategoryId) {
  const updatedCustomCategories = props.modelValue.categories_custom.filter(cat => cat.id !== customCategoryId);
  emit('update:modelValue', { ...props.modelValue, categories_custom: updatedCustomCategories });
}

// Functions to handle custom tags/bestFor (triggered from search inline button)
function addCustomTagFromSearch() {
  const name = tagSearch.value.trim();
  if (!name) return;
  if ((props.modelValue.bestFor_custom?.length || 0) >= 5) return;
  
  const newCustomTag = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };
  
  const updatedCustomTags = [...(props.modelValue.bestFor_custom || []), newCustomTag];
  emit('update:modelValue', { ...props.modelValue, bestFor_custom: updatedCustomTags });
  tagSearch.value = '';
}

function removeCustomTag(customTagId) {
  const updatedCustomTags = props.modelValue.bestFor_custom.filter(tag => tag.id !== customTagId);
  emit('update:modelValue', { ...props.modelValue, bestFor_custom: updatedCustomTags });
}

// Function to handle custom tech stack
function addCustomTechStack() {
  if (!customTechStackInput.value.trim()) return;
  
  // Check if already added as custom tech stack
  const alreadyAdded = props.modelValue.techStacks_custom?.some(ts =>
    ts.name.toLowerCase() === customTechStackInput.value.trim().toLowerCase()
  ) || false;
  
  if (alreadyAdded) {
    alert(`Tech stack "${customTechStackInput.value}" is already added as custom tech stack`);
    return;
  }
  
  // Check if we already have 3 custom tech stacks
  const currentCustomTechStacks = props.modelValue.techStacks_custom || [];
  if (currentCustomTechStacks.length >= 3) {
    alert('You can only add up to 3 custom tech stacks');
    return;
  }
  
  const newCustomTechStack = {
    id: `custom-${Date.now()}`, // Temporary ID
    name: customTechStackInput.value.trim(),
    is_custom: true
  };
  
  const updatedCustomTechStacks = [...currentCustomTechStacks, newCustomTechStack];
  emit('update:modelValue', { ...props.modelValue, techStacks_custom: updatedCustomTechStacks });
  
  customTechStackInput.value = '';
}

function removeCustomTechStack(customTechStackId) {
  const currentCustomTechStacks = props.modelValue.techStacks_custom || [];
  const updatedCustomTechStacks = currentCustomTechStacks.filter(ts => ts.id !== customTechStackId);
  emit('update:modelValue', { ...props.modelValue, techStacks_custom: updatedCustomTechStacks });
}

</script>
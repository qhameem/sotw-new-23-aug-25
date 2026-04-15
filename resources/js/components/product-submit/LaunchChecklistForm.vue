<template>
  <div>
    <div class="space-y-6">
      <!-- Back button at top removed for Single Step Form -->
      <div class="mb-2">
        
      </div>
      
      <!-- Makers & Extras Section -->
      <section>
        
        
        <div class="space-y-6">
          <!-- Makers' Links Section -->
          <div>
            <h4 class="text-xs font-bold text-gray-900 mb-3">Makers' Links</h4>
            
            <!-- Dynamic maker links -->
            <div v-for="(link, index) in makerLinks" :key="index" class="flex items-center mb-3">
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
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-bold text-gray-900">Tech Stack <span class="text-gray-400 font-normal text-xs ml-1">(Max 5)</span></h4>
            </div>

            <!-- Tech Stack Search -->
            <div class="relative mb-3">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                type="text"
                v-model="techSearch"
                placeholder="Search technologies..."
                class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
                :class="{'pr-36': showAddTechStackButton}"
              >
              <button
                v-if="showAddTechStackButton"
                type="button"
                @click="addCustomTechStackFromSearch"
                class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
              >
                + Add "{{ techSearch.trim() }}"
              </button>
            </div>

            <p class="text-xs text-gray-400 mb-2">Select the technologies that were used to develop the product.</p>

            <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
              <button
                v-for="tech in filteredTechStacks"
                :key="tech.id"
                type="button"
                @click="toggleTechStack(tech.id)"
                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
                :class="modelValue.tech_stack && modelValue.tech_stack.includes(tech.id)
                  ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm'
                  : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
              >
                {{ tech.name }}
                <svg v-if="modelValue.tech_stack && modelValue.tech_stack.includes(tech.id)" class="ml-1.5 h-3 w-3 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
              <div v-if="filteredTechStacks.length === 0 && !showAddTechStackButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
                No technologies found matching "{{ techSearch }}"
              </div>
            </div>
            
            <!-- Display selected custom tech stacks -->
            <div v-if="modelValue.tech_stack_custom && modelValue.tech_stack_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
              <span
                v-for="customTech in modelValue.tech_stack_custom"
                :key="customTech.id"
                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
              >
                {{ customTech.name }} (pending)
                <button
                  type="button"
                  @click="removeCustomTechStack(customTech.id)"
                  class="ml-2 text-purple-500 hover:text-purple-700"
                >
                  &times;
                </button>
              </span>
            </div>
          </div>
          
          <!-- Sell Product Option -->
          <div>
            <h4 class="text-md font-medium text-gray-700 mb-3">Product Sale</h4>
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
            <div v-if="modelValue.sell_product" class="mt-3 ml-6">
              <label for="asking-price" class="block text-sm font-semibold text-gray-700 mb-2">Asking Price (USD)</label>
              <input
                type="number"
                id="asking-price"
                :value="modelValue.asking_price || ''"
                @input="updateField('asking_price', $event.target.value)"
                placeholder="Enter price in USD"
                min="0"
                step="0.01"
                class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
              >
            </div>
          </div>
        </div>
      </section>
      

      
      <!-- Horizontal separator -->
      <hr class="border-t border-gray-200 my-6">
      
      <!-- Pricing Options / Save Button -->
      <section>
        <!-- Show save button only when editing an existing product (has ID) -->
        <div v-if="!!modelValue.id && !isAdmin" class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Save Changes</h3>
          <p class="text-sm text-gray-600 mb-6">You can save your edits directly without selecting a pricing option.</p>
          <div class="flex flex-col items-start gap-4">
            <div v-if="!isAllRequiredFilled" class="text-sm text-amber-600 font-medium">
              Note: Some required fields are missing, but you can still save.
            </div>
            <button
              @click="$emit('submit')"
              class="px-8 py-3 bg-rose-600 text-white font-bold rounded-lg shadow-md hover:bg-rose-700 transition-all focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
            >
              Save All Changes
            </button>
          </div>
        </div>
        
        <!-- Show pricing options only when creating a new product (no ID) -->
        <div v-else-if="!isAdmin">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Pricing Options</h3>
          <div v-if="progress.completed < progress.total" class="text-xs font-semibold text-gray-400 mb-4 transition-all duration-300">
            {{ progress.completed }} of {{ progress.total }} total required fields filled
          </div>
          <div class="flex flex-wrap gap-6 items-stretch">
            <FreeSubmissionOption
              id="free-option"
              name="pricing-option"
              value="free"
              :modelValue="selectedPricingOption"
              :isAllRequiredFilled="isAllRequiredFilled"
              :isEditMode="!!modelValue.id"
              @update:modelValue="selectedPricingOption = $event"
              title="Free Submission"
              price="$0"
              description="Launch your product for free with a badge"
              :features="freeLaunchFeatures"
              :isLoading="isLoading"
              @submit="handlePricingOptionSubmit"
            />
            
            <BadgeSubmissionOption
              id="badge-option"
              name="pricing-option"
              value="badge"
              :modelValue="selectedPricingOption"
              :isAllRequiredFilled="isAllRequiredFilled"
              :isEditMode="!!modelValue.id"
              @update:modelValue="selectedPricingOption = $event"
              title="Premium Launch (100% free)"
              price="Free"
              description="A do-follow backlink if you share our badge"
              :features="badgeLaunchFeatures"
              :isLoading="isLoading"
              @submit="handlePricingOptionSubmit"
            />
          </div>
        </div>
        
        <div v-else class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Save Changes</h3>
          <p class="text-sm text-gray-600 mb-6">As an admin, you can save your edits directly without selecting a pricing option.</p>
          <div v-if="!!modelValue.id" class="space-y-4 mb-6">
            <div>
              <label for="comparison-overrides" class="block text-sm font-semibold text-gray-700 mb-1">
                Curated Comparisons
              </label>
              <textarea
                id="comparison-overrides"
                :value="modelValue.comparison_overrides_input || ''"
                @input="updateField('comparison_overrides_input', $event.target.value)"
                rows="3"
                placeholder="Comma or newline separated product IDs or slugs (e.g. 12, ai-agent-flow, another-product)"
                class="w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">
                These are shown first in the sidebar "Compare with" section.
              </p>
            </div>

            <div>
              <label for="alternative-overrides" class="block text-sm font-semibold text-gray-700 mb-1">
                Curated Alternatives
              </label>
              <textarea
                id="alternative-overrides"
                :value="modelValue.alternative_overrides_input || ''"
                @input="updateField('alternative_overrides_input', $event.target.value)"
                rows="3"
                placeholder="Comma or newline separated product IDs or slugs"
                class="w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">
                These are shown first on the alternatives page.
              </p>
            </div>
          </div>
          <div class="flex flex-col items-start gap-4">
            <div v-if="!isAllRequiredFilled" class="text-sm text-amber-600 font-medium">
              Note: Some required fields are missing, but you can still save as admin.
            </div>
            <button
              @click="$emit('submit')"
              class="px-8 py-3 bg-rose-600 text-white font-bold rounded-lg shadow-md hover:bg-rose-700 transition-all focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
            >
              Save All Changes
            </button>
          </div>
        </div>
      </section> <!-- End Pricing Options / Save Button -->
      
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FreeSubmissionOption from './FreeSubmissionOption.vue';
import BadgeSubmissionOption from './BadgeSubmissionOption.vue';
import { getTabProgress } from '../../services/productFormService';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  logoPreview: {
    type: String,
    default: null
  },
  allTechStacks: Array,
  isAdmin: Boolean,
  isLoading: Boolean,
});

const emit = defineEmits(['update:modelValue', 'submit']);

const progress = computed(() => getTabProgress('launchChecklist', props.modelValue, props.logoPreview));

// Initialize maker links from modelValue
const makerLinks = ref(props.modelValue.maker_links?.length ? [...props.modelValue.maker_links] : []);

// Watch for external changes to maker_links (e.g. from Step 2 or restoration)
watch(() => props.modelValue.maker_links, (newVal) => {
  if (JSON.stringify(newVal) !== JSON.stringify(makerLinks.value.filter(l => l.trim() !== ''))) {
    makerLinks.value = newVal?.length ? [...newVal] : [];
  }
}, { deep: true });

// Tech Stack search + toggle
const techSearch = ref('');

const showAddTechStackButton = computed(() => {
  const search = techSearch.value.trim();
  if (!search) return false;
  if (props.modelValue.tech_stack_custom?.some(ts => ts.name.toLowerCase() === search.toLowerCase())) return false;
  if ((props.modelValue.tech_stack_custom?.length || 0) >= 3) return false;
  return !props.allTechStacks?.some(ts => ts.name.toLowerCase() === search.toLowerCase());
});

const filteredTechStacks = computed(() => {
  if (!props.allTechStacks) return [];
  if (!techSearch.value.trim()) return props.allTechStacks;
  
  const existingCustomTechStacks = props.modelValue.tech_stack_custom?.map(ts => ts.name.toLowerCase()) || [];
  
  return props.allTechStacks.filter(t =>
    t.name.toLowerCase().includes(techSearch.value.toLowerCase()) &&
    !existingCustomTechStacks.includes(t.name.toLowerCase())
  );
});

function toggleTechStack(id) {
  const current = Array.isArray(props.modelValue.tech_stack) ? [...props.modelValue.tech_stack] : [];
  const idx = current.indexOf(id);
  if (idx === -1) {
    if (current.length < 5) current.push(id);
  } else {
    current.splice(idx, 1);
  }
  updateField('tech_stack', current);
}

// Functions to handle custom tech stacks (triggered from search inline button)
function addCustomTechStackFromSearch() {
  const name = techSearch.value.trim();
  if (!name) return;
  if ((props.modelValue.tech_stack_custom?.length || 0) >= 3) return;
  
  const newCustomTechStack = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };
  
  const updatedCustomTechStacks = [...(props.modelValue.tech_stack_custom || []), newCustomTechStack];
  updateField('tech_stack_custom', updatedCustomTechStacks);
  techSearch.value = '';
}

function removeCustomTechStack(customTechStackId) {
  const currentCustomTechStacks = props.modelValue.tech_stack_custom || [];
  const updatedCustomTechStacks = currentCustomTechStacks.filter(ts => ts.id !== customTechStackId);
  updateField('tech_stack_custom', updatedCustomTechStacks);
}

// Sync local changes to modelValue
watch(makerLinks, (newVal) => {
  const filtered = newVal.filter(link => link.trim() !== '');
  if (JSON.stringify(filtered) !== JSON.stringify(props.modelValue.maker_links)) {
    emit('update:modelValue', {
      ...props.modelValue,
      maker_links: filtered
    });
  }
}, { deep: true });

function addMakerLink() {
  makerLinks.value.push('');
}

function removeMakerLink(index) {
  makerLinks.value.splice(index, 1);
}

function updateMakerLink(index, value) {
  makerLinks.value[index] = value;
}

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}

// Initialize selected pricing option with default value 'free'
const selectedPricingOption = ref('free');
const freeSubmissionOptionRef = ref(null);
const paidSubmissionOptionRef = ref(null);

// Watch for changes in selectedPricingOption and update modelValue accordingly
// Note: We should NOT overwrite the actual pricing categories with submission option
watch(selectedPricingOption, (newValue) => {
 // Keep the existing pricing categories and add submission option as separate field
  emit('update:modelValue', {
    ...props.modelValue,
    submissionOption: newValue // Add submission option as separate field
 });
}, { immediate: true });

// Check if all required fields are filled
const isAllRequiredFilled = computed(() => {
  const { link, name, tagline, tagline_detailed, description, categories, bestFor, pricing, logo, logos } = props.modelValue;
  const categoriesCustom = props.modelValue.categories_custom || [];
  
  // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
  const actualPricingCategories = (pricing || []).filter(id => id !== null && id !== undefined && id !== '' && !isNaN(id));
  
  const requiredFields = [
    link,
    name,
    tagline,
    tagline_detailed,
    description,
    (categories && Array.isArray(categories) && categories.length > 0) || categoriesCustom.length > 0,
    // bestFor is optional — not checked here
    actualPricingCategories.length > 0, // Only count actual pricing categories, not submission options
    logo || (logos && Array.isArray(logos) && logos.length > 0) || props.logoPreview // Check for logo preview as well
 ];
  
  return requiredFields.every(field => field);
});

// Define optional fields
const optionalFields = computed(() => [
  { key: 'maker_links', value: !!(props.modelValue.maker_links && props.modelValue.maker_links.length > 0), label: 'Makers\' links' },
 { key: 'tech_stack', value: !!(props.modelValue.tech_stack && props.modelValue.tech_stack.length > 0), label: 'Tech stack' },
 { key: 'sell_product', value: !!props.modelValue.sell_product, label: 'Selling product' },
]);

// Define pricing option features
const freeLaunchFeatures = [
 'Free submission',
 'Badge required on your site',
  'Potential waiting period for approval'
];

const badgeLaunchFeatures = [
  'Skip the wait. Launch next Monday',
  'Up to 3x more exposure',
  'Featured on homepage',
  'Guaranteed dofollow backlink'
];


// Handle pricing option submit (when user clicks the button in either option)
const handlePricingOptionSubmit = (optionValue) => {
  // Update the selected pricing option when user submits from either option
  selectedPricingOption.value = optionValue;
  // Update the model value to include the submission type
  emit('update:modelValue', {
    ...props.modelValue,
    submissionOption: optionValue,
    submission_type: optionValue, // 'free' or 'badge'
    tech_stack_custom: props.modelValue.tech_stack_custom
  });
  
  // Then call the original submit logic
  emit('submit');
};


// Handle field click - navigate to the corresponding form field
const handleFieldClick = (fieldKey) => {
 // Add a small delay to ensure the tab switch happens before scrolling
 setTimeout(() => {
    // Try to focus the corresponding input field if it exists
    const fieldElement = document.querySelector(`[data-field="${fieldKey}"]`);
    if (fieldElement) {
      fieldElement.focus();
      fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, 100);
};

// Helper function to format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(parseFloat(amount));
};
</script>

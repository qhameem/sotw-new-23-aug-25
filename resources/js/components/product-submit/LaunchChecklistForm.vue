<template>
  <div>
    <div class="space-y-6">
      <!-- Back button at top -->
      <div class="flex justify-between items-center mb-2">
        <h1 class="text-2xl font-bold text-gray-800">Launch</h1>
        <button @click="$emit('back')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
          ← Back to Images and Media
        </button>
      </div>
      
      <!-- Makers & Extras Section -->
      <section>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Makers & Extras</h3>
        
        <div class="space-y-6">
          <!-- Makers' Links Section -->
          <div>
            <h4 class="text-md font-medium text-gray-700 mb-3">Makers' Links</h4>
            
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
            <h4 class="text-md font-medium text-gray-700 mb-3">Tech Stack</h4>
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
      
      <!-- Optional fields section -->
      <section>
        <h3 class="text-lg font-semibold text-gray-700">Optional</h3>
        <div class="flex flex-wrap gap-4 mt-4">
          <div
            v-for="field in optionalFields"
            :key="field.key"
            class="flex items-center cursor-pointer min-w-fit"
            :class="field.value ? 'text-green-600' : 'text-gray-500'"
          >
            <svg
              :class="field.value ? 'text-green-500' : 'text-gray-400'"
              class="h-5 w-5 mr-2"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                :d="field.value ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'"
              />
            </svg>
            <span class="text-sm">{{ field.label }}</span>
            <span
              v-if="field.key === 'sell_product' && field.value && modelValue.asking_price"
              class="ml-2 text-xs text-gray-500"
            >
              ({{ formatCurrency(modelValue.asking_price) }})
            </span>
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
          <div v-else class="text-xs font-bold text-green-600 mb-4 flex items-center transition-all duration-300 animate-bounce">
            <span class="mr-1">✓</span> All requirements met! You are ready to launch.
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
              @submit="handlePricingOptionSubmit"
            />
            
            <PaidSubmissionOption
              id="paid-option"
              name="pricing-option"
              value="paid"
              :modelValue="selectedPricingOption"
              :isAllRequiredFilled="isAllRequiredFilled"
              :isEditMode="!!modelValue.id"
              @update:modelValue="selectedPricingOption = $event"
              title="Paid Submission"
              price="$29"
              description="Launch immediately without any requirements"
              :features="paidLaunchFeatures"
              @submit="handlePricingOptionSubmit"
            />
          </div>
        </div>
        
        <div v-else class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Save Changes</h3>
          <p class="text-sm text-gray-600 mb-6">As an admin, you can save your edits directly without selecting a pricing option.</p>
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
import PaidSubmissionOption from './PaidSubmissionOption.vue';
import SearchableDropdown from '../SearchableDropdown.vue';
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
});

const emit = defineEmits(['update:modelValue', 'back', 'submit']);

const progress = computed(() => getTabProgress('launchChecklist', props.modelValue, props.logoPreview));

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
  
  // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
  const actualPricingCategories = (pricing || []).filter(id => id !== null && id !== undefined && id !== '' && !isNaN(id));
  
  const requiredFields = [
    link,
    name,
    tagline,
    tagline_detailed,
    description,
    categories && Array.isArray(categories) && categories.length > 0,
    bestFor && Array.isArray(bestFor) && bestFor.length > 0,
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

const paidLaunchFeatures = [
  'You choose when to publish',
  'No badge required',
  'Immediate approval',
 'Priority placement'
];


// Handle pricing option submit (when user clicks the button in either option)
const handlePricingOptionSubmit = (optionValue) => {
 // Update the selected pricing option when user submits from either option
  selectedPricingOption.value = optionValue;
  // Update the model value to include the submission option separately
  emit('update:modelValue', {
    ...props.modelValue,
    submissionOption: optionValue // Add submission option as separate field
 });
  
  // Then call the original submit logic
  emit('submit');
  
  // Reset loading state after submission is complete
  // Note: This will be called immediately, but the actual reset happens in the parent component
  // after the submission process is complete
  if (freeSubmissionOptionRef.value && typeof freeSubmissionOptionRef.value.submissionComplete === 'function') {
    setTimeout(() => {
      freeSubmissionOptionRef.value.submissionComplete();
    }, 0);
  }
  
  if (paidSubmissionOptionRef.value && typeof paidSubmissionOptionRef.value.submissionComplete === 'function') {
    setTimeout(() => {
      paidSubmissionOptionRef.value.submissionComplete();
    }, 0);
  }
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
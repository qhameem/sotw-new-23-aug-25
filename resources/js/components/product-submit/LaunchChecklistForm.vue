<template>
  <div>
    <div class="space-y-6">
      <!-- Back button at top -->
      <div class="flex justify-between items-center mb-2">
        <h1 class="text-2xl font-bold text-gray-80">Launch</h1>
        <button @click="$emit('back')" class="text-sm font-medium text-gray-600 hover:text-gray-90">
          ← Back to Extras
        </button>
      </div>
      
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
      
      
      <!-- Pricing Options -->
      <section>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Pricing Options</h3>
        <div class="flex flex-wrap gap-6 items-stretch">
          <FreeSubmissionOption
            id="free-option"
            name="pricing-option"
            value="free"
            :modelValue="selectedPricingOption"
            :isAllRequiredFilled="isAllRequiredFilled"
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
            @update:modelValue="selectedPricingOption = $event"
            title="Paid Submission"
            price="$29"
            description="Launch immediately without any requirements"
            :features="paidLaunchFeatures"
            @submit="handlePricingOptionSubmit"
          />
        </div>
      </section> <!-- End Pricing Options -->
      
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import FreeSubmissionOption from './FreeSubmissionOption.vue';
import PaidSubmissionOption from './PaidSubmissionOption.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  logoPreview: {
    type: String,
    default: null
 },
});

const emit = defineEmits(['update:modelValue', 'back', 'submit']);

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
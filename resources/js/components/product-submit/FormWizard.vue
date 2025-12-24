<template>
  <div>
    <!-- Error message display -->
    <div v-if="showErrorMessage && !urlExistsError" class="fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50">
      {{ errorMessage }}
      <button @click="showErrorMessage = false" class="ml-4 text-white font-bold">&times;</button>
    </div>
    
    <ProductURLInput
      v-if="step === 1"
      v-model="form.link"
      :isLoading="isLoading"
      :isUrlInvalid="isUrlInvalid"
      :urlExistsError="urlExistsError"
      :existingProduct="existingProduct"
      @getStarted="getStarted"
      @clear="clearUrlInput"
    />

    <div v-if="step === 2">
      <div class="w-full p-1">
        <div class="w-full p-1 mb-4 bg-white rounded-lg shadow-sm sticky top-16 z-10">
          <div class="flex mb-4 items-center">
            <img
              v-if="form.favicon"
              :src="form.favicon"
              alt="Favicon"
              class="h-12 w-12 mr-2"
            >
            <div>
              <h2 class="text-lg font-semibold text-gray-700">{{ form.name || 'Product Details' }}</h2>
              <p class="text-sm text-gray-500">In progress</p>
            </div>
          </div>
          <ul class="flex flex-wrap justify-between items-center gap-4">
            <li v-for="(step, index) in sidebarSteps" :key="index"
                @click="currentTab = step.id"
                :class="['p-2 rounded-lg cursor-pointer font-normal text-gray-400 text-sm relative', { 'text-gray-700 font-semibold': currentTab === step.id }]">
              <a href="#" class="flex items-center space-x-1">
                <component :is="step.icon" />
                <span class="">{{ step.name }}</span>
              </a>
            </li>
          </ul>
        </div>
        
        <ProductDetailsForm
          v-if="currentTab === 'mainInfo'"
          v-model="form"
          :allCategories="allCategories"
          :allBestFor="allBestFor"
          :allPricing="allPricing"
          @next="goToNextStep('imagesAndMedia')"
          @back="goBack"
        />

        <ProductMediaForm
          v-if="currentTab === 'imagesAndMedia'"
          v-model="form"
          v-model:logoPreview="logoPreview"
          v-model:galleryPreviews="galleryPreviews"
          :loadingStates="loadingStates"
          @back="currentTab = 'mainInfo'"
          @next="goToNextStep('extras')"
          @extractLogos="extractLogosFromUrl"
        />

        <ProductMakersForm
          v-if="currentTab === 'extras'"
          v-model="form"
          :allTechStacks="allTechStacks"
          @back="currentTab = 'imagesAndMedia'"
          @next="goToNextStep('launchChecklist')"
        />

        <LaunchChecklistForm
          v-if="currentTab === 'launchChecklist'"
          v-model="form"
          :logoPreview="logoPreview"
          @back="currentTab = 'extras'"
          @submit="submitProduct"
        />
      </div>
    </div>
  </div>

  <ProductPreviewModal
    :show="showPreviewModal"
    :product="form"
    :allPricing="allPricing"
    @close="closeModal"
    @confirm="confirmSubmit"
 />
</template>

<script setup>
import { watch, onMounted, onUnmounted, nextTick } from 'vue';
import emitter from '../../eventBus';
import { EVENT_TYPES } from '../../eventTypes';
import { useProductForm } from '../../composables/useProductForm';
import ProductURLInput from './ProductURLInput.vue';
import ProductDetailsForm from './ProductDetailsForm.vue';
import ProductMediaForm from './ProductMediaForm.vue';
import ProductMakersForm from './ProductMakersForm.vue';
import LaunchChecklistForm from './LaunchChecklistForm.vue';
import ProductPreviewModal from './ProductPreviewModal.vue';
import { MainInfoIcon, ImagesMediaIcon, ExtrasIcon, LaunchChecklistIcon } from '../icons';

// Use the product form composable
const {
  errorMessage,
  showErrorMessage,
  step,
  currentTab,
  isRestored,
  isMounted,
  isLoading,
  urlExistsError,
  existingProduct,
  showPreviewModal,
  loadingStates,
  logoPreview,
  galleryPreviews,
  allCategories,
  allBestFor,
  allPricing,
  allTechStacks,
  form,
  sidebarSteps,
  isUrlInvalid,
  getStarted,
  clearUrlInput,
  goBack,
  goToNextStep,
  submitProduct,
 confirmSubmit,
  closeModal,
  validateForm,
  fetchInitialData,
  fetchRemainingData,
  initializeFormData,
  loadSavedData,
  saveFormData
} = useProductForm();

// Debounce function
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Debounced function form updates
const debouncedFormUpdate = debounce((newForm) => {
 saveFormData();
  
  // Emit form update event for the checklist
  const formForChecklist = {
     link: newForm.link,
     name: newForm.name,
     tagline: newForm.tagline,
     tagline_detailed: newForm.tagline_detailed,
     description: newForm.description,
     logo: (logoPreview || (newForm.logos && newForm.logos.length > 0)) ? logoPreview || newForm.logos[0] : null, // Pass actual logo value instead of boolean
     selectedPricing: newForm.pricing || [],
  };
  emitter.emit(EVENT_TYPES.FORM_UPDATED, formForChecklist);
}, 100); // 100ms debounce delay - reduced for more responsive updates

// Watch for form changes and trigger debounced update
watch(form, (newForm) => {
 // Clear error message when form is updated, but not if it's a URL exists error
 if (!urlExistsError.value) {
   showErrorMessage.value = false;
 }
 
 // Call the debounced function
 debouncedFormUpdate(newForm);
}, { deep: true });

// Debounced function for logo preview updates
const debouncedLogoUpdate = debounce((newLogoPreview) => {
 // Emit form update event for the checklist when logo changes
const formForChecklist = {
    link: form.link,
    name: form.name,
    tagline: form.tagline,
    tagline_detailed: form.tagline_detailed,
    description: form.description,
    logo: (newLogoPreview || (form.logos && form.logos.length > 0)) ? newLogoPreview || form.logos[0] : null, // Pass actual logo value instead of boolean
    selectedPricing: form.pricing || [],
  };
emitter.emit(EVENT_TYPES.FORM_UPDATED, formForChecklist);
}, 100); // 100ms debounce delay - reduced for more responsive updates

// Watch for logo preview changes and trigger debounced update
watch(logoPreview, (newLogoPreview) => {
 // Clear error message when logo preview changes, but not if it's a URL exists error
 if (!urlExistsError.value) {
   showErrorMessage.value = false;
 }
 
 // Call the debounced function
 debouncedLogoUpdate(newLogoPreview);
});

watch(() => form.link, (newLink, oldLink) => {
  if (isMounted.value && newLink !== oldLink) {
    form.name = '';
    form.tagline = '';
    form.tagline_detailed = '';
    form.description = '';
    // Don't reset favicon as it will be fetched automatically with initial data
    form.logos = [];
    logoPreview.value = null;
    
    // Fetch initial data for the new URL
    if (newLink) {
      fetchInitialData();
      // Also fetch remaining data (including logos) when the link is entered
      // This ensures logos are available as soon as the user enters a URL
      setTimeout(() => {
        if (form.name) { // Only fetch remaining data if we have a name
          fetchRemainingData();
        }
      }, 500); // Small delay to ensure fetchInitialData completes first
    }
  }
});

// Watch for step changes to update checklist visibility
watch(() => step.value, (newStep) => {
  if (newStep === 2) {
    // Always fetch remaining data when entering step 2 to ensure logos are available
    fetchRemainingData();
  }
  
  // Emit a custom event when step changes to update checklist visibility
  document.dispatchEvent(new CustomEvent('step-changed', {
    detail: { step: newStep }
  }));
});

watch(isRestored, (restored) => {
 if (restored && step.value === 2) {
    fetchRemainingData();
  }
});

onMounted(async () => {
  // Initialize form options
  await initializeFormData();
  
  // Load saved data if available
 loadSavedData();
  
  nextTick(() => {
    isRestored.value = true;
    isMounted.value = true;
    
    // Emit initial step change event to set checklist visibility
    document.dispatchEvent(new CustomEvent('step-changed', {
      detail: { step: step.value }  // Keep .value here as step is a ref
    }));
  });
});

// Function to switch to a specific tab
const switchTab = (payload) => {
  // Handle both old format (string) and new format (object with tabName)
  const tabName = typeof payload === 'string' ? payload : payload.tabName;
  
  // Ensure we're in step 2 (form view) before switching tabs
  if (step.value !== 2) {
    step.value = 2;
  }
  currentTab.value = tabName;
};

// Listen for the switch-tab event from the DynamicChecklist component
onMounted(() => {
    emitter.on(EVENT_TYPES.SWITCH_TAB, switchTab);
});

onUnmounted(() => {
    emitter.off('switch-tab', switchTab);
});

// Function to extract logos when the button is clicked
async function extractLogosFromUrl() {
 console.log('extractLogosFromUrl called', {
    form: form,
    link: form?.link,
    name: form?.name,
    linkType: typeof form?.link,
    linkTruthy: !!form?.link
 });
  
  try {
    // Immediately set the loading state for logos to show the loader immediately
    loadingStates.logos = true;
    console.log('Loading state set to true');
    
    // Use the reactive form from the composable - access the values directly
    console.log('Full form object:', JSON.stringify(form, null, 2));
    
    // Check if we have a link - if not, show an appropriate message
    // Using the actual form data from the composable which should be reactive
    const linkValue = form?.link;
    console.log('Link value being checked:', linkValue, 'Type:', typeof linkValue, 'Truthy:', !!linkValue);
    
    if (!linkValue || linkValue.trim() === '') {
      console.log('No link available, prompting user to enter a product URL first');
      // Show an error message to the user
      showErrorMessage.value = true;
      errorMessage.value = 'Please enter a product URL first before extracting logos.';
      return;
    }
    
    // If we have a link but no name, fetch the initial data first
    if (linkValue && !form.name) {
      console.log('Link exists but name is missing, fetching initial data first', { link: linkValue });
      await fetchInitialData();
      // Wait a bit to ensure initial data is fetched before remaining data
      await new Promise(resolve => setTimeout(resolve, 500));
    }
    
    // Now that we have both link and name (or have attempted to fetch them), proceed with logo extraction
    if (linkValue && form.name) {
      console.log('Calling fetchRemainingData with link and name', { link: linkValue, name: form.name });
      await fetchRemainingData(true); // Pass true to indicate explicit logo extraction
    } else {
      console.log('Cannot extract logos - missing required data after attempting to fetch initial data', {
        link: linkValue,
        name: form?.name
      });
      showErrorMessage.value = true;
      errorMessage.value = 'Unable to extract logos. Please make sure the product URL is valid and try again.';
    }
 } catch (error) {
    console.error('Error during logo extraction:', error);
    // Make sure to reset the loading state even if there's an error
    loadingStates.logos = false;
    // Show error message to user
    showErrorMessage.value = true;
    errorMessage.value = 'Error extracting logos: ' + (error.message || 'Unknown error occurred');
 } finally {
    console.log('Resetting loading state to false in finally block');
    // Ensure loading state is reset in all cases
    loadingStates.logos = false;
 }
}
</script>
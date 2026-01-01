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
        <div class="w-full p-1 mb-4 bg-white sticky top-16 z-10">
          <div class="flex mb-4 items-center">
            <img
              v-if="form.favicon"
              :src="form.favicon"
              alt="Favicon"
              class="h-12 w-12 mr-2"
            >
            <div>
              <h2 class="text-lg font-bold text-gray-700">{{ form.name || 'Product Details' }}</h2>
              <p class="text-xs text-gray-500">In progress</p>
            </div>
          </div>
          <ul class="flex flex-wrap rounded-md justify-between items-center gap-4">
            <li v-for="(step, index) in sidebarSteps" :key="index"
                @click="currentTab = step.id"
                :class="['py-1 my-2 cursor-pointer font-medium text-gray-400 text-sm relative', { 'text-rose-500 font-medium': currentTab === step.id }]">
              <a href="#" class="flex flex-col items-center relative">
                <div class="flex items-center space-x-1">
                  <span :class="['flex items-center justify-center rounded-full w-6 h-6 text-xs font-bold text-white', isStepCompleted(step.id) ? 'bg-green-500' : (currentTab === step.id ? 'bg-rose-500' : 'bg-gray-300')]">
                    <template v-if="isStepCompleted(step.id)">
                      ✓
                    </template>
                    <template v-else>
                      {{ index + 1 }}
                    </template>
                  </span>
                  <component :is="step.icon" />
                  <span class="">{{ step.name }}</span>
                </div>
                <!-- <svg v-if="currentTab === step.id" class="w-5 h-5 text-gray-500 absolute top-full mt-1" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11.178 19.569a.998.998 0 0 0 1.644 0l9-13A.999 0 0 0 21 5H3a1.002 1.002 0 0-.822 1.569l9 13z" fill="currentColor"></path>
                </svg> -->
              </a>
            </li>
          </ul>
          <hr class="border-t border-gray-200 mt-2">
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
          @next="goToNextStep('launchChecklist')"
          @extractLogos="extractLogosFromUrl"
        />

        <LaunchChecklistForm
          v-if="currentTab === 'launchChecklist'"
          v-model="form"
          :logoPreview="logoPreview"
          :allTechStacks="allTechStacks"
          @back="currentTab = 'imagesAndMedia'"
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
import { useProductForm } from '../../composables/useProductForm';
import { isTabCompleted } from '../../services/productFormService';
import ProductURLInput from './ProductURLInput.vue';
import ProductDetailsForm from './ProductDetailsForm.vue';
import ProductMediaForm from './ProductMediaForm.vue';
import LaunchChecklistForm from './LaunchChecklistForm.vue';
import ProductPreviewModal from './ProductPreviewModal.vue';
import { MainInfoIcon, ImagesMediaIcon, LaunchChecklistIcon } from '../icons';

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
}, 100); // 100ms debounce delay - reduced for more responsive updates


// Debounced function for logo preview updates
const debouncedLogoUpdate = debounce((newLogoPreview) => {
}, 10); // 100ms debounce delay - reduced for more responsive updates

// Watch for form changes and trigger debounced update
watch(form, (newForm) => {
 // Clear error message when form is updated, but not if it's a URL exists error
 if (!urlExistsError.value) {
   showErrorMessage.value = false;
 }
 
 // Call the debounced function
 debouncedFormUpdate(newForm);
}, { deep: true });

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

// Watch for step changes
watch(() => step.value, (newStep) => {
  if (newStep === 2) {
    // Always fetch remaining data when entering step 2 to ensure logos are available
    fetchRemainingData();
  }
  
  // Emit a custom event when step changes
  document.dispatchEvent(new CustomEvent('step-changed', {
    detail: { step: newStep }
  }));
});

watch(isRestored, (restored) => {
 if (restored && step.value === 2) {
    fetchRemainingData();
  }
});

// Watch for form changes to trigger UI updates for step completion
watch(() => [
  form.name,
  form.tagline,
  form.tagline_detailed,
  form.description,
  form.categories,
  form.bestFor,
  form.pricing,
  logoPreview,
  form.logos
], () => {
  // This forces the UI to update when form data changes
  // The isStepCompleted function will be re-evaluated when these values change
}, { deep: true });

onMounted(async () => {
  // Initialize form options
  await initializeFormData();
  
  // Load saved data if available
 loadSavedData();
  
  nextTick(() => {
    isRestored.value = true;
    isMounted.value = true;
    
    // Emit initial step change event
    document.dispatchEvent(new CustomEvent('step-changed', {
      detail: { step: step }
    }));
  });
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

// Function to check if a step is completed based on required fields
const isStepCompleted = (stepId) => {
  // Find the step in sidebarSteps
  const step = sidebarSteps.find(s => s.id === stepId);
  if (!step) return false;
  
  // Use the service function to check if the tab is completed
 const completed = isTabCompleted(step, form, logoPreview);
  
  // For launch checklist, we'll consider it completed when the form is submitted
  // For now, this tab will only show a checkmark when the form is actually submitted
 if (stepId === 'launchChecklist') {
    return false; // This tab should not show a checkmark until form is submitted
  }
  
  return completed;
}
</script>
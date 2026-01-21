<template>
  <div class="h-full flex flex-col flex-1">
    <!-- Error message display -->
    <div v-if="showErrorMessage" class="fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50">
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
      :submissionBgUrl="submissionBgUrl"
      @getStarted="getStarted"
      @clear="clearUrlInput"
    />

    <div v-if="step === 2" :class="['w-full px-4 pt-28 mx-auto pb-12', isAdmin ? 'max-w-full' : 'max-w-6xl']">
      <div class="w-full md:p-6">
        <div class="flex flex-col md:flex-row gap-8">
          <!-- Sidebar Navigation -->
          <div class="w-full md:w-64 shrink-0 transition-all duration-300 ease-in-out md:sticky md:left-0 z-20">
            <div class="md:sticky md:top-24 bg-gray-100 rounded-lg p-4 md:shadow-sm border border-gray-100">
              <div class="flex mb-6 items-center border-b pb-4">
                <img
                  v-if="form.favicon"
                  :src="form.favicon"
                  alt="Favicon"
                  class="h-10 w-10 mr-3 rounded-md shadow-sm"
                >
                <div class="overflow-hidden">
                  <h2 class="text-base font-bold text-gray-800 truncate">{{ form.name || 'Product Details' }}</h2>
                  <p class="text-xs text-blue-600 font-medium bg-blue-50 inline-block px-1.5 py-0.5 rounded mt-1">In progress</p>
                </div>
              </div>
              
              <ul class="flex flex-row md:flex-col overflow-x-auto md:overflow-visible gap-2 md:gap-1 pb-2 md:pb-0 no-scrollbar">
                <li v-for="(step, index) in sidebarSteps" :key="index"
                    @click="currentTab = step.id"
                    :class="['cursor-pointer rounded-md transition-all duration-200 whitespace-nowrap', 
                      currentTab === step.id ? 'bg-white shadow-sm' : 'hover:bg-gray-50']">
                  <a href="#" class="flex items-center px-3 py-2.5 relative group">
                    <span :class="['flex items-center justify-center rounded-full w-6 h-6 text-xs font-bold mr-3 transition-colors', 
                      currentTab === step.id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500 group-hover:bg-gray-300']">
                      {{ index + 1 }}
                    </span>
                    <span :class="['text-sm font-medium', currentTab === step.id ? 'text-gray-900 font-bold' : 'text-gray-600 group-hover:text-gray-900']">{{ step.name }}</span>
                    
                    <!-- Arrow for active state on desktop -->
                    <div v-if="currentTab === step.id" class="hidden md:block absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-1/2 w-2 h-2 bg-white rotate-45 border-t border-r border-transparent"></div>
                  </a>
                </li>
              </ul>
            </div>
          </div>
          
          <!-- Main Content Area -->
          <div :class="['flex-1 min-w-0 bg-gray-50 rounded-lg md:shadow-sm md:border md:border-gray-100 md:p-6', isAdmin ? 'max-w-full' : 'max-w-4xl']">
            <ProductDetailsForm
              v-if="currentTab === 'mainInfo'"
              :modelValue="form"
              @update:modelValue="Object.assign(form, $event)"
              :allCategories="allCategories"
              :allBestFor="allBestFor"
              :allPricing="allPricing"
              :loadingStates="loadingStates"
              :extractionErrors="extractionErrors"
              @next="goToNextStep('imagesAndMedia')"
              @back="goBack"
            />

            <ProductMediaForm
              v-if="currentTab === 'imagesAndMedia'"
              :modelValue="form"
              @update:modelValue="Object.assign(form, $event)"
              v-model:logoPreview="logoPreview"
              v-model:galleryPreviews="galleryPreviews"
              :loadingStates="loadingStates"
              @back="currentTab = 'mainInfo'"
              @next="goToNextStep('launchChecklist')"
              @extractLogos="extractLogosFromUrl"
            />

            <LaunchChecklistForm
              v-if="currentTab === 'launchChecklist'"
              :modelValue="form"
              @update:modelValue="Object.assign(form, $event)"
              :logoPreview="logoPreview"
              :allTechStacks="allTechStacks"
              :isAdmin="isAdmin"
              @back="currentTab = 'imagesAndMedia'"
              @submit="submitProduct"
            />
          </div>
        </div>
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
  isAdmin,
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
  submissionBgUrl,
  fetchInitialMetadata,
  fetchRemainingData,
  initializeFormData,
 loadSavedData,
  saveFormData
} = useProductForm();

// Track if we've loaded initial data to avoid clearing fields when loading existing product
let initialDataLoaded = false;

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




// Set flag when initial data is loaded
const markInitialDataLoaded = () => {
  initialDataLoaded = true;
};

// Watch for when initial data is loaded via the composable
watch(() => form.id, (newId) => {
 // When a product ID is set, it means we're loading an existing product
 if (newId && !initialDataLoaded) {
    markInitialDataLoaded();
  }
});

watch(() => form.link, (newLink, oldLink) => {
  // If we haven't loaded initial data yet, just mark it as processed
  if (!initialDataLoaded) {
    // Check if this looks like initial data loading (oldLink is empty/falsy)
    if (!oldLink && newLink) {
      // This appears to be initial data loading, don't clear fields
      markInitialDataLoaded();
      return;
    }
  }
  // Only clear fields if component is mounted, initial data has been loaded, and the link actually changed
  else if (isMounted.value && newLink !== oldLink) {
    form.name = '';
    form.tagline = '';
    form.tagline_detailed = '';
    form.description = '';
    // Don't reset favicon as it will be fetched automatically with initial data
    form.logos = [];
    logoPreview.value = null;
    
     
    // We do NOT fetch initial data here anymore.
    // Fetching happens only when the user clicks "Get Started" to prevent UI blocking loops during typing.
    /*
    if (newLink) {
      fetchInitialData();
    }
    */
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
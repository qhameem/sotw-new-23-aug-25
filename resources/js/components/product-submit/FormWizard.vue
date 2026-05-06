<template>
  <div class="min-h-screen bg-white text-gray-900 font-sans pb-20">
    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto w-full px-4 pt-4 mt-4 md:mt-12 md:px-8 md:pt-12">
      
      <transition name="fade-slide" mode="out-in">
        <!-- Landing View -->
        <div v-if="!showForm" key="landing" class="grid grid-cols-1 lg:grid-cols-12 gap-12">
          <!-- Left Column: Entry Options -->
          <div class="lg:col-span-8 space-y-6">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-8">Submit a Project</h1>
            
            <!-- URL Input Block -->
            <ProductURLInput
              :modelValue="form.link"
              @update:modelValue="handleUrlInputUpdate"
              :isLoading="isLoading"
              :loadingProgress="loadingProgress"
              :loadingMessage="loadingMessage"
              :isUrlInvalid="isUrlInvalid"
              :urlTrimSuggestion="urlTrimSuggestion"
              :urlExistsError="urlExistsError"
              :existingProduct="existingProduct"
              @getStarted="handleUrlFetch"
              @clear="clearForm"
            />

            <!-- Manual Fill Trigger -->
            <div
              @click="showForm = true"
              class="group relative border-2 border-dashed border-gray-200 rounded-3xl h-1/2 flex items-center justify-center cursor-pointer hover:border-sky-400 hover:bg-sky-50/30 transition-all duration-300"
            >
              <div class="text-center group-hover:scale-105 transition-transform duration-300">
                <p class="text-gray-400 font-medium text-sm">Or click here to fill manually</p>
              </div>
            </div>
          </div>

          <!-- Right Column: Sidebar Previews -->
          <div class="hidden lg:block lg:col-span-4 space-y-8">
            <ProductPreviewCard 
              :form="form" 
              :logoPreview="logoPreview" 
              :galleryPreviews="galleryPreviews" 
              :allCategories="allCategories"
              @open-logo-picker="openLogoPicker"
              @remove-logo="removeSelectedLogo"
            />
            <FormProgress 
              :form="form" 
              :logoPreview="logoPreview" 
              :galleryPreviews="galleryPreviews"
            />
          </div>
        </div>

        <!-- Full Form View -->
        <div v-else key="form" class="grid grid-cols-1 lg:grid-cols-12 gap-12">
          <!-- Left Main Column (Form Fields) -->
          <div class="lg:col-span-8 space-y-10">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 mb-6">Submit a Project</h1>

              <!-- Submission Error Message -->
              <transition name="fade">
                <div v-if="showErrorMessage" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg flex items-start">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm text-red-700 font-medium">
                      {{ errorMessage }}
                    </p>
                  </div>
                  <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                      <button @click="showErrorMessage = false" type="button" class="inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </transition>
              
              <form @submit.prevent="submitProduct" class="space-y-8">
                <div id="url-section" class="scroll-mt-6">
                  <ProductURLInput
                    :modelValue="form.link"
                    @update:modelValue="handleUrlInputUpdate"
                    :isLoading="isLoading"
                    :loadingProgress="loadingProgress"
                    :loadingMessage="loadingMessage"
                    :isUrlInvalid="isUrlInvalid"
                    :urlTrimSuggestion="urlTrimSuggestion"
                    :urlExistsError="urlExistsError"
                    :existingProduct="existingProduct"
                    @getStarted="handleUrlFetch"
                    @clear="clearForm"
                  />
                </div>

                <div id="details-section" class="scroll-mt-6">
                  <ProductDetailsForm
                    :modelValue="form"
                    @update:modelValue="handleFormDetailUpdate"
                    :allCategories="allCategories"
                    :allBestFor="allBestFor"
                    :allPricing="allPricing"
                    :loadingStates="loadingStates"
                    :extractionErrors="extractionErrors"
                  />
                </div>

                <!-- Section 3: Media -->
                <div id="media-section" class="scroll-mt-6 border-t border-gray-100 pt-8">
                  <h2 class="text-xl font-bold text-gray-800 mb-4">Media</h2>
                  <ProductMediaForm
                    :modelValue="form"
                    @update:modelValue="handleFormDetailUpdate"
                    :logoPreview="logoPreview"
                    :galleryPreviews="galleryPreviews"
                    :loadingStates="loadingStates"
                    @update:logoPreview="logoPreview = $event"
                    @update:galleryPreviews="galleryPreviews = $event"
                    @open-logo-picker="openLogoPicker"
                  />
                </div>

                <!-- Section 4: Launch Checklist & Submit -->
                <div id="launch-section" class="scroll-mt-6 border-t border-gray-100 pt-8">
                  <h2 class="text-xl font-bold text-gray-800 mb-4">Additional Info</h2>
                  <LaunchChecklistForm
                    :modelValue="form"
                    @update:modelValue="handleFormDetailUpdate"
                    :logoPreview="logoPreview"
                    :allTechStacks="allTechStacks"
                    :isAdmin="isAdmin"
                    :isLoading="isLoading"
                    @submit="submitProduct"
                  />
                </div>
              </form>
            </div>
            
            <!-- Bottom Navigation Buttons -->
            <div class="flex justify-between items-center pt-8 border-t border-gray-100">
              
            </div>
          </div>

            <!-- Right Sidebar (Preview & Progress) -->
           <div class="hidden lg:block lg:col-span-4 space-y-8">
             <div class="sticky top-8 space-y-6">
               <ProductPreviewCard 
                 :form="form" 
                 :logoPreview="logoPreview" 
                 :galleryPreviews="galleryPreviews" 
                 :allCategories="allCategories"
                 @open-logo-picker="openLogoPicker"
                 @remove-logo="removeSelectedLogo"
               />
               <FormProgress 
                 :form="form" 
                 :logoPreview="logoPreview" 
                 :galleryPreviews="galleryPreviews"
               />
             </div>
           </div>
         </div>
       </transition>

      <LogoPickerModal
        :show="isLogoPickerOpen"
        :currentLogo="logoPreview || form.favicon"
        :logos="form.logos"
        :favicon="form.favicon || originalFavicon"
        :productName="form.name || 'your product'"
        :isLoading="loadingStates.logos"
        @close="closeLogoPicker"
        @select-logo="applySelectedLogo"
        @upload-logo="uploadLogoFile"
        @refresh-logos="extractLogos"
        @restore-favicon="restoreFaviconLogo"
      />
    </div>
   </div>
 </template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import ProductURLInput from './ProductURLInput.vue';
import ProductDetailsForm from './ProductDetailsForm.vue';
import ProductMediaForm from './ProductMediaForm.vue';
import LaunchChecklistForm from './LaunchChecklistForm.vue';
import ProductPreviewCard from './ProductPreviewCard.vue';
import FormProgress from './FormProgress.vue';
import LogoPickerModal from './LogoPickerModal.vue';
import { useProductForm } from '../../composables/useProductForm';

const props = defineProps({
  initialProduct: {
    type: Object,
    default: null
  },
  // Removed props that are now fetched via useProductForm
});

const showForm = ref(props.initialProduct ? true : false);
const isLogoPickerOpen = ref(false);
let urlExistsCheckTimeout = null;

const {
  form,
  loadingStates,
  extractionErrors,
  urlExistsError,
  existingProduct,
  logoPreview,
  galleryPreviews,
  submitProduct,
  fetchInitialData,
  checkUrlExists,
  extractLogos,
  allCategories,
  allBestFor,
  allPricing,
  allTechStacks,
  isAdmin,
  isUrlInvalid,
  urlTrimSuggestion,
  initializeFormData,
  isLoading,
  loadingProgress,
  loadingMessage,
  errorMessage,
  showErrorMessage,
  isRestored
} = useProductForm(props.initialProduct);

// When editing an existing product, show the form once data is loaded
watch(isRestored, (val) => {
  if (val && form.link) {
    showForm.value = true;
  }
});

// Store the original fetched favicon so users can restore it after removing
const originalFavicon = ref(null);
watch(() => form.favicon, (newVal) => {
  if (newVal && !originalFavicon.value) {
    originalFavicon.value = newVal;
  }
});

watch(() => form.link, (newVal) => {
  // Reset original favicon when URL changes
  if (!newVal) originalFavicon.value = null;
  console.log('[FormWizard] form.link changed:', newVal);
});

// Navigation Steps
const steps = [
  { id: 'url-section', name: 'Start' },
  { id: 'details-section', name: 'Details' },
  { id: 'media-section', name: 'Media' },
  { id: 'launch-section', name: 'Launch' },
];

const activeSection = ref('url-section');

const scrollToSection = (id) => {
  const element = document.getElementById(id);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    activeSection.value = id;
  }
};

// Intersection Observer for scroll spying
let observer = null;
onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        activeSection.value = entry.target.id;
      }
    });
  }, { threshold: 0.5 });

  steps.forEach((step) => {
    const el = document.getElementById(step.id);
    if (el) observer.observe(el);
  });
  
  initializeFormData();
});

onUnmounted(() => {
  if (urlExistsCheckTimeout) {
    clearTimeout(urlExistsCheckTimeout);
  }
  if (observer) observer.disconnect();
});

// Handle URL Fetch Action (formerly "Get Started")
const handleUrlFetch = async (url) => {
  if (isLoading.value) {
    return;
  }

  form.link = url;
  isLoading.value = true;
  loadingProgress.value = 3;
  loadingMessage.value = 'Checking website URL...';

  await checkUrlExists(url);
  if (urlExistsError.value) {
    isLoading.value = false;
    loadingProgress.value = 0;
    loadingMessage.value = '';
    return;
  }

  await fetchInitialData(url);
  showForm.value = true;
};

const handleUrlInputUpdate = (val) => {
  console.log('[FormWizard] update:modelValue:', val);
  form.link = val;

  if (urlExistsCheckTimeout) {
    clearTimeout(urlExistsCheckTimeout);
  }

  if (!val) {
    checkUrlExists('');
    return;
  }

  urlExistsCheckTimeout = setTimeout(() => {
    checkUrlExists(val);
  }, 300);
};

const clearForm = () => {
    form.link = '';
    checkUrlExists('');
    // Reset other fields if needed, but keeping it simple for now as per previous logic
};

const handleFormDetailUpdate = (updatedForm) => {
  // We must mutate the reactive form object, not replace it
  Object.assign(form, updatedForm);
};

const openLogoPicker = async () => {
  isLogoPickerOpen.value = true;

  if (!loadingStates.logos && form.link && (!form.logos || form.logos.length === 0)) {
    await extractLogos();
  }
};

const closeLogoPicker = () => {
  isLogoPickerOpen.value = false;
};

const applySelectedLogo = (logoUrl) => {
  form.logo = logoUrl;
  logoPreview.value = logoUrl;
  isLogoPickerOpen.value = false;
};

const uploadLogoFile = (file) => {
  form.logo = file;

  const reader = new FileReader();
  reader.onload = (event) => {
    logoPreview.value = event.target.result;
    isLogoPickerOpen.value = false;
  };
  reader.readAsDataURL(file);
};

const restoreFaviconLogo = () => {
  const restoredLogo = form.favicon || originalFavicon.value;
  if (!restoredLogo) {
    return;
  }

  form.logo = null;
  form.favicon = restoredLogo;
  logoPreview.value = restoredLogo;
  isLogoPickerOpen.value = false;
};

const removeSelectedLogo = () => {
  form.logo = null;
  form.favicon = null;
  logoPreview.value = null;
};


// Calculate Overall Progress (Simplified for demo)
const overallProgress = computed(() => {
  let filled = 0;
  let total = 0;

  // Basic required fields check (simplified)
  if (form.link) filled++; total++;
  if (form.name) filled++; total++;
  if (form.tagline) filled++; total++;
  if (form.description) filled++; total++;
  if (form.logo || logoPreview.value) filled++; total++;
  
  return (filled / total) * 100;
});

</script>

<style scoped>
/* Add any specific overrides here if needed */

</style>

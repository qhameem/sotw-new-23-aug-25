<template>
  <div class="min-h-screen bg-white text-gray-900 font-sans pb-20">
    <!-- Main Content Area -->
    <div class="max-w-7xl mx-auto w-full px-4 pt-4 mt-4 md:mt-12 md:px-8 md:pt-12">
      
      <transition name="fade-slide" mode="out-in">
        <!-- Landing View -->
        <div v-if="!showForm" key="landing" class="grid grid-cols-1 lg:grid-cols-12 gap-12">
          <!-- Left Column: Entry Options -->
          <div class="lg:col-span-8 space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
              <h1 class="text-2xl font-bold text-gray-800">Submit a Project</h1>
              <div v-if="showAdminSandboxControls" class="md:max-w-sm md:flex-shrink-0">
                <AdminSandboxBanner
                  :modelValue="form"
                  :sandboxNotice="sandboxNotice"
                  :isLoading="isLoading"
                  @update:modelValue="handleFormDetailUpdate"
                />
              </div>
            </div>
            
            <!-- URL Input Block -->
            <ProductURLInput
              :modelValue="form.link"
              :additionalResources="form.additional_resources"
              @update:modelValue="handleUrlInputUpdate"
              @update:additionalResources="handleAdditionalResourcesUpdate"
              @validate-field="handleFieldValidationRequest"
              :isLoading="isLoading"
              :showExtraContext="showAiContext"
              :isSandboxMode="showAdminSandboxControls && form.sandbox_mode"
              :loadingProgress="loadingProgress"
              :loadingMessage="loadingMessage"
              :isUrlInvalid="isUrlInvalid"
              :urlTrimSuggestion="urlTrimSuggestion"
              :urlExistsError="urlExistsError"
              :existingProduct="existingProduct"
              :fieldError="validationErrors.link"
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
              @upload-screenshot="uploadScreenshotFile"
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
              <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <h1 class="text-3xl font-bold text-gray-900">Submit a Project</h1>
                <div v-if="showAdminSandboxControls" class="md:max-w-sm md:flex-shrink-0">
                  <AdminSandboxBanner
                    :modelValue="form"
                    :sandboxNotice="sandboxNotice"
                    :isLoading="isLoading"
                    @update:modelValue="handleFormDetailUpdate"
                  />
                </div>
              </div>

              <div
                v-if="autofillReveal.active"
                class="mb-4 rounded-xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-xs text-sky-700"
              >
                We’re filling this out step by step. Fields unlock as each piece of product info is ready.
              </div>

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
                    :additionalResources="form.additional_resources"
                    @update:modelValue="handleUrlInputUpdate"
                    @update:additionalResources="handleAdditionalResourcesUpdate"
                    @validate-field="handleFieldValidationRequest"
                    :isLoading="isLoading"
                    :showExtraContext="showAiContext"
                    :isSandboxMode="showAdminSandboxControls && form.sandbox_mode"
                    :loadingProgress="loadingProgress"
                    :loadingMessage="loadingMessage"
                    :isUrlInvalid="isUrlInvalid"
                    :urlTrimSuggestion="urlTrimSuggestion"
                    :urlExistsError="urlExistsError"
                    :existingProduct="existingProduct"
                    :fieldError="validationErrors.link"
                    @getStarted="handleUrlFetch"
                    @clear="clearForm"
                  />
                </div>

                <div id="details-section" class="scroll-mt-6">
                  <ProductDetailsForm
                    :modelValue="form"
                    @update:modelValue="handleFormDetailUpdate"
                    @rewrite-description="handleDescriptionRewrite"
                    :allCategories="allCategories"
                    :allUseCases="allUseCases"
                    :allPlatforms="allPlatforms"
                    :allBestFor="allBestFor"
                    :allPricing="allPricing"
                    :loadingStates="loadingStates"
                    :extractionErrors="extractionErrors"
                    :validationErrors="validationErrors"
                    :autofillReveal="autofillReveal"
                    :isAdmin="isAdmin"
                  />
                </div>

                <div class="lg:hidden">
                  <ProductPreviewCard
                    :form="form"
                    :logoPreview="logoPreview"
                    :galleryPreviews="galleryPreviews"
                    :allCategories="allCategories"
                    :validationErrors="validationErrors"
                    @update:modelValue="handleFormDetailUpdate"
                    @open-logo-picker="openLogoPicker"
                    @remove-logo="removeSelectedLogo"
                    @upload-screenshot="uploadScreenshotFile"
                  />
                </div>

                <div id="launch-section" class="scroll-mt-6 border-t border-gray-100 pt-8">
                  <h2 class="text-xl font-bold text-gray-800 mb-4">Additional Info</h2>
                  <div
                    class="transition-all duration-300"
                    :class="{ 'autofill-locked-section': autofillReveal.active && !autofillReveal.unlocked.launch }"
                  >
                    <LaunchChecklistForm
                      :modelValue="form"
                      @update:modelValue="handleFormDetailUpdate"
                      :logoPreview="logoPreview"
                      :allTechStacks="allTechStacks"
                      :isAdmin="isAdmin"
                      :adminSandboxEnabled="adminSandboxEnabled"
                      :isLoading="isLoading"
                      :submitState="submitState"
                      :validationErrors="validationErrors"
                      :validationSummary="validationSummary"
                      :generalErrorMessage="showErrorMessage ? errorMessage : ''"
                      @focus-field="handleFocusField"
                      @submit="submitProduct"
                    />
                  </div>
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
                 :validationErrors="validationErrors"
                 @update:modelValue="handleFormDetailUpdate"
                 @open-logo-picker="openLogoPicker"
                 @remove-logo="removeSelectedLogo"
                 @upload-screenshot="uploadScreenshotFile"
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
import AdminSandboxBanner from './AdminSandboxBanner.vue';
import ProductURLInput from './ProductURLInput.vue';
import ProductDetailsForm from './ProductDetailsForm.vue';
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
  simulateSandboxAutofill,
  checkUrlExists,
  extractLogos,
  rewriteProductDescription,
  allCategories,
  allUseCases,
  allPlatforms,
  allBestFor,
  allPricing,
  allTechStacks,
  isAdmin,
  adminSandboxEnabled,
  isUrlInvalid,
  urlTrimSuggestion,
  initializeFormData,
  isLoading,
  submitState,
  sandboxNotice,
  loadingProgress,
  loadingMessage,
  autofillReveal,
  errorMessage,
  showErrorMessage,
  validationErrors,
  validationSummary,
  isRestored,
  touchField,
  validateField,
  resetValidationState,
  focusField,
  markManualLogoChosen,
  markManualScreenshotChosen,
  resetManualMediaChoices
} = useProductForm(props.initialProduct);

const showAdminSandboxControls = computed(() => isAdmin.value && adminSandboxEnabled.value && !form.id);
const showAiContext = computed(() => !form.id);

// When editing an existing product, show the form once data is loaded
watch(isRestored, (val) => {
  if (val && form.link) {
    showForm.value = true;
  }
});

watch(
  () => autofillReveal.showFormReady,
  (isReady) => {
    if (isReady && form.link) {
      showForm.value = true;
    }
  }
);

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

watch(showAdminSandboxControls, (isVisible) => {
  if (!isVisible && form.sandbox_mode) {
    form.sandbox_mode = false;
  }
}, { immediate: true });

// Navigation Steps
const steps = [
  { id: 'url-section', name: 'Start' },
  { id: 'details-section', name: 'Details' },
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

  resetManualMediaChoices();

  if (showAdminSandboxControls.value && form.sandbox_mode) {
    await simulateSandboxAutofill();
    showForm.value = true;
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
  touchField('link');
  validateField('link');

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

const handleAdditionalResourcesUpdate = (val) => {
  form.additional_resources = val;
};

const clearForm = () => {
    form.link = '';
    checkUrlExists('');
    resetManualMediaChoices();
    resetValidationState();
    // Reset other fields if needed, but keeping it simple for now as per previous logic
};

const handleFormDetailUpdate = (updatedForm) => {
  const changedKeys = Object.keys(updatedForm).filter((key) => JSON.stringify(form[key]) !== JSON.stringify(updatedForm[key]));

  // We must mutate the reactive form object, not replace it
  Object.assign(form, updatedForm);

  const fieldsToValidate = new Set();
  const fieldMap = {
    link: ['link'],
    name: ['name'],
    tagline: ['tagline'],
    description: ['description'],
    categories: ['categories'],
    categories_custom: ['categories'],
    useCases: ['useCases'],
    useCases_custom: ['useCases'],
    pricing: ['pricing'],
    pricing_page_url: ['pricing_page_url'],
    maker_links: ['maker_links'],
    badge_opt_in: ['badge_placement_url', 'badge_verified', 'badge_week_start'],
    badge_placement_url: ['badge_placement_url', 'badge_verified'],
    badge_verified: ['badge_verified', 'badge_week_start'],
    badge_week_start: ['badge_week_start'],
  };

  changedKeys.forEach((key) => {
    const mappedFields = fieldMap[key] || [];
    mappedFields.forEach((fieldKey) => {
      touchField(fieldKey);
      fieldsToValidate.add(fieldKey);
    });
  });

  fieldsToValidate.forEach((fieldKey) => validateField(fieldKey));
};

const handleFieldValidationRequest = (fieldKey) => {
  touchField(fieldKey);
  validateField(fieldKey);
};

const handleFocusField = (fieldKey) => {
  focusField(fieldKey);
};

const handleDescriptionRewrite = async () => {
  if (loadingStates.description || isLoading.value) {
    return;
  }

  await rewriteProductDescription();
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
  markManualLogoChosen();
  form.logo = logoUrl;
  logoPreview.value = logoUrl;
  touchField('logo');
  validateField('logo');
  isLogoPickerOpen.value = false;
};

const uploadLogoFile = (file) => {
  markManualLogoChosen();
  form.logo = file;
  touchField('logo');
  validateField('logo');

  const reader = new FileReader();
  reader.onload = (event) => {
    logoPreview.value = event.target.result;
    isLogoPickerOpen.value = false;
  };
  reader.readAsDataURL(file);
};

const uploadScreenshotFile = (file) => {
  markManualScreenshotChosen();
  form.gallery = [file];

  const reader = new FileReader();
  reader.onload = (event) => {
    galleryPreviews.value = [event.target.result];
  };
  reader.readAsDataURL(file);
};

const restoreFaviconLogo = () => {
  markManualLogoChosen(false);
  const restoredLogo = form.favicon || originalFavicon.value;
  if (!restoredLogo) {
    return;
  }

  form.logo = null;
  form.favicon = restoredLogo;
  logoPreview.value = restoredLogo;
  touchField('logo');
  validateField('logo');
  isLogoPickerOpen.value = false;
};

const removeSelectedLogo = () => {
  markManualLogoChosen();
  form.logo = null;
  form.favicon = null;
  logoPreview.value = null;
  touchField('logo');
  validateField('logo');
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
.autofill-locked-section {
  filter: blur(1.5px);
  opacity: 0.58;
  pointer-events: none;
  user-select: none;
}

</style>

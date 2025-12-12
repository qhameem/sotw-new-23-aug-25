import { reactive, toRefs, computed } from 'vue';
import { productFormService, createProductFormState } from '../services/productFormService';
import axios from 'axios';

// Create a global state for the product form
const globalFormState = createProductFormState();

export function useProductForm() {
  // Create reactive state
  const state = reactive({
    step: globalFormState.step,
    currentTab: globalFormState.currentTab,
    isRestored: globalFormState.isRestored,
    isMounted: globalFormState.isMounted,
    isLoading: globalFormState.isLoading,
    urlExistsError: globalFormState.urlExistsError,
    existingProduct: globalFormState.existingProduct,
    showPreviewModal: globalFormState.showPreviewModal,
    loadingStates: { ...globalFormState.loadingStates },
    logoPreview: globalFormState.logoPreview,
    galleryPreviews: [...globalFormState.galleryPreviews],
    allCategories: globalFormState.allCategories,
    allBestFor: globalFormState.allBestFor,
    allPricing: globalFormState.allPricing,
    allTechStacks: globalFormState.allTechStacks,
    form: { ...globalFormState.form },
    sidebarSteps: [...globalFormState.sidebarSteps],
    errorMessage: '',
    showErrorMessage: false,
  });

  // Computed properties
  const isUrlInvalid = computed(() => {
    return productFormService.isUrlInvalid(state.form.link);
 });

  const completionPercentage = computed(() => {
    // Calculate completion based on required fields
    // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
    const actualPricingCategories = (state.form.pricing || []).filter(id => !isNaN(id));
    
    const requiredFields = [
      state.form.link,
      state.form.name,
      state.form.tagline,
      state.form.tagline_detailed,
      state.form.description,
      state.form.categories && state.form.categories.length > 0,
      state.form.bestFor && state.form.bestFor.length > 0,
      actualPricingCategories.length > 0, // Only count actual pricing categories, not submission options
      state.logoPreview || (state.form.logos && state.form.logos.length > 0)
    ];
    
    const completedFields = requiredFields.filter(field => field).length;
    return Math.round((completedFields / requiredFields.length) * 100);
  });

  // Actions
  const getStarted = async () => {
    state.step = 2;
    
    // Dispatch step change event to update checklist visibility
    document.dispatchEvent(new CustomEvent('step-changed', {
      detail: { step: state.step }
    }));
    
    // Fetch initial data for the entered URL
    if (state.form.link && !state.form.name) {
      await fetchInitialData();
    }
  };

  const clearUrlInput = () => {
    state.showErrorMessage = false;
    state.form.link = '';
    state.urlExistsError = false;
    state.existingProduct = null;
  };

  const goBack = () => {
    state.showErrorMessage = false;
    state.step = 1;
    
    // Dispatch step change event to update checklist visibility
    document.dispatchEvent(new CustomEvent('step-changed', {
      detail: { step: state.step }
    }));
    
    // Clear form data when going back to URL input to ensure clean state for new URL
    state.form.name = '';
    state.form.tagline = '';
    state.form.tagline_detailed = '';
    state.form.description = '';
    state.form.favicon = '';
    state.form.logos = [];
    state.form.categories = [];
    state.form.bestFor = [];
    state.form.pricing = [];
    state.form.tech_stack = [];
    state.form.logo = null;
    state.form.gallery = Array(3).fill(null);
    state.form.video_url = '';
    state.form.maker_links = [];
    state.form.sell_product = false;
    state.form.asking_price = null;
    state.form.x_account = '';
    state.form.submissionOption = null;
    state.logoPreview = null;
    state.galleryPreviews = Array(3).fill(null);
  };

  const goToNextStep = (tabId) => {
    state.showErrorMessage = false;
    state.currentTab = tabId;
  };

  const submitProduct = () => {
    if (!validateForm()) {
      return false;
    }
    
    // Check if the selected submission option is 'free' to bypass the modal
    // If submission option is set to 'free', submit directly without showing modal
    if (state.form.submissionOption === 'free') {
      // Directly submit the product without showing modal
      confirmSubmit();
      return true;
    } else {
      // Show the preview modal for other submission options (like paid)
      state.showPreviewModal = true;
      return true;
    }
  };

  const confirmSubmit = async () => {
    if (!validateForm()) {
      return false;
    }
    
    try {
      state.isLoading = true;
      
      // Prepare form data for submission
      const formData = new FormData();
      
      // Add basic fields
      formData.append('name', state.form.name);
      formData.append('tagline', state.form.tagline);
      formData.append('product_page_tagline', state.form.tagline_detailed);
      formData.append('description', state.form.description);
      formData.append('link', state.form.link);
      // User ID is automatically set by the backend based on the authenticated user
      
      // Add categories - combine all category types (regular, bestFor, and pricing) into one array as expected by backend
      const allCategories = [];
      
      // Add regular categories
      if (state.form.categories && state.form.categories.length > 0) {
        const validCategories = state.form.categories.filter(id => id !== null && id !== undefined && id !== '');
        allCategories.push(...validCategories);
      }
      
      // Add bestFor categories
      if (state.form.bestFor && state.form.bestFor.length > 0) {
        const validBestFor = state.form.bestFor.filter(id => id !== null && id !== undefined && id !== '');
        allCategories.push(...validBestFor);
      }
      
      // Add pricing categories - these should be valid category IDs (not submission options like 'free' or 'paid')
      if (state.form.pricing && state.form.pricing.length > 0) {
        // Filter out non-numeric values like 'free' or 'paid' which are submission options, not category IDs
        const validPricing = state.form.pricing.filter(id => id !== null && id !== undefined && id !== '' && !isNaN(id));
        allCategories.push(...validPricing);
      }
      
      // Append all categories to formData
      allCategories.forEach((categoryId, index) => {
        formData.append(`categories[${index}]`, categoryId);
      });
      
      // Add tech stacks
      if (state.form.tech_stack && state.form.tech_stack.length > 0) {
        state.form.tech_stack.forEach((techStackId, index) => {
          formData.append(`tech_stacks[${index}]`, techStackId);
        });
      }
      
      
      // Add logo if available as file
      if (state.form.logo) {
        formData.append('logo', state.form.logo);
      } else if (state.logoPreview) {
        // If we have a logo preview URL, we might need to handle it differently
        // For now, we'll try to fetch it as a blob if it's a local file
        formData.append('logo_url', state.logoPreview);
      }
      
      // Add gallery images if available
      if (state.form.gallery && state.form.gallery.length > 0) {
        state.form.gallery.forEach((galleryImage, index) => {
          if (galleryImage) {
            formData.append(`media[${index}]`, galleryImage);
          }
        });
      }
      
      // Add video URL if available
      if (state.form.video_url) {
        formData.append('video_url', state.form.video_url);
      }
      
      // Add selling product info - ensure boolean value is sent in Laravel-accepted format
      if (state.form.sell_product !== undefined && state.form.sell_product !== null) {
        const sellProductValue = state.form.sell_product ? '1' : '0';
        formData.append('sell_product', sellProductValue);
        if (state.form.asking_price && state.form.sell_product) {
          formData.append('asking_price', state.form.asking_price);
        }
      } else {
        // If sell_product is undefined or null, default to '0' (false)
        formData.append('sell_product', '0');
      }
      
      // Add X account if available
      if (state.form.x_account) {
        formData.append('x_account', state.form.x_account);
      }
      
      // Submit the form
      const response = await axios.post('/products', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      
      // Clear form data after successful submission
      productFormService.clearFormData();
      
      // Redirect to success page
      if (response.data && response.data.redirect_url) {
        window.location.href = response.data.redirect_url;
      } else {
        // Fallback redirect
        window.location.href = `/products/submission-success/${response.data.product_id || ''}`;
      }
      
      return true;
    } catch (error) {
      console.error('Error submitting product:', error);
      state.showErrorMessage = true;
      state.errorMessage = error.response?.data?.message || 'Failed to submit product. Please try again.';
      return false;
    } finally {
      state.isLoading = false;
      state.showPreviewModal = false;
    }
  };

  const closeModal = () => {
    state.showPreviewModal = false;
  };

  const validateForm = () => {
    if (!state.form.link) {
      state.showErrorMessage = true;
      state.errorMessage = 'Product URL is required.';
      return false;
    }

    if (!state.form.name) {
      state.showErrorMessage = true;
      state.errorMessage = 'Product name is required.';
      return false;
    }

    if (!state.form.tagline) {
      state.showErrorMessage = true;
      state.errorMessage = 'Tagline is required.';
      return false;
    }

    if (!state.form.tagline_detailed) {
      state.showErrorMessage = true;
      state.errorMessage = 'Product details page tagline is required.';
      return false;
    }

    if (!state.form.description) {
      state.showErrorMessage = true;
      state.errorMessage = 'Description is required.';
      return false;
    }

    // Validate categories: minimum 1, maximum 3
    const validCategories = (state.form.categories || []).filter(id => id !== null && id !== undefined && id !== '');
    if (validCategories.length === 0) {
      state.showErrorMessage = true;
      state.errorMessage = 'At least one category is required.';
      return false;
    }
    if (validCategories.length > 3) {
      state.showErrorMessage = true;
      state.errorMessage = 'Maximum 3 categories allowed.';
      return false;
    }

    // Validate bestFor: minimum 1, maximum 3
    const validBestFor = (state.form.bestFor || []).filter(id => id !== null && id !== undefined && id !== '');
    if (validBestFor.length === 0) {
      state.showErrorMessage = true;
      state.errorMessage = 'At least one "best for" option is required.';
      return false;
    }
    if (validBestFor.length > 3) {
      state.showErrorMessage = true;
      state.errorMessage = 'Maximum 3 "best for" options allowed.';
      return false;
    }

    // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
    const actualPricingCategories = (state.form.pricing || []).filter(id => !isNaN(id));
    if (actualPricingCategories.length === 0) {
      state.showErrorMessage = true;
      state.errorMessage = 'At least one pricing model is required.';
      return false;
    }

    if (!state.logoPreview && (!state.form.logos || state.form.logos.length === 0)) {
      state.showErrorMessage = true;
      state.errorMessage = 'A logo is required.';
      return false;
    }

    state.showErrorMessage = false;
    return true;
  };

  const fetchInitialData = async () => {
    if (!state.form.link) return;

    state.loadingStates.name = true;
    state.isLoading = true;

    try {
      const response = await axios.post('/api/fetch-initial-metadata', { url: state.form.link });
      const data = response.data;

      state.form.name = data.name;
      state.form.tagline_detailed = data.tagline;
      state.form.favicon = data.favicon;

      state.loadingStates.name = false;
    } catch (error) {
      console.error('Error fetching initial metadata:', error);
      state.loadingStates.name = false;
      state.showErrorMessage = true;
      state.errorMessage = 'Failed to fetch product metadata. Please check the URL and try again.';
      state.isLoading = false;
    }
 };

  const fetchRemainingData = async () => {
    const shouldFetchContent = !state.form.tagline && !state.form.tagline_detailed && !state.form.description;
    const shouldFetchCategoriesAndBestFor = !state.form.categories || state.form.categories.length === 0 || !state.form.bestFor || state.form.bestFor.length === 0;

    if (shouldFetchContent) {
      state.loadingStates.description = true;
    }
    if (shouldFetchCategoriesAndBestFor) {
      state.loadingStates.categories = true;
      state.loadingStates.bestFor = true;
    }
    state.loadingStates.logos = true;

    try {
      const response = await axios.post('/api/process-url', {
        url: state.form.link,
        name: state.form.name,
        tagline: state.form.tagline,
        fetch_content: shouldFetchContent,
      });
      const data = response.data;

      if (shouldFetchContent) {
        state.form.tagline_detailed = data.tagline_detailed;
        state.form.description = data.description;
      }
      state.form.logos = data.logos;
      state.form.categories = data.categories || [];
      state.form.bestFor = data.bestFor || [];
    } catch (error) {
      console.error('Error fetching remaining data:', error);
      state.showErrorMessage = true;
      state.errorMessage = 'Failed to fetch additional product data. Please check the URL and try again.';
    } finally {
      Object.keys(state.loadingStates).forEach(k => state.loadingStates[k] = false);
      state.isLoading = false;
    }
  };

  const updateForm = (field, value) => {
    state.form[field] = value;
  };

  const updateFormMultiple = (updates) => {
    Object.keys(updates).forEach(key => {
      state.form[key] = updates[key];
    });
 };

  const resetForm = () => {
    state.form = { ...createProductFormState().form };
    state.logoPreview = null;
    state.galleryPreviews = Array(3).fill(null);
  };

  // Initialize form data
  const initializeFormData = async () => {
    try {
      const [categoriesResponse, techStackResponse] = await Promise.all([
        axios.get('/api/categories'),
        axios.get('/api/tech-stacks')
      ]);

      state.allCategories = categoriesResponse.data.categories;
      state.allBestFor = categoriesResponse.data.bestFor;
      state.allPricing = categoriesResponse.data.pricing;
      state.allTechStacks = techStackResponse.data;
    } catch (error) {
      console.error('Failed to fetch initial form data:', error);
      state.showErrorMessage = true;
      state.errorMessage = 'Failed to load form options. Some features may not work properly.';
    }
  };

  // Load saved data from session storage
  const loadSavedData = () => {
    const savedData = productFormService.loadFormData();
    if (savedData) {
      if (savedData.link) {
        updateFormMultiple(savedData);
        state.logoPreview = savedData.logoPreview || null;
        state.galleryPreviews = savedData.galleryPreviews || Array(3).fill(null);
        if (savedData.name) {
          state.step = 2;
        }
      }
    }
  };

  // Save form data to session storage
  const saveFormData = () => {
    productFormService.saveFormData(state.form, state.logoPreview, state.galleryPreviews);
  };

  return {
    ...toRefs(state),
    isUrlInvalid,
    completionPercentage,
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
    updateForm,
    updateFormMultiple,
    resetForm,
    initializeFormData,
    loadSavedData,
    saveFormData
  };
}
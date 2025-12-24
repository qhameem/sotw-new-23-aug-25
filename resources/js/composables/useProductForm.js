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

  const checkUrlExists = async () => {
    console.log('checkUrlExists called with URL:', state.form.link);
    if (!state.form.link) {
      state.urlExistsError = false;
      state.existingProduct = null;
      return;
    }

    try {
      const response = await productFormService.checkUrlExists(state.form.link);
      console.log('checkUrlExists response:', response);
      if (response.exists) {
        state.urlExistsError = true;
        state.existingProduct = response.product;
        // Don't show the general error message since it's now handled in the ProductURLInput component
        state.showErrorMessage = false;
        state.errorMessage = `This URL already exists as "${response.product.name}". You cannot add the same product twice.`;
      } else {
        state.urlExistsError = false;
        state.existingProduct = null;
        state.showErrorMessage = false;
      }
      console.log('Updated state - urlExistsError:', state.urlExistsError, 'existingProduct:', state.existingProduct);
    } catch (error) {
      console.error('Error checking URL existence:', error);
      state.urlExistsError = false;
      state.existingProduct = null;
    }
  };

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
    console.log('getStarted called, form link:', state.form.link, 'urlExistsError:', state.urlExistsError);
    // First check if URL already exists
    if (state.form.link) {
      await checkUrlExists();
      
      // If URL exists, don't proceed
      if (state.urlExistsError) {
        console.log('URL exists, preventing progression');
        // Don't show the general error message since it's now handled in the ProductURLInput component
        state.showErrorMessage = false;
        state.errorMessage = `This URL already exists as "${state.existingProduct.name}". You cannot add the same product twice.`;
        return false;
      }
    }
    
    console.log('Proceeding to step 2');
    state.step = 2;
    
    // Dispatch step change event to update checklist visibility
    document.dispatchEvent(new CustomEvent('step-changed', {
      detail: { step: state.step }
    }));
    
    // Fetch initial data for the entered URL
    if (state.form.link && !state.form.name) {
      await fetchInitialData();
    }
    
    return true;
  };

  const clearUrlInput = () => {
    state.showErrorMessage = false;
    state.form.link = '';
    state.urlExistsError = false;
    state.existingProduct = null;
    // Also reset any URL-related validation state
    state.errorMessage = '';
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
      // Set loading state to show the loader
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
      
      // Dispatch an event to notify other components of successful submission
      document.dispatchEvent(new CustomEvent('product-submitted', {
        detail: {
          productId: response.data.product_id,
          message: 'Product submitted successfully'
        }
      }));
      
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
      // Always reset loading state after submission
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

    // Check if URL already exists before allowing submission
    if (state.urlExistsError) {
      // Don't show the general error message since it's now handled in the ProductURLInput component
      state.showErrorMessage = false;
      state.errorMessage = `This URL already exists as "${state.existingProduct.name}". You cannot add the same product twice.`;
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

  const fetchRemainingData = async (explicitLogoExtraction = false) => {
    const shouldFetchContent = !state.form.tagline && !state.form.tagline_detailed && !state.form.description;
    const shouldFetchCategoriesAndBestFor = !state.form.categories || state.form.categories.length === 0 || !state.form.bestFor || state.form.bestFor.length === 0;
  
    // Always fetch logos if we have a link and name, regardless of other content
    // If explicitLogoExtraction is true, always attempt to fetch logos even if they exist
    const shouldFetchLogos = state.form.link && state.form.name && (explicitLogoExtraction || (!state.form.logos || state.form.logos.length === 0));
  
    // Only proceed if we have a valid link and name
    if (!state.form.link || !state.form.name) {
      Object.keys(state.loadingStates).forEach(k => state.loadingStates[k] = false);
      state.isLoading = false;
      return;
    }
  
    if (shouldFetchContent) {
      state.loadingStates.description = true;
    }
    if (shouldFetchCategoriesAndBestFor) {
      state.loadingStates.categories = true;
      state.loadingStates.bestFor = true;
    }
    if (shouldFetchLogos) {
      state.loadingStates.logos = true;
    }
  
    try {
      // Make the API call with a timeout
      const response = await axios.post('/api/process-url', {
        url: state.form.link,
        name: state.form.name,
        tagline: state.form.tagline,
        fetch_content: shouldFetchContent,
      }, {
        timeout: 30000 // 30 second timeout
      });
      const data = response.data;

      if (shouldFetchContent) {
        state.form.tagline_detailed = data.tagline_detailed;
        state.form.description = data.description;
      }
      // Always update logos if we received them, regardless of whether we explicitly requested them
      if (data.logos && Array.isArray(data.logos)) {
        state.form.logos = data.logos;
      }
      if (shouldFetchCategoriesAndBestFor) {
        state.form.categories = data.categories || [];
        state.form.bestFor = data.bestFor || [];
        state.form.pricing = data.pricing || [];
      }
    } catch (error) {
      console.error('Error fetching remaining data:', error);
      // Check if it's a timeout error
      const isTimeoutError = error.code === 'ECONNABORTED' || (error.response && error.response.status === 408);
      
      // Only show error message if this is during active form filling, not during restoration
      if (shouldFetchContent || shouldFetchCategoriesAndBestFor) {
        state.showErrorMessage = true;
        if (isTimeoutError) {
          state.errorMessage = 'Logo extraction timed out. Please try again later.';
        } else {
          state.errorMessage = 'Failed to fetch additional product data. You can continue filling the form manually.';
        }
      }
      // Still allow the form to continue working even if data fetching fails
      // Make sure to log any specific error for logo extraction to help with debugging
      if (shouldFetchLogos) {
        console.error('Error during logo extraction:', error.message || error);
      }
    } finally {
      // Always reset the specific loading states that were requested
      // This ensures that even if the API call fails, the loading state is properly reset
      if (shouldFetchContent) {
        state.loadingStates.description = false;
      }
      if (shouldFetchCategoriesAndBestFor) {
        state.loadingStates.categories = false;
        state.loadingStates.bestFor = false;
      }
      if (shouldFetchLogos || explicitLogoExtraction) {
        // Always reset the logos loading state if we attempted to fetch logos
        state.loadingStates.logos = false;
      }
      state.isLoading = false;
    }
  };

  const updateForm = async (field, value) => {
    console.log('updateForm called for field:', field, 'with value:', value);
    state.form[field] = value;
    
    // Check URL existence when the link field is updated
    if (field === 'link') {
      console.log('Link field updated, calling checkUrlExists');
      await checkUrlExists();
    }
 };

  const updateFormMultiple = async (updates) => {
    Object.keys(updates).forEach(key => {
      state.form[key] = updates[key];
    });
    
    // Check URL existence if the link field was updated
    if (updates.link !== undefined) {
      await checkUrlExists();
    }
  };

  const resetForm = () => {
    state.form = { ...createProductFormState().form };
    state.logoPreview = null;
    state.galleryPreviews = Array(3).fill(null);
    // Reset URL validation state when form is reset
    state.urlExistsError = false;
    state.existingProduct = null;
    state.showErrorMessage = false;
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
    
    // Load saved data after initializing form options
    await loadSavedData();
  };

  // Load saved data from session storage
  const loadSavedData = async () => {
    const savedData = productFormService.loadFormData();
    if (savedData) {
      if (savedData.link) {
        await updateFormMultiple(savedData);
        state.logoPreview = savedData.logoPreview || null;
        state.galleryPreviews = savedData.galleryPreviews || Array(3).fill(null);
        if (savedData.name) {
          state.step = 2;
        }
        // Check for URL existence when loading saved data with a link
        await checkUrlExists();
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
    saveFormData,
    checkUrlExists
  };
}
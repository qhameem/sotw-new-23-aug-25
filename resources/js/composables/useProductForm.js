import { reactive, computed, ref, toRefs } from 'vue';
import { productFormService, createProductFormState } from '../services/productFormService';
import axios from 'axios';

// Create a global state for the product form
const globalFormState = createProductFormState();

export function useProductForm() {
  // Use global state directly to avoid ref conflicts
  const form = reactive({ ...globalFormState.form });
  const loadingStates = { ...globalFormState.loadingStates };
  const sidebarSteps = [...globalFormState.sidebarSteps];

  // Create local refs for form-specific error messages to avoid conflicts
  const errorMessage = ref('');
  const showErrorMessage = ref(false);

  // Computed properties
  const isUrlInvalid = computed(() => {
    return productFormService.isUrlInvalid(form.link);
  });

  const checkUrlExists = async () => {
    console.log('checkUrlExists called with URL:', form.link);
    if (!form.link) {
      globalFormState.urlExistsError.value = false;
      globalFormState.existingProduct.value = null;
      return;
    }

    try {
      const response = await productFormService.checkUrlExists(form.link);
      console.log('checkUrlExists response:', response);
      if (response.exists) {
        globalFormState.urlExistsError.value = true;
        globalFormState.existingProduct.value = response.product;
        // Don't show the general error message since it's now handled in the ProductURLInput component
        showErrorMessage.value = false;
        errorMessage.value = `This URL already exists as "${response.product.name}". You cannot add the same product twice.`;
      } else {
        globalFormState.urlExistsError.value = false;
        globalFormState.existingProduct.value = null;
        showErrorMessage.value = false;
      }
      console.log('Updated state - urlExistsError:', globalFormState.urlExistsError.value, 'existingProduct:', globalFormState.existingProduct.value);
    } catch (error) {
      console.error('Error checking URL existence:', error);
      globalFormState.urlExistsError.value = false;
      globalFormState.existingProduct.value = null;
    }
  };

  const completionPercentage = computed(() => {
    // Calculate completion based on required fields
    // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
    const actualPricingCategories = (form.pricing || []).filter(id => !isNaN(id));

    const requiredFields = [
      form.link,
      form.name,
      form.tagline,
      form.tagline_detailed,
      form.description,
      form.categories && form.categories.length > 0,
      form.bestFor && form.bestFor.length > 0,
      actualPricingCategories.length > 0, // Only count actual pricing categories, not submission options
      globalFormState.logoPreview.value || (form.logos && form.logos.length > 0)
    ];

    const completedFields = requiredFields.filter(field => field).length;
    return Math.round((completedFields / requiredFields.length) * 100);
  });

  // Actions
  const getStarted = async () => {
    console.log('getStarted called, form link:', form.link, 'urlExistsError:', globalFormState.urlExistsError.value);
    // First check if URL already exists
    if (form.link) {
      await checkUrlExists();

      // If URL exists, don't proceed
      if (globalFormState.urlExistsError.value) {
        console.log('URL exists, preventing progression');
        // Don't show the general error message since it's now handled in the ProductURLInput component
        showErrorMessage.value = false;
        errorMessage.value = `This URL already exists as "${globalFormState.existingProduct.value.name}". You cannot add the same product twice.`;
        return false;
      }
    }

    console.log('Proceeding to step 2');
    globalFormState.step.value = 2;

    // Dispatch step change event to update checklist visibility
    document.dispatchEvent(new CustomEvent('step-changed', {
      detail: { step: globalFormState.step.value }
    }));

    // Fetch initial data for the entered URL in the background
    if (form.link && !form.name) {
      fetchInitialData();
    }

    return true;
  };

  const clearUrlInput = () => {
    showErrorMessage.value = false;
    form.link = '';
    globalFormState.urlExistsError.value = false;
    globalFormState.existingProduct.value = null;
    // Also reset any URL-related validation state
    errorMessage.value = '';
  };

  const goBack = () => {
    showErrorMessage.value = false;
    globalFormState.step.value = 1;

    // Dispatch step change event to update checklist visibility
    document.dispatchEvent(new CustomEvent('step-changed', {
      detail: { step: globalFormState.step.value }
    }));

    // Clear form data when going back to URL input to ensure clean state for new URL
    form.name = '';
    form.tagline = '';
    form.tagline_detailed = '';
    form.description = '';
    form.favicon = '';
    form.logos = [];
    form.categories = [];
    form.bestFor = [];
    form.pricing = [];
    form.tech_stack = [];
    form.logo = null;
    form.gallery = Array(3).fill(null);
    form.video_url = '';
    form.maker_links = [];
    form.sell_product = false;
    form.asking_price = null;
    form.x_account = '';
    form.submissionOption = null;
    globalFormState.logoPreview.value = null;
    globalFormState.galleryPreviews.value = Array(3).fill(null);
  };

  const goToNextStep = (tabId) => {
    showErrorMessage.value = false;
    globalFormState.currentTab.value = tabId;
  };

  const submitProduct = () => {
    if (!validateForm()) {
      return false;
    }

    // Check if the selected submission option is 'free' to bypass the modal
    // If submission option is set to 'free', submit directly without showing modal
    if (form.submissionOption === 'free') {
      // Directly submit the product without showing modal
      confirmSubmit();
      return true;
    } else {
      // Show the preview modal for other submission options (like paid)
      globalFormState.showPreviewModal.value = true;
      return true;
    }
  };

  const confirmSubmit = async () => {
    if (!validateForm()) {
      return false;
    }

    try {
      // Set loading state to show the loader
      globalFormState.isLoading.value = true;

      // Prepare form data for submission
      const formData = new FormData();

      // Add basic fields
      formData.append('name', form.name);
      formData.append('tagline', form.tagline);
      formData.append('product_page_tagline', form.tagline_detailed);
      formData.append('description', form.description);
      formData.append('link', form.link);
      // User ID is automatically set by the backend based on the authenticated user

      // Add categories - combine all category types (regular, bestFor, and pricing) into one array as expected by backend
      const allCategories = [];

      // Add regular categories
      if (form.categories && form.categories.length > 0) {
        const validCategories = form.categories.filter(id => id !== null && id !== undefined && id !== '');
        allCategories.push(...validCategories);
      }

      // Add bestFor categories
      if (form.bestFor && form.bestFor.length > 0) {
        const validBestFor = form.bestFor.filter(id => id !== null && id !== undefined && id !== '');
        allCategories.push(...validBestFor);
      }

      // Add pricing categories - these should be valid category IDs (not submission options like 'free' or 'paid')
      if (form.pricing && form.pricing.length > 0) {
        // Filter out non-numeric values like 'free' or 'paid' which are submission options, not category IDs
        const validPricing = form.pricing.filter(id => id !== null && id !== undefined && id !== '' && !isNaN(id));
        allCategories.push(...validPricing);
      }

      // Append all categories to formData
      allCategories.forEach((categoryId, index) => {
        formData.append(`categories[${index}]`, categoryId);
      });

      // Add tech stacks
      if (form.tech_stack && form.tech_stack.length > 0) {
        form.tech_stack.forEach((techStackId, index) => {
          formData.append(`tech_stacks[${index}]`, techStackId);
        });
      }

      // Add logo if available as file
      if (form.logo) {
        formData.append('logo', form.logo);
      } else if (globalFormState.logoPreview.value) {
        // If we have a logo preview URL, we might need to handle it differently
        // For now, we'll try to fetch it as a blob if it's a local file
        formData.append('logo_url', globalFormState.logoPreview.value);
      }

      // Add gallery images if available
      if (form.gallery && form.gallery.length > 0) {
        form.gallery.forEach((galleryImage, index) => {
          if (galleryImage) {
            formData.append(`media[${index}]`, galleryImage);
          }
        });
      }

      // Add video URL if available
      if (form.video_url) {
        formData.append('video_url', form.video_url);
      }

      // Add selling product info - ensure boolean value is sent in Laravel-accepted format
      if (form.sell_product !== undefined && form.sell_product !== null) {
        const sellProductValue = form.sell_product ? '1' : '0';
        formData.append('sell_product', sellProductValue);
        if (form.asking_price && form.sell_product) {
          formData.append('asking_price', form.asking_price);
        }
      } else {
        // If sell_product is undefined or null, default to '0' (false)
        formData.append('sell_product', '0');
      }

      // Add X account if available
      if (form.x_account) {
        formData.append('x_account', form.x_account);
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
      showErrorMessage.value = true;
      errorMessage.value = error.response?.data?.message || 'Failed to submit product. Please try again.';
      return false;
    } finally {
      // Always reset loading state after submission
      globalFormState.isLoading.value = false;
      globalFormState.showPreviewModal.value = false;
    }
  };

  const closeModal = () => {
    globalFormState.showPreviewModal.value = false;
  };

  const validateForm = () => {
    if (!form.link) {
      showErrorMessage.value = true;
      errorMessage.value = 'Product URL is required.';
      return false;
    }

    // Check if URL already exists before allowing submission
    if (globalFormState.urlExistsError.value) {
      // Don't show the general error message since it's now handled in the ProductURLInput component
      showErrorMessage.value = false;
      errorMessage.value = `This URL already exists as "${globalFormState.existingProduct.value.name}". You cannot add the same product twice.`;
      return false;
    }

    if (!form.name) {
      showErrorMessage.value = true;
      errorMessage.value = 'Product name is required.';
      return false;
    }

    if (!form.tagline) {
      showErrorMessage.value = true;
      errorMessage.value = 'Tagline is required.';
      return false;
    }

    if (!form.tagline_detailed) {
      showErrorMessage.value = true;
      errorMessage.value = 'Product details page tagline is required.';
      return false;
    }

    if (!form.description) {
      showErrorMessage.value = true;
      errorMessage.value = 'Description is required.';
      return false;
    }

    // Validate categories: minimum 1, maximum 3
    const validCategories = (form.categories || []).filter(id => id !== null && id !== undefined && id !== '');
    if (validCategories.length === 0) {
      showErrorMessage.value = true;
      errorMessage.value = 'At least one category is required.';
      return false;
    }
    if (validCategories.length > 3) {
      showErrorMessage.value = true;
      errorMessage.value = 'Maximum 3 categories allowed.';
      return false;
    }

    // Validate bestFor: minimum 1, maximum 3
    const validBestFor = (form.bestFor || []).filter(id => id !== null && id !== undefined && id !== '');
    if (validBestFor.length === 0) {
      showErrorMessage.value = true;
      errorMessage.value = 'At least one "best for" option is required.';
      return false;
    }
    if (validBestFor.length > 3) {
      showErrorMessage.value = true;
      errorMessage.value = 'Maximum 3 "best for" options allowed.';
      return false;
    }

    // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
    const actualPricingCategories = (form.pricing || []).filter(id => !isNaN(id));
    if (actualPricingCategories.length === 0) {
      showErrorMessage.value = true;
      errorMessage.value = 'At least one pricing model is required.';
      return false;
    }

    if (!globalFormState.logoPreview.value && (!form.logos || form.logos.length === 0)) {
      showErrorMessage.value = true;
      errorMessage.value = 'A logo is required.';
      return false;
    }

    showErrorMessage.value = false;
    return true;
  };

  const fetchInitialData = async () => {
    const linkValue = form.link;
    console.log('fetchInitialData called with link:', linkValue);

    if (!linkValue || linkValue.trim() === '') {
      console.log('No link provided to fetchInitialData, returning early');
      return;
    }

    loadingStates.name = true;
    globalFormState.isLoading.value = true;

    try {
      const response = await axios.post('/api/fetch-initial-metadata', { url: linkValue });
      const data = response.data;

      console.log('fetchInitialData response:', data);

      form.name = data.name;
      form.tagline = data.tagline;
      form.tagline_detailed = data.tagline_detailed || data.tagline; // Use tagline_detailed if provided, otherwise fallback to tagline
      form.favicon = data.favicon;

      // Auto-set favicon as logo if available
      if (data.favicon) {
        globalFormState.logoPreview.value = data.favicon;
      }

      loadingStates.name = false;
    } catch (error) {
      console.error('Error fetching initial metadata:', error);
      loadingStates.name = false;
      showErrorMessage.value = true;
      errorMessage.value = 'Failed to fetch product metadata. Please check the URL and try again.';
      globalFormState.isLoading.value = false;
    }
  };

  const fetchRemainingData = async (explicitLogoExtraction = false) => {
    console.log('fetchRemainingData called', {
      explicitLogoExtraction,
      link: form.link,
      linkType: typeof form.link,
      linkTruthy: !!form.link,
      name: form.name
    });

    const shouldFetchContent = !form.tagline && !form.tagline_detailed && !form.description;
    const shouldFetchCategoriesAndBestFor = !form.categories || form.categories.length === 0 || !form.bestFor || form.bestFor.length === 0;

    // Always fetch logos if we have a link and name, regardless of other content
    // If explicitLogoExtraction is true, always attempt to fetch logos even if they exist
    const shouldFetchLogos = form.link && (explicitLogoExtraction || (!form.logos || form.logos.length === 0));

    console.log('Should fetch checks:', { shouldFetchContent, shouldFetchCategoriesAndBestFor, shouldFetchLogos });

    // Only proceed if we have a valid link (name is not strictly required for explicit logo extraction)
    const linkValue = form.link;
    if (!linkValue || linkValue.trim() === '') {
      console.log('No link provided or link is empty, returning early');
      Object.keys(loadingStates).forEach(k => loadingStates[k] = false);
      globalFormState.isLoading.value = false;
      return;
    }

    if (shouldFetchContent) {
      loadingStates.description = true;
      console.log('Setting description loading state to true');
    }
    if (shouldFetchCategoriesAndBestFor) {
      loadingStates.categories = true;
      loadingStates.bestFor = true;
      console.log('Setting categories and bestFor loading states to true');
    }
    if (shouldFetchLogos) {
      loadingStates.logos = true;
      console.log('Setting logos loading state to true');
    }

    try {
      const linkValue = form.link;
      const nameValue = form.name || '';
      const taglineValue = form.tagline || '';

      console.log('Making API call to /api/process-url with:', {
        url: linkValue,
        name: nameValue,
        tagline: taglineValue,
        fetch_content: shouldFetchContent
      });

      // Make the API call with a timeout
      const response = await axios.post('/api/process-url', {
        url: linkValue,
        name: nameValue, // Pass empty string if name is not available
        tagline: taglineValue, // Pass empty string if tagline is not available
        fetch_content: shouldFetchContent,
      }, {
        timeout: 30000 // 30 second timeout
      });
      console.log('API response received:', response.data);

      const data = response.data;

      if (shouldFetchContent) {
        form.tagline = data.tagline || form.tagline; // Only update if we received a new value
        form.tagline_detailed = data.tagline_detailed || form.tagline_detailed; // Only update if we received a new value
        form.description = data.description || form.description; // Only update if we received a new value
      }
      // Always update logos if we received them, regardless of whether we explicitly requested them
      if (data.logos && Array.isArray(data.logos)) {
        console.log('Updating logos with data:', data.logos);
        form.logos = data.logos;
      }
      if (shouldFetchCategoriesAndBestFor) {
        form.categories = data.categories || [];
        form.bestFor = data.bestFor || [];
        form.pricing = data.pricing || [];
      }
    } catch (error) {
      console.error('Error fetching remaining data:', error);
      // Check if it's a timeout error
      const isTimeoutError = error.code === 'ECONNABORTED' || (error.response && error.response.status === 408);

      // Only show error message if this is during active form filling, not during restoration
      if (shouldFetchContent || shouldFetchCategoriesAndBestFor) {
        showErrorMessage.value = true;
        if (isTimeoutError) {
          errorMessage.value = 'Logo extraction timed out. Please try again later.';
        } else {
          errorMessage.value = 'Failed to fetch additional product data. You can continue filling the form manually.';
        }
      }
      // Still allow the form to continue working even if data fetching fails
      // Make sure to log any specific error for logo extraction to help with debugging
      if (shouldFetchLogos) {
        console.error('Error during logo extraction:', error.message || error);
      }
    } finally {
      console.log('In finally block, resetting loading states');
      // Always reset the specific loading states that were requested
      // This ensures that even if the API call fails, the loading state is properly reset
      if (shouldFetchContent) {
        loadingStates.description = false;
        console.log('Resetting description loading state to false');
      }
      if (shouldFetchCategoriesAndBestFor) {
        loadingStates.categories = false;
        loadingStates.bestFor = false;
        console.log('Resetting categories and bestFor loading states to false');
      }
      if (shouldFetchLogos || explicitLogoExtraction) {
        // Always reset the logos loading state if we attempted to fetch logos
        loadingStates.logos = false;
        console.log('Resetting logos loading state to false');
      }
      // Only set the general isLoading to false if no other loading operations are in progress
      // Check if any other loading states are still active
      const anyLoadingActive = Object.values(loadingStates).some(loading => loading === true);
      if (!anyLoadingActive) {
        globalFormState.isLoading.value = false;
        console.log('Resetting general isLoading to false');
      }
    }
  };

  const updateForm = async (field, value) => {
    console.log('updateForm called for field:', field, 'with value:', value);
    form[field] = value;

    // Check URL existence when the link field is updated
    if (field === 'link') {
      console.log('Link field updated, calling checkUrlExists');
      await checkUrlExists();
    }
  };

  const updateFormMultiple = async (updates) => {
    // Update each field individually to ensure reactivity
    Object.keys(updates).forEach(key => {
      if (form.hasOwnProperty(key)) {
        if (Array.isArray(updates[key])) {
          form[key] = [...updates[key]]; // Create a new array to ensure reactivity
        } else {
          form[key] = updates[key];
        }
      }
    });

    // Check URL existence if the link field was updated
    if (updates.link !== undefined) {
      await checkUrlExists();
    }
  };

  const resetForm = () => {
    Object.assign(form, { ...createProductFormState().form });
    globalFormState.logoPreview.value = null;
    globalFormState.galleryPreviews.value = Array(3).fill(null);
    // Reset URL validation state when form is reset
    globalFormState.urlExistsError.value = false;
    globalFormState.existingProduct.value = null;
    showErrorMessage.value = false;
  };

  // Initialize form data
  const initializeFormData = async () => {
    try {
      const [categoriesResponse, techStackResponse] = await Promise.all([
        axios.get('/api/categories'),
        axios.get('/api/tech-stacks')
      ]);

      globalFormState.allCategories.value = categoriesResponse.data.categories;
      globalFormState.allBestFor.value = categoriesResponse.data.bestFor;
      globalFormState.allPricing.value = categoriesResponse.data.pricing;
      globalFormState.allTechStacks.value = techStackResponse.data;
    } catch (error) {
      console.error('Failed to fetch initial form data:', error);
      showErrorMessage.value = true;
      errorMessage.value = 'Failed to load form options. Some features may not work properly.';
    }

    // Load initial data from the HTML element attributes first (for editing existing products)
    // This ensures that if we're editing an existing product, we load that data first
    loadInitialDataFromElement();

    // Then load saved data (from session storage) which might override initial data
    // Only load saved data if we're not editing an existing product (to prevent override)
    const element = document.getElementById('product-submit-app');
    if (element) {
      const displayData = element.getAttribute('data-display-data');
      const isAdmin = element.getAttribute('data-is-admin');
      // If we're an admin editing an existing product, don't load saved data as it may override the loaded product data
      if (!(isAdmin === 'true' && displayData)) {
        await loadSavedData();
      }
    } else {
      // If element is not available yet, try loading saved data after a short delay
      setTimeout(async () => {
        await loadSavedData();
      }, 100);
    }
  };

  // Load initial data from HTML element attributes (for editing existing products)
  const loadInitialDataFromElement = () => {
    // Try to load immediately
    tryLoadInitialData();

    // If element is not found, set up a MutationObserver to wait for it to be added to the DOM
    if (!document.getElementById('product-submit-app')) {
      const observer = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
          if (mutation.type === 'childList') {
            const element = document.getElementById('product-submit-app');
            if (element) {
              observer.disconnect(); // Stop observing once we find the element
              tryLoadInitialData();
              return;
            }
          }
        }
      });

      // Start observing
      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
    }
  };

  // Helper function to try loading initial data
  const tryLoadInitialData = () => {
    const element = document.getElementById('product-submit-app');
    if (element) {
      console.log('Found product-submit-app element, attempting to load initial data');

      // Get data attributes from the element
      const displayData = element.getAttribute('data-display-data');
      const isAdmin = element.getAttribute('data-is-admin');
      const allPricing = element.getAttribute('data-pricing-categories');
      const selectedBestForCategories = element.getAttribute('data-selected-best-for-categories');

      console.log('Data attributes:', { displayData, isAdmin, allPricing, selectedBestForCategories });

      if (displayData) {
        try {
          const initialData = JSON.parse(displayData);
          console.log('Parsed initial data:', initialData);

          // For admin users, always load the original product data regardless of pending edits
          if (isAdmin === 'true') {
            console.log('Loading data for admin user');

            // Load the original product data
            // The current_categories from the controller contains all categories (regular, pricing, bestFor)
            // We need to separate them based on their types
            let allCategoryIds = initialData.current_categories || [];
            let pricingCategoryIds = [];
            let regularCategoryIds = [];

            if (allCategoryIds.length > 0 && allPricing) {
              try {
                const pricingCats = JSON.parse(allPricing);
                const pricingCatIds = pricingCats.map(cat => parseInt(cat.id));

                // Separate pricing and regular categories
                pricingCategoryIds = allCategoryIds.filter(catId =>
                  pricingCatIds.includes(parseInt(catId))
                );
                regularCategoryIds = allCategoryIds.filter(catId =>
                  !pricingCatIds.includes(parseInt(catId))
                );
              } catch (e) {
                console.error('Error parsing pricing categories:', e);
                // If parsing fails, treat all as regular categories
                regularCategoryIds = allCategoryIds;
              }
            } else {
              // If no pricing categories are available, assume all are regular categories
              regularCategoryIds = allCategoryIds;
            }

            console.log('Category IDs - Regular:', regularCategoryIds, 'Pricing:', pricingCategoryIds);

            updateFormMultiple({
              name: initialData.name,
              tagline: initialData.tagline,
              tagline_detailed: initialData.product_page_tagline || initialData.tagline_detailed,
              description: initialData.description,
              link: initialData.link,
              categories: regularCategoryIds,
              bestFor: JSON.parse(selectedBestForCategories || '[]'),
              pricing: pricingCategoryIds,
              tech_stack: initialData.current_tech_stacks || [],
              video_url: initialData.video_url,
            });

            console.log('Updated form with initial data for admin');

            // Set step to 2 to show the form
            globalFormState.step.value = 2;
          } else {
            console.log('Loading data for regular user');

            // For regular users, load data as appropriate
            updateFormMultiple({
              name: initialData.name,
              tagline: initialData.tagline,
              tagline_detailed: initialData.product_page_tagline || initialData.tagline_detailed,
              description: initialData.description,
              link: initialData.link,
              categories: initialData.current_categories || [],
              bestFor: JSON.parse(selectedBestForCategories || '[]'),
              pricing: initialData.current_categories ? [] : [], // This might need to be handled differently
              tech_stack: initialData.current_tech_stacks || [],
              video_url: initialData.video_url,
            });

            // Set step to 2 to show the form
            globalFormState.step.value = 2;
          }
        } catch (error) {
          console.error('Error parsing initial data from element:', error);
          // Fallback: try to initialize with basic data
          const element = document.getElementById('product-submit-app');
          if (element) {
            const displayData = element.getAttribute('data-display-data');
            const isAdmin = element.getAttribute('data-is-admin');
            const selectedBestForCategories = element.getAttribute('data-selected-best-for-categories');

            if (displayData) {
              try {
                const initialData = JSON.parse(displayData);
                if (isAdmin === 'true') {
                  updateFormMultiple({
                    name: initialData.name || '',
                    tagline: initialData.tagline || '',
                    tagline_detailed: initialData.product_page_tagline || initialData.tagline_detailed || '',
                    description: initialData.description || '',
                    link: initialData.link || '',
                    categories: initialData.current_categories || [],
                    bestFor: JSON.parse(selectedBestForCategories || '[]'),
                    pricing: [],
                    tech_stack: initialData.current_tech_stacks || [],
                    video_url: initialData.video_url || '',
                  });

                  globalFormState.step.value = 2;
                }
              } catch (innerError) {
                console.error('Error in fallback parsing:', innerError);
              }
            }
          }
        }
      } else {
        console.log('No displayData found in element attributes');
      }
    } else {
      console.log('product-submit-app element not found');
    }
  };

  // Load saved data from session storage
  const loadSavedData = async () => {
    // Don't load saved data if we're currently editing an existing product (to prevent override)
    const element = document.getElementById('product-submit-app');
    if (element) {
      const displayData = element.getAttribute('data-display-data');
      const isAdmin = element.getAttribute('data-is-admin');

      // If we're an admin editing an existing product, skip loading saved data
      if (isAdmin === 'true' && displayData) {
        console.log('Skipping loading saved data because we are editing an existing product as admin');
        return;
      }
    }

    const savedData = productFormService.loadFormData();
    if (savedData) {
      if (savedData.link) {
        await updateFormMultiple(savedData);
        globalFormState.logoPreview.value = savedData.logoPreview || null;
        globalFormState.galleryPreviews.value = savedData.galleryPreviews || Array(3).fill(null);
        if (savedData.name) {
          globalFormState.step.value = 2;
        }
        // Check for URL existence when loading saved data with a link
        await checkUrlExists();
      }
    }
  };

  // Save form data to session storage
  const saveFormData = () => {
    productFormService.saveFormData(form, globalFormState.logoPreview.value, globalFormState.galleryPreviews.value);
  };

  return {
    step: globalFormState.step,
    currentTab: globalFormState.currentTab,
    isRestored: globalFormState.isRestored,
    isMounted: globalFormState.isMounted,
    isLoading: globalFormState.isLoading,
    urlExistsError: globalFormState.urlExistsError,
    existingProduct: globalFormState.existingProduct,
    showPreviewModal: globalFormState.showPreviewModal,
    loadingStates,
    logoPreview: globalFormState.logoPreview,
    galleryPreviews: globalFormState.galleryPreviews,
    allCategories: globalFormState.allCategories,
    allBestFor: globalFormState.allBestFor,
    allPricing: globalFormState.allPricing,
    allTechStacks: globalFormState.allTechStacks,
    form,
    sidebarSteps,
    errorMessage,
    showErrorMessage,
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
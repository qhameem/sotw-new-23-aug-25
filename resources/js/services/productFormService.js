/**
 * Service for managing product form state
 */

// Define initial form state
const initialFormState = {
  link: '',
  name: '',
  tagline: '',
  tagline_detailed: '',
  description: '',
  categories: [],
  bestFor: [],
  pricing: [],
  tech_stack: [],
  favicon: '',
  logo: null,
  gallery: Array(3).fill(null),
  video_url: '',
  logos: [],
  maker_links: [],
  sell_product: false,
  asking_price: null,
  additionalLinks: [],
 x_account: '',
};

// Define loading states
const initialLoadingStates = {
  name: false,
  tagline: false,
  description: false,
  categories: false,
  bestFor: false,
  logos: false,
  ai: false,
};

// Define sidebar steps
const sidebarSteps = [
  { id: 'mainInfo', name: 'Main info', icon: 'MainInfoIcon' },
  { id: 'imagesAndMedia', name: 'Images and media', icon: 'ImagesMediaIcon' },
  { id: 'extras', name: 'Extras', icon: 'ExtrasIcon' },
  { id: 'launchChecklist', name: 'Launch', icon: 'LaunchChecklistIcon' },
];

export const createProductFormState = () => {
  return {
    step: 1,
    currentTab: 'mainInfo',
    isRestored: false,
    isMounted: false,
    isLoading: false,
    urlExistsError: false,
    existingProduct: null,
    showPreviewModal: false,
    loadingStates: { ...initialLoadingStates },
    logoPreview: null,
    galleryPreviews: Array(3).fill(null),
    allCategories: [],
    allBestFor: [],
    allPricing: [],
    allTechStacks: [],
    form: { ...initialFormState },
    sidebarSteps: [...sidebarSteps],
  };
};

export const productFormService = {
  /**
   * Save form data to session storage
   */
  saveFormData(formData, logoPreview, galleryPreviews) {
    const dataToSave = {
      ...formData,
      logoPreview,
      galleryPreviews,
    };
    // Remove file objects which can't be serialized
    delete dataToSave.logo;
    delete dataToSave.gallery;
    sessionStorage.setItem('productFormData', JSON.stringify(dataToSave));
  },

  /**
   * Load form data from session storage
   */
 loadFormData() {
    const savedData = sessionStorage.getItem('productFormData');
    return savedData ? JSON.parse(savedData) : null;
  },

  /**
   * Clear form data from session storage
   */
  clearFormData() {
    sessionStorage.removeItem('productFormData');
  },

  /**
   * Validate URL format
   */
  isUrlInvalid(url) {
    if (!url) {
      return true;
    }
    try {
      let formattedUrl = url;
      if (!/^https?:\/\//i.test(formattedUrl)) {
        formattedUrl = 'http://' + formattedUrl;
      }
      new URL(formattedUrl);
      return false;
    } catch (e) {
      return true;
    }
  },

  /**
   * Check if a tab is completed based on required fields
   */
  isTabCompleted(step, form, logoPreview) {
    let isCompleted = false;

    switch(step.id) {
      case 'mainInfo':
        // Main info tab requires: name, tagline, tagline_detailed, description, categories, bestFor, pricing
        isCompleted = !!(
          form.name &&
          form.tagline &&
          form.tagline_detailed &&
          form.description &&
          form.categories && form.categories.length > 0 &&
          form.bestFor && form.bestFor.length > 0 &&
          form.pricing && form.pricing.length > 0
        );
        break;
      case 'imagesAndMedia':
        // Images and media tab requires: at least one logo (either uploaded logo or selected from suggested logos)
        isCompleted = !!(logoPreview);
        break;
      case 'extras':
        // Extras tab has no required fields, so it's always considered complete if reached
        isCompleted = true;
        break;
      case 'launchChecklist':
        // Launch checklist requires all the main required fields
        isCompleted = !!(
          form.link &&
          form.name &&
          form.tagline &&
          form.tagline_detailed &&
          form.description &&
          form.categories && form.categories.length > 0 &&
          form.bestFor && form.bestFor.length > 0 &&
          form.pricing && form.pricing.length > 0 &&
          (logoPreview || (form.logos && form.logos.length > 0))
        );
        break;
      default:
        isCompleted = false;
    }

    return isCompleted;
  },

  /**
   * Fetch initial metadata from API
   */
  async fetchInitialData(link) {
    if (!link) return null;

    try {
      const response = await axios.post('/api/fetch-initial-metadata', { url: link });
      return response.data;
    } catch (error) {
      console.error('Error fetching initial metadata:', error);
      throw error;
    }
  },

  /**
   * Fetch remaining data from API
   */
  async fetchRemainingData(link, name, tagline, shouldFetchContent = true) {
    try {
      const response = await axios.post('/api/process-url', {
        url: link,
        name,
        tagline,
        fetch_content: shouldFetchContent,
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching remaining data:', error);
      throw error;
    }
  },

  /**
   * Fetch categories and tech stacks from API
   */
  async fetchInitialFormOptions() {
    try {
      const [categoriesResponse, techStacksResponse] = await Promise.all([
        axios.get('/api/categories'),
        axios.get('/api/tech-stacks')
      ]);

      return {
        categories: categoriesResponse.data.categories,
        bestFor: categoriesResponse.data.bestFor,
        pricing: categoriesResponse.data.pricing,
        techStacks: techStacksResponse.data,
      };
    } catch (error) {
      console.error('Failed to fetch initial form data:', error);
      throw error;
    }
 }
};

export const isTabCompleted = (step, form, logoPreview) => {
  let isCompleted = false;

  switch(step.id) {
    case 'mainInfo':
      // Main info tab requires: name, tagline, tagline_detailed, description, categories, bestFor, pricing
      isCompleted = !!(
        form.name &&
        form.tagline &&
        form.tagline_detailed &&
        form.description &&
        form.categories && form.categories.length > 0 &&
        form.bestFor && form.bestFor.length > 0 &&
        form.pricing && form.pricing.length > 0
      );
      break;
    case 'imagesAndMedia':
      // Images and media tab requires: at least one logo (either uploaded logo or selected from suggested logos)
      isCompleted = !!(logoPreview);
      break;
    case 'extras':
      // Extras tab has no required fields, so it's always considered complete if reached
      isCompleted = true;
      break;
    case 'launchChecklist':
      // Launch checklist requires all the main required fields
      isCompleted = !!(
        form.link &&
        form.name &&
        form.tagline &&
        form.tagline_detailed &&
        form.description &&
        form.categories && form.categories.length > 0 &&
        form.bestFor && form.bestFor.length > 0 &&
        form.pricing && form.pricing.length > 0 &&
        (logoPreview || (form.logos && form.logos.length > 0))
      );
      break;
    default:
      isCompleted = false;
  }

  return isCompleted;
};

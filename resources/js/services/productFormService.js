import { ref, reactive } from 'vue';
import axios from 'axios';

/**
 * Service for managing product form state
 */

const TRACKING_QUERY_PARAMS = new Set([
  'fbclid',
  'gclid',
  'gclsrc',
  'dclid',
  'msclkid',
  'mc_cid',
  'mc_eid',
  'igshid',
  'ref',
  'ref_src',
  'si',
]);

const formatUrlWithoutRootSlash = (urlObject) => {
  const protocol = urlObject.protocol.replace(':', '');
  const path = urlObject.pathname === '/' ? '' : urlObject.pathname;
  return `${protocol}://${urlObject.host}${path}${urlObject.search}${urlObject.hash}`;
};

// Define initial form state
const initialFormState = {
  id: null,
  link: '',
  name: '',
  slug: '',
  tagline: '',
  tagline_detailed: '',
  description: '',
  categories: [],
  categories_custom: [],
  bestFor: [],
  bestFor_custom: [],
  pricing: [],
  pricing_page_url: '',
  tech_stack: [],
  tech_stack_custom: [],
  favicon: '',
  logo: null,
  gallery: Array(3).fill(null),
  video_url: '',
  logos: [],
  maker_links: [],
  sell_product: false,
  asking_price: null,
  x_account: '',
  comparison_overrides_input: '',
  alternative_overrides_input: '',
  fromSource: null,
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
  { id: 'launchChecklist', name: 'Launch', icon: 'LaunchChecklistIcon' },
];

export const createProductFormState = () => {
  return {
    step: ref(1),
    currentTab: ref('mainInfo'),
    isRestored: ref(false),
    isMounted: ref(false),
    isLoading: ref(false),
    loadingProgress: ref(0),
    loadingMessage: ref(''),
    urlExistsError: ref(false),
    existingProduct: ref(null),
    showPreviewModal: ref(false),
    submissionBgUrl: ref(''),
    extractionErrors: reactive({
      name: '',
      tagline: '',
      description: '',
      categories: '',
      bestFor: '',
      logos: '',
    }),
    loadingStates: { ...initialLoadingStates },
    logoPreview: ref(null),
    galleryPreviews: ref(Array(3).fill(null)),
    allCategories: ref([]),
    allBestFor: ref([]),
    allPricing: ref([]),
    allTechStacks: ref([]),
    isAdmin: ref(false),
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
   * Suggest if trailing URL parts can be trimmed (anchor, tracking params, trailing slash)
   */
  getUrlTrimSuggestion(url) {
    if (!url || typeof url !== 'string') {
      return null;
    }

    const rawUrl = url.trim();
    if (!rawUrl) {
      return null;
    }

    try {
      const withScheme = /^https?:\/\//i.test(rawUrl) ? rawUrl : `https://${rawUrl}`;
      const parsed = new URL(withScheme);
      const trimmed = new URL(parsed.toString());
      const removedParts = [];

      if (trimmed.hash) {
        trimmed.hash = '';
        removedParts.push('the page anchor (#...)');
      }

      const hasTrailingSlash = trimmed.pathname !== '/' && /\/+$/.test(trimmed.pathname);
      if (hasTrailingSlash) {
        trimmed.pathname = trimmed.pathname.replace(/\/+$/, '');
        removedParts.push('the trailing slash');
      }

      const removedTrackingParams = [];
      for (const key of [...trimmed.searchParams.keys()]) {
        const lowered = key.toLowerCase();
        if (lowered.startsWith('utm_') || TRACKING_QUERY_PARAMS.has(lowered)) {
          trimmed.searchParams.delete(key);
          removedTrackingParams.push(key);
        }
      }

      if (removedTrackingParams.length > 0) {
        removedParts.push(
          removedTrackingParams.length === 1
            ? `tracking parameter "${removedTrackingParams[0]}"`
            : `${removedTrackingParams.length} tracking parameters`
        );
      }

      if (removedParts.length === 0) {
        return null;
      }

      const currentUrl = formatUrlWithoutRootSlash(parsed);
      const suggestedUrl = formatUrlWithoutRootSlash(trimmed);
      if (!suggestedUrl || suggestedUrl === currentUrl) {
        return null;
      }

      return {
        suggestedUrl,
        removedParts,
      };
    } catch (e) {
      return null;
    }
  },

  /**
   * Check if a tab is completed based on required fields
   */
  isTabCompleted(step, form, logoPreview) {
    let isCompleted = false;

    switch (step.id) {
      case 'mainInfo':
        // Main info tab requires: name, tagline, tagline_detailed, description, categories, bestFor, pricing
        isCompleted = !!(
          form.name &&
          form.tagline &&
          form.tagline_detailed &&
          form.description &&
          (form.categories?.length > 0 || form.categories_custom?.length > 0) &&
          form.pricing && form.pricing.length > 0
        );
        break;
      case 'imagesAndMedia':
        // Images and media tab requires: at least one logo (either uploaded logo or selected from suggested logos)
        isCompleted = !!(logoPreview);
        break;
      case 'launchChecklist':
        // Launch checklist requires all the main required fields
        isCompleted = !!(
          form.link &&
          form.name &&
          form.tagline &&
          form.tagline_detailed &&
          form.description &&
          (form.categories?.length > 0 || form.categories_custom?.length > 0) &&
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
  },

  /**
   * Check if URL already exists
   */
  async checkUrlExists(url, excludeId = null) {
    console.log('Checking URL existence:', url, excludeId ? `(excluding ${excludeId})` : '');
    try {
      const response = await axios.get('/check-product-url', {
        params: {
          url: url,
          exclude_id: excludeId
        }
      });
      console.log('URL check response:', response.data);
      return response.data;
    } catch (error) {
      console.error('Error checking URL:', error);
      throw error;
    }
  }
};

export const isTabCompleted = (step, form, logoPreview) => {
  let isCompleted = false;

  switch (step.id) {
    case 'mainInfo':
      // Main info tab requires: name, tagline, tagline_detailed, description, categories, bestFor, pricing
      isCompleted = !!(
        form.name &&
        form.tagline &&
        form.tagline_detailed &&
        form.description &&
        (form.categories?.length > 0 || form.categories_custom?.length > 0) &&
        form.pricing && form.pricing.length > 0
      );
      break;
    case 'imagesAndMedia':
      // Images and media tab requires: at least one logo (either uploaded logo or selected from suggested logos)
      isCompleted = !!(logoPreview);
      break;
    case 'launchChecklist':
      // Launch checklist requires all the main required fields
      isCompleted = !!(
        form.link &&
        form.name &&
        form.tagline &&
        form.tagline_detailed &&
        form.description &&
        (form.categories?.length > 0 || form.categories_custom?.length > 0) &&
        form.pricing && form.pricing.length > 0 &&
        (logoPreview || (form.logos && form.logos.length > 0))
      );
      break;
    default:
      isCompleted = false;
  }

  return isCompleted;
};

/**
 * Get progress of a tab (completed vs total required fields)
 */
export const getTabProgress = (stepId, form, logoPreview) => {
  let completed = 0;
  let total = 0;

  switch (stepId) {
    case 'mainInfo':
      total = 6;
      if (form.name) completed++;
      if (form.tagline) completed++;
      if (form.tagline_detailed) completed++;
      if (form.description) completed++;
      if (form.categories?.length > 0 || form.categories_custom?.length > 0) completed++;
      if (form.pricing && form.pricing.length > 0) completed++;
      break;
    case 'imagesAndMedia':
      total = 1;
      if (logoPreview) completed++;
      break;
    case 'launchChecklist':
      total = 7; // 6 from mainInfo + logo
      if (form.link) completed++;
      if (form.name) completed++;
      if (form.tagline) completed++;
      if (form.tagline_detailed) completed++;
      if (form.description) completed++;
      if (form.categories?.length > 0 || form.categories_custom?.length > 0) completed++;
      if (form.pricing && form.pricing.length > 0) completed++;
      if (logoPreview || (form.logos && form.logos.length > 0)) completed++;
      break;
  }

  return { completed, total };
};

import { reactive, computed, ref, toRefs } from 'vue';
import { productFormService, createProductFormState } from '../services/productFormService';
import axios from 'axios';

// Create a global state for the product form
const globalFormState = createProductFormState();

// Create shared reactive objects to ensure consistency across all components
const sharedForm = reactive({ ...globalFormState.form });
const sharedLoadingStates = reactive({ ...globalFormState.loadingStates });

let loadingAnimationFrameId = null;

export function useProductForm() {
  const isAdmin = globalFormState.isAdmin;
  const submissionBgUrl = globalFormState.submissionBgUrl;
  const extractionErrors = globalFormState.extractionErrors;
  const form = sharedForm;
  const loadingStates = sharedLoadingStates;
  const sidebarSteps = [...globalFormState.sidebarSteps];

  // Create local refs for form-specific error messages to avoid conflicts
  const errorMessage = ref('');
  const showErrorMessage = ref(false);

  // Computed properties
  const isUrlInvalid = computed(() => {
    return productFormService.isUrlInvalid(form.link);
  });
  const urlTrimSuggestion = computed(() => {
    return productFormService.getUrlTrimSuggestion(form.link);
  });

  const markManualLogoChosen = (chosen = true) => {
    globalFormState.manualLogoChosen.value = chosen;
  };

  const markManualScreenshotChosen = (chosen = true) => {
    globalFormState.manualScreenshotChosen.value = chosen;
  };

  const resetManualMediaChoices = () => {
    globalFormState.manualLogoChosen.value = false;
    globalFormState.manualScreenshotChosen.value = false;
  };

  const mergeAutofillLinks = (existingLinks = [], fetchedLinks = []) => {
    const merged = [];
    const seen = new Set();

    [...existingLinks, ...fetchedLinks].forEach((link) => {
      if (typeof link !== 'string') {
        return;
      }

      const trimmed = link.trim();
      if (!trimmed || seen.has(trimmed)) {
        return;
      }

      seen.add(trimmed);
      merged.push(trimmed);
    });

    return merged.slice(0, 10);
  };

  const delay = (ms) => new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });

  const createSandboxSvgDataUrl = ({ background, accent, label, textColor = '#ffffff' }) => {
    const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
        <defs>
          <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="${background}" />
            <stop offset="100%" stop-color="${accent}" />
          </linearGradient>
        </defs>
        <rect width="1200" height="630" rx="32" fill="url(#bg)" />
        <circle cx="190" cy="170" r="72" fill="rgba(255,255,255,0.16)" />
        <rect x="310" y="124" width="580" height="82" rx="18" fill="rgba(255,255,255,0.14)" />
        <rect x="310" y="244" width="430" height="28" rx="14" fill="rgba(255,255,255,0.18)" />
        <rect x="310" y="292" width="360" height="28" rx="14" fill="rgba(255,255,255,0.14)" />
        <rect x="310" y="372" width="200" height="64" rx="32" fill="rgba(255,255,255,0.22)" />
        <text x="310" y="176" font-family="Arial, Helvetica, sans-serif" font-size="52" font-weight="700" fill="${textColor}">${label}</text>
        <text x="310" y="420" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="600" fill="${textColor}">Sandbox autofill preview</text>
      </svg>
    `;

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
  };

  const buildSandboxAutofillPayload = () => {
    const fallbackCategoryId = globalFormState.allCategories.value?.[0]?.id || null;
    const fallbackUseCaseId = globalFormState.allUseCases.value?.[0]?.id || null;
    const fallbackPlatformId = globalFormState.allPlatforms.value?.[0]?.id || null;
    const fallbackBestForId = globalFormState.allBestFor.value?.[0]?.id || null;
    const fallbackPricingId = globalFormState.allPricing.value?.[0]?.id || null;

    return {
      link: 'https://sandbox-preview.test',
      name: 'Sandbox Project',
      tagline: 'Sandbox autofill for testing the submission flow',
      tagline_detailed: 'Preview AI autofill states without submitting a real product',
      description: '<p>This is a sandbox-only autofill result for admins. It simulates a successful AI extraction so you can verify loading states, transitions, and preview behavior without touching production data.</p><p>No URL was fetched, and no submission will be stored while sandbox mode remains active.</p>',
      categories: fallbackCategoryId ? [fallbackCategoryId] : [],
      useCases: fallbackUseCaseId ? [fallbackUseCaseId] : [],
      platforms: fallbackPlatformId ? [fallbackPlatformId] : [],
      bestFor: fallbackBestForId ? [fallbackBestForId] : [],
      pricing: fallbackPricingId ? [fallbackPricingId] : [],
      pricing_page_url: 'https://sandbox-preview.test/pricing',
      x_account: '@sandbox_preview',
      maker_links: ['https://www.linkedin.com/company/sandbox-preview'],
      logos: [
        createSandboxSvgDataUrl({
          background: '#0f172a',
          accent: '#2563eb',
          label: 'Sandbox',
        }),
      ],
      screenshot: createSandboxSvgDataUrl({
        background: '#0f766e',
        accent: '#14b8a6',
        label: 'Sandbox Project',
      }),
    };
  };

  const parseCategoryIdList = (rawValue) => {
    try {
      return JSON.parse(rawValue || '[]').map((item) => parseInt(item.id ?? item, 10)).filter((item) => !Number.isNaN(item));
    } catch (error) {
      console.error('Error parsing category ID list:', error);
      return [];
    }
  };

  const splitSelectedCategoryIds = (allCategoryIds = [], options = {}) => {
    const parsedAllCategoryIds = (allCategoryIds || []).map((id) => parseInt(id, 10)).filter((id) => !Number.isNaN(id));
    const useCaseIds = new Set(parseCategoryIdList(options.useCaseCategories));
    const pricingIds = new Set(parseCategoryIdList(options.pricingCategories));
    const platformIds = new Set(parseCategoryIdList(options.platformCategories));

    let selectedUseCaseIds = [];
    try {
      selectedUseCaseIds = JSON.parse(options.selectedUseCaseCategories || '[]')
        .map((id) => parseInt(id, 10))
        .filter((id) => !Number.isNaN(id));
    } catch (error) {
      console.error('Error parsing selected use-case categories:', error);
    }

    let bestForIds = [];
    try {
      bestForIds = JSON.parse(options.selectedBestForCategories || '[]')
        .map((id) => parseInt(id, 10))
        .filter((id) => !Number.isNaN(id));
    } catch (error) {
      console.error('Error parsing selected best-for categories:', error);
    }

    let selectedPlatformIds = [];
    try {
      selectedPlatformIds = JSON.parse(options.selectedPlatformCategories || '[]')
        .map((id) => parseInt(id, 10))
        .filter((id) => !Number.isNaN(id));
    } catch (error) {
      console.error('Error parsing selected platform categories:', error);
    }

    const selectedUseCaseIdSet = new Set(selectedUseCaseIds);
    const bestForIdSet = new Set(bestForIds);
    const selectedPlatformIdSet = new Set(selectedPlatformIds);

    return {
      pricing: parsedAllCategoryIds.filter((id) => pricingIds.has(id)),
      useCases: parsedAllCategoryIds.filter((id) => useCaseIds.has(id) || selectedUseCaseIdSet.has(id)),
      bestFor: parsedAllCategoryIds.filter((id) => bestForIdSet.has(id)),
      platforms: parsedAllCategoryIds.filter((id) => platformIds.has(id) || selectedPlatformIdSet.has(id)),
      categories: parsedAllCategoryIds.filter((id) => !pricingIds.has(id) && !useCaseIds.has(id) && !selectedUseCaseIdSet.has(id) && !bestForIdSet.has(id) && !platformIds.has(id) && !selectedPlatformIdSet.has(id)),
    };
  };

  const mapStreamProgressToUi = (streamProgress) => {
    const normalizedProgress = Math.max(0, Math.min(Number(streamProgress) || 0, 100));
    return 35 + (normalizedProgress * 0.55);
  };

  const stopLoadingAnimation = () => {
    if (loadingAnimationFrameId !== null) {
      cancelAnimationFrame(loadingAnimationFrameId);
      loadingAnimationFrameId = null;
    }
  };

  const tickLoadingAnimation = () => {
    const target = globalFormState.loadingTargetProgress.value || 0;
    const current = globalFormState.loadingProgress.value || 0;
    const delta = target - current;

    if (Math.abs(delta) <= 0.1) {
      globalFormState.loadingProgress.value = target;
    } else {
      const step = Math.min(Math.max(delta * 0.18, 0.35), 4);
      globalFormState.loadingProgress.value = Math.min(target, current + step);
    }

    const shouldContinue = globalFormState.isLoading.value
      ? globalFormState.loadingProgress.value + 0.1 < target
      : false;

    if (shouldContinue) {
      loadingAnimationFrameId = requestAnimationFrame(tickLoadingAnimation);
      return;
    }

    stopLoadingAnimation();
  };

  const ensureLoadingAnimation = () => {
    if (loadingAnimationFrameId === null) {
      loadingAnimationFrameId = requestAnimationFrame(tickLoadingAnimation);
    }
  };

  const beginAutofillProgress = (message, progress = 5, sessionType = 'fullAutofill') => {
    globalFormState.isLoading.value = true;
    globalFormState.loadingMessage.value = message;
    globalFormState.loadingSessionType.value = sessionType;
    globalFormState.loadingStartedAt.value = Date.now();
    globalFormState.loadingTargetProgress.value = progress;
    globalFormState.loadingProgress.value = progress;
    ensureLoadingAnimation();
  };

  const updateAutofillProgress = (message, progress) => {
    if (!globalFormState.loadingStartedAt.value) {
      beginAutofillProgress(message, progress);
      return;
    }

    globalFormState.loadingMessage.value = message;
    globalFormState.loadingTargetProgress.value = Math.max(globalFormState.loadingTargetProgress.value || 0, progress);
    ensureLoadingAnimation();
  };

  const completeAutofillProgress = () => {
    globalFormState.loadingTargetProgress.value = 100;
    globalFormState.loadingProgress.value = 100;
    globalFormState.loadingSessionType.value = null;
    globalFormState.loadingStartedAt.value = null;
    stopLoadingAnimation();
  };

  const applyAutofillLinks = (data) => {
    if (data.pricing_page_url && (!form.pricing_page_url || form.pricing_page_url.trim() === '')) {
      form.pricing_page_url = data.pricing_page_url;
      console.log('Applied autofill pricing page URL:', data.pricing_page_url);
    }

    if (data.x_account && (!form.x_account || form.x_account.trim() === '')) {
      form.x_account = data.x_account;
      console.log('Applied autofill X account:', data.x_account);
    }

    if (Array.isArray(data.maker_links) && data.maker_links.length > 0) {
      const mergedLinks = mergeAutofillLinks(form.maker_links || [], data.maker_links);
      if (JSON.stringify(mergedLinks) !== JSON.stringify(form.maker_links || [])) {
        form.maker_links = mergedLinks;
        console.log('Applied autofill maker links:', mergedLinks);
      }
    }
  };

  const checkUrlExists = async (urlToCheck = form.link) => {
    console.log('checkUrlExists called with URL:', urlToCheck, 'and ID:', form.id);
    if (!urlToCheck) {
      globalFormState.urlExistsError.value = false;
      globalFormState.existingProduct.value = null;
      return { exists: false };
    }

    if (productFormService.isUrlInvalid(urlToCheck)) {
      globalFormState.urlExistsError.value = false;
      globalFormState.existingProduct.value = null;
      return { exists: false };
    }

    try {
      const response = await productFormService.checkUrlExists(urlToCheck, form.id);
      console.log('checkUrlExists response:', response);

      if (urlToCheck !== form.link) {
        console.log('Ignoring stale URL check response for:', urlToCheck);
        return response;
      }

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
      return response;
    } catch (error) {
      console.error('Error checking URL existence:', error);
      globalFormState.urlExistsError.value = false;
      globalFormState.existingProduct.value = null;
      return { exists: false };
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
    resetManualMediaChoices();
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
    form.categories_custom = [];
    form.useCases = [];
    form.useCases_custom = [];
    form.platforms = [];
    form.platforms_custom = [];
    form.bestFor = [];
    form.bestFor_custom = [];
    form.pricing = [];
    form.tech_stack = [];
    form.logo = null;
    form.gallery = [null];
    form.video_url = '';
    form.maker_links = [];
    form.sell_product = false;
    form.asking_price = null;
    form.x_account = '';
    form.submissionOption = 'free';
    form.submission_type = 'free';
    form.badge_opt_in = false;
    form.badge_placement_url = '';
    form.badge_week_start = '';
    form.badge_verified = false;
    globalFormState.logoPreview.value = null;
    globalFormState.galleryPreviews.value = [null];
    resetManualMediaChoices();
  };

  const goToNextStep = (tabId) => {
    showErrorMessage.value = false;
    globalFormState.currentTab.value = tabId;
  };

  const submitProduct = () => {
    if (globalFormState.isLoading.value) {
      return false;
    }

    if (!validateForm()) {
      globalFormState.submitState.value = 'idle';
      return false;
    }

    confirmSubmit();
    return true;
  };

  const confirmSubmit = async () => {
    if (globalFormState.isLoading.value) {
      return false;
    }

    if (!validateForm()) {
      globalFormState.submitState.value = 'idle';
      return false;
    }

    let didSucceed = false;
    const isSandboxSubmission = globalFormState.isAdmin.value && form.sandbox_mode;

    try {
      // Set loading state to show the loader
      globalFormState.isLoading.value = true;
      globalFormState.submitState.value = 'loading';
      if (!isSandboxSubmission) {
        globalFormState.sandboxNotice.value = '';
      }

      // Prepare form data for submission
      const formData = new FormData();

      // If we're updating an existing product, spoof the PUT method for Laravel
      // Logic moved effectively to url determination block to ensure consistency
      // Keeping this comment for clarity that method spoofing is handled below based on URL 


      // Add basic fields
      formData.append('name', form.name);
      if (form.slug) {
        formData.append('slug', form.slug);
      }
      formData.append('tagline', form.tagline);
      formData.append('product_page_tagline', form.tagline_detailed);
      formData.append('description', form.description);
      formData.append('link', form.link);
      // User ID is automatically set by the backend based on the authenticated user

      // Add categories - combine all taxonomy selections into one array as expected by backend
      const allCategories = [];

      // Add regular categories
      if (form.categories && form.categories.length > 0) {
        const validCategories = form.categories.filter(id => id !== null && id !== undefined && id !== '');
        allCategories.push(...validCategories);
      }

      if (form.useCases && form.useCases.length > 0) {
        const validUseCases = form.useCases.filter(id => id !== null && id !== undefined && id !== '');
        allCategories.push(...validUseCases);
      }

      if (form.platforms && form.platforms.length > 0) {
        const validPlatforms = form.platforms.filter(id => id !== null && id !== undefined && id !== '');
        allCategories.push(...validPlatforms);
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

      // Add custom categories if any
      if (form.categories_custom && form.categories_custom.length > 0) {
        form.categories_custom.forEach((customCat, index) => {
          formData.append(`custom_categories[${index}][name]`, customCat.name);
          formData.append(`custom_categories[${index}][type]`, 'category');
        });
      }

      // Add custom bestFor if any
      if (form.bestFor_custom && form.bestFor_custom.length > 0) {
        form.bestFor_custom.forEach((customTag, index) => {
          formData.append(`custom_categories[${index + (form.categories_custom ? form.categories_custom.length : 0)}][name]`, customTag.name);
          formData.append(`custom_categories[${index + (form.categories_custom ? form.categories_custom.length : 0)}][type]`, 'best_for');
        });
      }

      if (form.useCases_custom && form.useCases_custom.length > 0) {
        const offset = (form.categories_custom ? form.categories_custom.length : 0) + (form.bestFor_custom ? form.bestFor_custom.length : 0);
        form.useCases_custom.forEach((customUseCase, index) => {
          formData.append(`custom_categories[${index + offset}][name]`, customUseCase.name);
          formData.append(`custom_categories[${index + offset}][type]`, 'use_case');
        });
      }

      if (form.platforms_custom && form.platforms_custom.length > 0) {
        const offset = (form.categories_custom ? form.categories_custom.length : 0)
          + (form.bestFor_custom ? form.bestFor_custom.length : 0)
          + (form.useCases_custom ? form.useCases_custom.length : 0);
        form.platforms_custom.forEach((customPlatform, index) => {
          formData.append(`custom_categories[${index + offset}][name]`, customPlatform.name);
          formData.append(`custom_categories[${index + offset}][type]`, 'platform');
        });
      }

      // Add tech stacks
      if (form.tech_stack && form.tech_stack.length > 0) {
        form.tech_stack.forEach((techStackId, index) => {
          formData.append(`tech_stacks[${index}]`, techStackId);
        });
      }

      // Add custom tech stacks if any
      if (form.tech_stack_custom && form.tech_stack_custom.length > 0) {
        form.tech_stack_custom.forEach((customTech, index) => {
          formData.append(`custom_tech_stacks[${index}][name]`, customTech.name);
        });
      }

      // Add logo if available as file or URL
      if (form.logo instanceof File) {
        formData.append('logo', form.logo);
      } else if (typeof form.logo === 'string' && form.logo) {
        // If logo is a string (e.g. from suggested logos), send it as logo_url
        formData.append('logo_url', form.logo);
      } else if (globalFormState.logoPreview.value) {
        // If we have a logo preview URL but no form.logo set, use that
        formData.append('logo_url', globalFormState.logoPreview.value);
      }

      // Submit only the single product screenshot slot.
      const screenshotPreview = globalFormState.galleryPreviews.value?.[0];
      const screenshotFile = form.gallery?.[0];
      if (screenshotFile instanceof File) {
        formData.append('media[0]', screenshotFile);
      } else if (typeof screenshotPreview === 'string' && screenshotPreview && screenshotPreview.startsWith('http')) {
        formData.append('media_urls[0]', screenshotPreview);
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

      // Add pricing page url if available
      if (form.pricing_page_url) {
        formData.append('pricing_page_url', form.pricing_page_url);
      }

      // Add X account if available
      if (form.x_account) {
        formData.append('x_account', form.x_account);
      }

      // Add maker links if available
      if (form.maker_links && form.maker_links.length > 0) {
        form.maker_links.forEach((link) => {
          if (link && link.trim() !== '') {  // Only add non-empty links
            formData.append('maker_links[]', link);
          }
        });
      }

      // Add submission type ('free' or 'badge')
      if (form.submission_type) {
        formData.append('submission_type', form.submission_type);
      }

      if (form.badge_placement_url) {
        formData.append('badge_placement_url', form.badge_placement_url);
      }

      if (form.badge_week_start) {
        formData.append('badge_week_start', form.badge_week_start);
      }

      formData.append('sandbox_mode', form.sandbox_mode ? '1' : '0');

      // Admin-only curated related-product overrides
      if (globalFormState.isAdmin.value) {
        formData.append('comparison_overrides_input', form.comparison_overrides_input || '');
        formData.append('alternative_overrides_input', form.alternative_overrides_input || '');
      }

      // Determine submission URL
      // Determine submission URL and method logic
      let url;
      let isUpdate = false;

      if (form.id) {
        if (globalFormState.isAdmin.value) {
          url = `/admin/products/${form.id}`;
          isUpdate = true;
        } else {
          // For regular users potentially updating their own product
          // We should maintain the RESTful convention if existing ID is valid for update
          // check if we are on an edit page or just have a stale ID
          // For now, assuming if we are NOT admin, we might be creating. 
          // But if we truly have an ID and intend to update, we should trust form.id?
          // Actually, the error happens because URL is forced to /products.
          // If we really want to update, it should be /products/{id}.
          // Let's assume if we have an ID we should try to update at /products/{id}
          url = `/products/${form.id}`;
          isUpdate = true;
        }
      } else {
        url = '/products';
      }

      // Safety check: if we are sending to the create endpoint, do NOT spoof PUT
      // This handles cases where form.id might be stale or incorrect for the current action
      if (url === '/products' || url.endsWith('/products')) {
        isUpdate = false;
        if (form.id) {
          console.warn('Form has ID but URL is /products. Ignoring ID for submission method.');
          // Only spoof PUT if we are NOT targeting the create endpoint
        }
      }

      // If we're updating an existing product, spoof the PUT method for Laravel
      if (isUpdate) {
        formData.append('_method', 'PUT');
      }

      // Check if we came from approvals page and add the parameter
      const urlParams = new URLSearchParams(window.location.search);
      const fromParam = urlParams.get('from');
      if (fromParam) {
        formData.append('from', fromParam);
      } else if (form.fromSource) {
        // Use the fromSource that was captured during initialization
        formData.append('from', form.fromSource);
      }

      // Submit the form
      const response = await axios.post(url, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      // Clear form data after successful submission
      productFormService.clearFormData();

      if (response.data?.sandbox) {
        didSucceed = true;
        globalFormState.submitState.value = 'success';
        globalFormState.sandboxNotice.value = response.data.message || 'Sandbox submission complete. No product was saved.';
        await new Promise((resolve) => {
          window.setTimeout(resolve, 650);
        });
        globalFormState.isLoading.value = false;
        globalFormState.submitState.value = 'idle';
        return true;
      }

      // Dispatch an event to notify other components of successful submission
      document.dispatchEvent(new CustomEvent('product-submitted', {
        detail: {
          productId: response.data.product_id,
          message: 'Product submitted successfully'
        }
      }));

      didSucceed = true;
      globalFormState.submitState.value = 'success';
      await new Promise((resolve) => {
        window.setTimeout(resolve, 650);
      });

      // Redirect to success page
      if (response.data && typeof response.data === 'object' && response.data.redirect_url) {
        window.location.href = response.data.redirect_url;
      } else if (response.request?.responseURL && response.request.responseURL.includes('/submission-success/')) {
        // If Laravel already redirected and Axios followed it, use that final URL.
        window.location.href = response.request.responseURL;
      } else {
        // Fallback redirect
        const productId = (response.data && typeof response.data === 'object') ? response.data.product_id : null;
        if (productId) {
          window.location.href = `/submission-success/${productId}`;
        } else {
          // Last resort to avoid routing into PUT /products/{product}
          window.location.href = '/my-products';
        }
      }

      return true;
    } catch (error) {
      console.error('Error submitting product:', error);
      globalFormState.submitState.value = 'idle';
      showErrorMessage.value = true;
      const firstValidationError = error.response?.data?.errors
        ? Object.values(error.response.data.errors).flat()[0]
        : null;
      errorMessage.value = firstValidationError || error.response?.data?.message || 'Failed to submit product. Please try again.';
      return false;
    } finally {
      if (!didSucceed) {
        globalFormState.isLoading.value = false;
      }
      globalFormState.showPreviewModal.value = false;
    }
  };

  const closeModal = () => {
    globalFormState.showPreviewModal.value = false;
  };

  const validateForm = () => {
    if (globalFormState.isAdmin.value && form.sandbox_mode) {
      showErrorMessage.value = false;
      errorMessage.value = '';
      return true;
    }

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

    // Validate categories: minimum 1 (either existing or custom)
    const validCategories = (form.categories || []).filter(id => id !== null && id !== undefined && id !== '');
    const customCategories = (form.categories_custom || []).filter(cat => cat && cat.name && cat.name.trim() !== '');
    if (validCategories.length === 0 && customCategories.length === 0) {
      showErrorMessage.value = true;
      errorMessage.value = 'At least one category is required.';
      return false;
    }

    const validUseCases = (form.useCases || []).filter(id => id !== null && id !== undefined && id !== '');
    const customUseCases = (form.useCases_custom || []).filter(useCase => useCase && useCase.name && useCase.name.trim() !== '');
    if (validUseCases.length === 0 && customUseCases.length === 0) {
      showErrorMessage.value = true;
      errorMessage.value = 'At least one use case is required.';
      return false;
    }

    // bestFor/Tags is optional — no validation needed

    // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
    const actualPricingCategories = (form.pricing || []).filter(id => !isNaN(id));
    if (actualPricingCategories.length === 0) {
      showErrorMessage.value = true;
      errorMessage.value = 'At least one pricing model is required.';
      return false;
    }

    const hasLogo = !!globalFormState.logoPreview.value || !!form.logo || (form.logos && form.logos.length > 0);
    if (!hasLogo) {
      showErrorMessage.value = true;
      errorMessage.value = 'A logo is required.';
      return false;
    }

    if (form.submission_type === 'badge') {
      if (!form.badge_placement_url) {
        showErrorMessage.value = true;
        errorMessage.value = 'Add the badge URL on your site before submitting.';
        return false;
      }

      if (!form.badge_verified) {
        showErrorMessage.value = true;
        errorMessage.value = 'Verify your badge placement before submitting for a scheduled week.';
        return false;
      }

      if (!form.badge_week_start) {
        showErrorMessage.value = true;
        errorMessage.value = 'Choose a launch week after badge verification.';
        return false;
      }
    }

    showErrorMessage.value = false;
    return true;
  };

  const fetchInitialData = async (urlOverride) => {
    const linkValue = urlOverride || form.link;
    console.log('fetchInitialData called with link:', linkValue);

    if (!linkValue || linkValue.trim() === '') {
      console.log('No link provided to fetchInitialData, returning early');
      return;
    }

    loadingStates.name = true;
    extractionErrors.name = '';
    beginAutofillProgress('Initializing request...', 5, 'fullAutofill');

    try {
      updateAutofillProgress('Fetching basic metadata and taking screenshot...', 10);
      const response = await axios.post('/api/fetch-initial-metadata', { url: linkValue });
      updateAutofillProgress('Basic metadata received. Preparing detailed analysis...', 30);
      const data = response.data;

      console.log('fetchInitialData response:', data);

      if (data.name) form.name = data.name;
      if (data.tagline) form.tagline = data.tagline;
      if (data.tagline_detailed) form.tagline_detailed = data.tagline_detailed;
      if (data.favicon) form.favicon = data.favicon;
      applyAutofillLinks(data);

      // Auto-set screenshot as first gallery item if available
      if (data.screenshot_url && !globalFormState.manualScreenshotChosen.value) {
        globalFormState.galleryPreviews.value[0] = data.screenshot_url;
      }

      console.log('Form state after fetchInitialData:', {
        name: form.name,
        tagline: form.tagline,
        favicon: form.favicon
      });

      // Prioritize the best logo found by our extractor over the favicon
      if (data.logos && data.logos.length > 0 && !globalFormState.manualLogoChosen.value) {
        globalFormState.logoPreview.value = data.logos[0];
      } else if (data.favicon && !globalFormState.manualLogoChosen.value) {
        globalFormState.logoPreview.value = data.favicon;
      }

      loadingStates.name = false;

      // Ensure form.link is set
      if (!form.link && linkValue) {
        form.link = linkValue;
      }

      // Trigger fetching of remaining data (description, categories, etc.)
      // We don't await this to keep the UI responsive, or we could await it if we want the button to spin until everything is done.
      // Based on user feedback "stuck in loop", they likely want to see progress.
      // Let's await it so the button state reflects total activity.
      await fetchRemainingData(false, linkValue);

    } catch (error) {
      console.error('Error fetching initial metadata:', error);
      loadingStates.name = false;
      extractionErrors.name = 'Failed to extract name and taglines.';
      showErrorMessage.value = true;
      errorMessage.value = 'Failed to fetch product metadata. Please check the URL and try again.';
    } finally {
      if (!Object.values(loadingStates).some((loading) => loading === true)) {
        globalFormState.isLoading.value = false;
        globalFormState.loadingTargetProgress.value = 0;
        globalFormState.loadingSessionType.value = null;
        globalFormState.loadingStartedAt.value = null;
        stopLoadingAnimation();
      }
    }
  };

  const processUrlStreamRequest = async ({ url, name, tagline, fetchContent = true, onProgress = null }) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch('/api/process-url-stream', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json, application/x-ndjson',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
      },
      body: JSON.stringify({
        url,
        name,
        tagline,
        fetch_content: fetchContent,
      })
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    let finalData = {};

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;

      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop();

      for (const line of lines) {
        if (!line.trim()) {
          continue;
        }

        try {
          const streamData = JSON.parse(line);

          if (streamData.data) {
            finalData = streamData.data;
          }

          if (onProgress) {
            onProgress(streamData);
          }
        } catch (error) {
          console.error('Error parsing stream chunk', error, line);
        }
      }
    }

    if (buffer.trim()) {
      try {
        const streamData = JSON.parse(buffer);
        if (streamData.data) {
          finalData = streamData.data;
        }
        if (onProgress) {
          onProgress(streamData);
        }
      } catch (error) {
        console.error('Error parsing final stream chunk', error, buffer);
      }
    }

    return finalData;
  };

  const fetchRemainingData = async (explicitLogoExtraction = false, urlOverride = null, options = {}) => {
    console.log('fetchRemainingData called', {
      explicitLogoExtraction,
      urlOverride,
      link: form.link,
      linkType: typeof form.link,
      linkTruthy: !!form.link,
      name: form.name
    });

    const forceContentFetch = options.forceContentFetch === true;
    const forceDescriptionOverwrite = options.forceDescriptionOverwrite === true;
    const contentOnly = options.contentOnly === true;

    // We should fetch content if description is missing, OR if we don't have a detailed tagline.
    // The previous logic (!tagline && !detailed && !description) was too strict because initial data provides a tagline.
    const shouldFetchContent = forceContentFetch || !form.description || !form.tagline_detailed;

    // Always fetch categories if they are empty
    const shouldFetchCategoriesAndBestFor = !contentOnly && (
      !form.categories || form.categories.length === 0 ||
      !form.useCases || form.useCases.length === 0 ||
      !form.bestFor || form.bestFor.length === 0 ||
      !form.platforms || form.platforms.length === 0 ||
      !form.tech_stack || form.tech_stack.length === 0
    );

    // Use urlOverride if available, otherwise fall back to form.link
    const linkValue = urlOverride || form.link;

    // Always fetch logos if we have a link and name, regardless of other content
    // If explicitLogoExtraction is true, always attempt to fetch logos even if they exist
    const shouldFetchLogos = !contentOnly && linkValue && (explicitLogoExtraction || (!form.logos || form.logos.length === 0));

    console.log('Should fetch checks:', { shouldFetchContent, shouldFetchCategoriesAndBestFor, shouldFetchLogos });

    // START DEBUG
    console.log('Form state before fetchRemainingData:', {
      tagline: form.tagline,
      tagline_detailed: form.tagline_detailed,
      description: form.description
    });
    // END DEBUG

    // Only proceed if we have a valid link (name is not strictly required for explicit logo extraction)
    if (!linkValue || linkValue.trim() === '') {
      console.log('No link provided or link is empty, returning early');
      Object.keys(loadingStates).forEach(k => loadingStates[k] = false);
      globalFormState.isLoading.value = false;
      return;
    }

    if (shouldFetchContent) {
      loadingStates.description = true;
      extractionErrors.tagline = '';
      extractionErrors.description = '';
      console.log('Setting description loading state to true');
    }
    if (shouldFetchCategoriesAndBestFor) {
      loadingStates.categories = true;
      loadingStates.bestFor = true;
      extractionErrors.categories = '';
      extractionErrors.useCases = '';
      extractionErrors.bestFor = '';
      console.log('Setting categories and bestFor loading states to true');
    }
    if (shouldFetchLogos) {
      loadingStates.logos = true;
      extractionErrors.logos = '';
      console.log('Setting logos loading state to true');
    }

    try {
      if (!globalFormState.loadingStartedAt.value) {
        beginAutofillProgress(
          explicitLogoExtraction ? 'Preparing logo extraction...' : 'Preparing detailed analysis...',
          35,
          explicitLogoExtraction ? 'logoExtraction' : 'fullAutofill'
        );
      }

      const nameValue = form.name || '';
      const taglineValue = form.tagline || '';

      console.log('Making API call to /api/process-url-stream with:', {
        url: linkValue,
        name: nameValue,
        tagline: taglineValue,
        fetch_content: shouldFetchContent,
      });

      updateAutofillProgress('Connecting for detailed analysis...', 35);

      const data = await processUrlStreamRequest({
        url: linkValue,
        name: nameValue,
        tagline: taglineValue,
        fetchContent: shouldFetchContent,
        onProgress: (streamData) => {
          if (streamData.progress !== undefined && streamData.progress !== null) {
            const isFinalStreamStep = Number(streamData.progress) >= 100;
            updateAutofillProgress(
              isFinalStreamStep
                ? 'Applying extracted data to the form...'
                : (streamData.message || globalFormState.loadingMessage.value || 'Analyzing product website...'),
              isFinalStreamStep ? 90 : mapStreamProgressToUi(streamData.progress)
            );
          } else if (streamData.message) {
            globalFormState.loadingMessage.value = streamData.message;
          }
        }
      });

      console.log('fetchRemainingData: Stream finished. Extracted data object:', data);
      console.log('fetchRemainingData: Extracted data object:', data);

      updateAutofillProgress('Applying extracted data to the form...', 91);

      // Update form data sequentially to give visual feedback
      // 1. Content (Description & Detailed Tagline)
      if (shouldFetchContent) {
        console.log('fetchRemainingData: Updating content fields...');

        // The streaming endpoint uses TaglineRewriterService (AI rewrite),
        // so its taglines should ALWAYS override the initial heuristic ones.
        if (!contentOnly && data.tagline && data.tagline.trim() !== '') {
          const tagline = data.tagline.length > 140 ? data.tagline.substring(0, 137) + '...' : data.tagline;
          console.log('fetchRemainingData: Setting AI-rewritten tagline to:', tagline);
          form.tagline = tagline;
        }

        if (!contentOnly && data.tagline_detailed && data.tagline_detailed.trim() !== '') {
          const detailed = data.tagline_detailed.length > 160 ? data.tagline_detailed.substring(0, 157) + '...' : data.tagline_detailed;
          console.log('fetchRemainingData: Setting AI-rewritten tagline_detailed to:', detailed);
          form.tagline_detailed = detailed;
        }

        if (forceDescriptionOverwrite || !form.description || form.description.trim() === '' || form.description === '<p></p>') {
          console.log('fetchRemainingData: Setting description to:', data.description);
          form.description = data.description || form.description;
        }
        loadingStates.description = false; // Turn off immediately for visual progress
        updateAutofillProgress('Applying extracted product copy...', 94);
        await new Promise(r => setTimeout(r, 300)); // Small delay for visual effect
      }

      // 2. Categories
      if (shouldFetchCategoriesAndBestFor) {
        console.log('fetchRemainingData: Updating categories/useCases/bestFor/pricing...', {
          categories: data.categories,
          useCases: data.useCases,
          platforms: data.platforms,
          bestFor: data.bestFor,
          pricing: data.pricing,
          techStacks: data.tech_stacks,
          suggestedCategories: data.suggestedCategories
        });

        // Set categories from classifier (only if not already user-selected)
        if (data.categories && data.categories.length > 0 && (!form.categories || form.categories.length === 0)) {
          form.categories = data.categories;
          console.log('fetchRemainingData: Set categories to:', data.categories);
        }

        if (data.useCases && data.useCases.length > 0 && (!form.useCases || form.useCases.length === 0)) {
          form.useCases = data.useCases;
          console.log('fetchRemainingData: Set useCases to:', data.useCases);
        }

        // Set bestFor from classifier (only if not already user-selected)
        if (data.bestFor && data.bestFor.length > 0 && (!form.bestFor || form.bestFor.length === 0)) {
          form.bestFor = data.bestFor;
          console.log('fetchRemainingData: Set bestFor to:', data.bestFor);
        }

        if (data.platforms && data.platforms.length > 0 && (!form.platforms || form.platforms.length === 0)) {
          form.platforms = data.platforms;
          console.log('fetchRemainingData: Set platforms to:', data.platforms);
        }

        if (data.tech_stacks && data.tech_stacks.length > 0 && (!form.tech_stack || form.tech_stack.length === 0)) {
          form.tech_stack = data.tech_stacks;
          console.log('fetchRemainingData: Set tech_stack to:', data.tech_stacks);
        }

        // Set pricing from classifier
        if (data.pricing && data.pricing.length > 0) {
          form.pricing = data.pricing;
          console.log('fetchRemainingData: Set pricing to:', data.pricing);
        }

        // Auto-add suggested categories (classifier names that don't exist in DB) as custom entries
        if (data.suggestedCategories && data.suggestedCategories.length > 0) {
          const existingCustomNames = (form.categories_custom || []).map(c => c.name.toLowerCase());
          const newCustomCategories = data.suggestedCategories
            .filter(name => !existingCustomNames.includes(name.toLowerCase()))
            .slice(0, 3) // Max 3 custom categories
            .map(name => ({
              id: `custom-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`,
              name: name,
              is_custom: true
            }));
          if (newCustomCategories.length > 0) {
            form.categories_custom = [...(form.categories_custom || []), ...newCustomCategories].slice(0, 3);
            console.log('fetchRemainingData: Auto-added suggested custom categories:', newCustomCategories.map(c => c.name));
          }
        }

        if (data.suggestedUseCases && data.suggestedUseCases.length > 0) {
          const existingCustomNames = (form.useCases_custom || []).map(c => c.name.toLowerCase());
          const newCustomUseCases = data.suggestedUseCases
            .filter(name => !existingCustomNames.includes(name.toLowerCase()))
            .slice(0, 3)
            .map(name => ({
              id: `custom-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`,
              name,
              is_custom: true
            }));

          if (newCustomUseCases.length > 0) {
            form.useCases_custom = [...(form.useCases_custom || []), ...newCustomUseCases].slice(0, 3);
            console.log('fetchRemainingData: Auto-added suggested custom use cases:', newCustomUseCases.map(c => c.name));
          }
        }

        updateAutofillProgress('Applying categories, use cases, and pricing...', 97);
      }

      // 3. Update screenshot if available (refresh with viewport-specific version)
      if (!contentOnly) {
        updateAutofillProgress('Finalizing media, links, and logo suggestions...', 99);

        if (data.screenshot_url && !globalFormState.manualScreenshotChosen.value) {
          globalFormState.galleryPreviews.value[0] = data.screenshot_url;
        }

        // 3.5. Update pricing page url if available
        if (data.pricing_page_url && (!form.pricing_page_url || form.pricing_page_url.trim() === '')) {
          form.pricing_page_url = data.pricing_page_url;
          console.log('fetchRemainingData: Auto-filled pricing_page_url with:', data.pricing_page_url);
        }

        applyAutofillLinks(data);

        // 4. Update logos if available
        if (data.logos && data.logos.length > 0) {
          form.logos = data.logos;
          // If we don't have a logo preview yet, or it's just the favicon, set it to the best logo
          if (
            !globalFormState.manualLogoChosen.value &&
            (!globalFormState.logoPreview.value || globalFormState.logoPreview.value === form.favicon)
          ) {
            globalFormState.logoPreview.value = data.logos[0];
          }
        }
      }

    } catch (error) {
      console.error('Error fetching remaining data:', error);
      // Check if it's a timeout error
      const isTimeoutError = error.code === 'ECONNABORTED' || (error.response && error.response.status === 408);

      // Only show error message if this is during active form filling, not during restoration
      if (shouldFetchContent) {
        extractionErrors.tagline = 'Failed to extract taglines.';
        extractionErrors.description = 'Failed to extract description.';
      }
      if (shouldFetchCategoriesAndBestFor) {
        extractionErrors.categories = 'Failed to extract categories.';
        extractionErrors.useCases = 'Failed to extract use cases.';
        extractionErrors.bestFor = 'Failed to extract "best for" labels.';
      }

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
        extractionErrors.logos = 'Failed to extract logos.';
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
        completeAutofillProgress();
        globalFormState.isLoading.value = false;
        globalFormState.loadingTargetProgress.value = 0;
        globalFormState.loadingStartedAt.value = null;
        console.log('Resetting general isLoading to false');
      }
    }
  };

  const extractLogos = async () => {
    console.log('extractLogos called');
    await fetchRemainingData(true);
  };

  const simulateSandboxAutofill = async () => {
    if (globalFormState.isLoading.value) {
      return false;
    }

    const sandboxPayload = buildSandboxAutofillPayload();
    globalFormState.sandboxNotice.value = '';
    globalFormState.urlExistsError.value = false;
    globalFormState.existingProduct.value = null;
    showErrorMessage.value = false;
    errorMessage.value = '';

    loadingStates.name = true;
    loadingStates.description = true;
    loadingStates.categories = true;
    loadingStates.bestFor = true;
    loadingStates.logos = true;

    beginAutofillProgress('Checking website URL...', 5, 'fullAutofill');

    try {
      await delay(260);
      form.link = sandboxPayload.link;
      updateAutofillProgress('Fetching basic metadata and taking screenshot...', 10);

      await delay(420);
      form.name = sandboxPayload.name;
      form.tagline = sandboxPayload.tagline;
      form.tagline_detailed = sandboxPayload.tagline_detailed;
      form.favicon = sandboxPayload.logos[0];
      if (!globalFormState.manualLogoChosen.value) {
        globalFormState.logoPreview.value = sandboxPayload.logos[0];
      }
      if (!globalFormState.manualScreenshotChosen.value) {
        globalFormState.galleryPreviews.value[0] = sandboxPayload.screenshot;
      }
      loadingStates.name = false;
      updateAutofillProgress('Basic metadata received. Preparing detailed analysis...', 30);

      await delay(520);
      updateAutofillProgress('Connecting for detailed analysis...', 35);

      await delay(420);
      updateAutofillProgress('Reading page content and rewriting product summary...', 56);

      await delay(420);
      form.description = sandboxPayload.description;
      loadingStates.description = false;
      updateAutofillProgress('Mapping categories, use cases, pricing, and tags...', 74);

      await delay(420);
      form.categories = sandboxPayload.categories;
      form.useCases = sandboxPayload.useCases;
      form.platforms = sandboxPayload.platforms;
      form.bestFor = sandboxPayload.bestFor;
      form.pricing = sandboxPayload.pricing;
      form.pricing_page_url = sandboxPayload.pricing_page_url;
      form.x_account = sandboxPayload.x_account;
      form.maker_links = sandboxPayload.maker_links;
      loadingStates.categories = false;
      loadingStates.bestFor = false;
      updateAutofillProgress('Finishing logo and media suggestions...', 92);

      await delay(360);
      form.logos = sandboxPayload.logos;
      loadingStates.logos = false;
      completeAutofillProgress();
      globalFormState.isLoading.value = false;
      globalFormState.loadingTargetProgress.value = 0;
      globalFormState.loadingStartedAt.value = null;
      return true;
    } catch (error) {
      console.error('Sandbox autofill simulation failed:', error);
      showErrorMessage.value = true;
      errorMessage.value = 'Sandbox autofill failed. Please try again.';
      globalFormState.isLoading.value = false;
      globalFormState.loadingTargetProgress.value = 0;
      globalFormState.loadingStartedAt.value = null;
      stopLoadingAnimation();
      return false;
    } finally {
      Object.keys(loadingStates).forEach((key) => {
        loadingStates[key] = false;
      });
      globalFormState.loadingMessage.value = '';
      globalFormState.loadingProgress.value = 0;
      globalFormState.loadingSessionType.value = null;
    }
  };

  const rewriteProductDescription = async (urlOverride = null) => {
    const linkValue = urlOverride || form.link;

    if (!linkValue || linkValue.trim() === '') {
      extractionErrors.description = 'Product URL is required to rewrite the description.';
      return;
    }

    showErrorMessage.value = false;
    extractionErrors.description = '';
    loadingStates.description = true;
    globalFormState.isLoading.value = true;
    beginAutofillProgress('Preparing description rewrite...', 35, 'fullAutofill');

    try {
      await fetchRemainingData(false, linkValue, {
        forceContentFetch: true,
        forceDescriptionOverwrite: true,
        contentOnly: true,
      });
    } catch (error) {
      console.error('Error rewriting product description:', error);
      extractionErrors.description = 'Failed to rewrite description.';
      showErrorMessage.value = true;
      errorMessage.value = 'Failed to rewrite the product description. Please try again.';
    } finally {
      loadingStates.description = false;
      const anyLoadingActive = Object.values(loadingStates).some((loading) => loading === true);
      if (!anyLoadingActive) {
        completeAutofillProgress();
        globalFormState.isLoading.value = false;
      }
    }
  };

  const updateForm = async (field, value) => {
    console.log('updateForm called for field:', field, 'with value:', value);
    // Preserve the fromSource field when updating
    const preservedFromSource = form.fromSource;
    form[field] = value;
    // Restore the fromSource field after updating
    if (preservedFromSource) {
      form.fromSource = preservedFromSource;
    }

    // Check URL existence when the link field is updated
    if (field === 'link') {
      console.log('Link field updated, calling checkUrlExists');
      await checkUrlExists();
    }
  };

  const updateFormMultiple = async (updates) => {
    // Preserve the fromSource field if it exists in the current form
    const preservedFromSource = form.fromSource;

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

    // Restore the fromSource field if it was preserved
    if (preservedFromSource) {
      form.fromSource = preservedFromSource;
    }

    // Check URL existence if the link field was updated
    if (updates.link !== undefined) {
      await checkUrlExists();
    }
  };

  const resetForm = () => {
    // Preserve the fromSource when resetting the form
    const preservedFromSource = form.fromSource;
    Object.assign(form, { ...createProductFormState().form });
    form.fromSource = preservedFromSource;
    globalFormState.logoPreview.value = null;
    globalFormState.galleryPreviews.value = [null];
    resetManualMediaChoices();
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
      globalFormState.allUseCases.value = categoriesResponse.data.useCases || [];
      globalFormState.allPlatforms.value = categoriesResponse.data.platforms || [];
      globalFormState.allBestFor.value = categoriesResponse.data.bestFor;
      globalFormState.allPricing.value = categoriesResponse.data.pricing;
      globalFormState.allTechStacks.value = techStackResponse.data;

      // Initialize isAdmin state
      const element = document.getElementById('product-submit-app');
      if (element) {
        globalFormState.isAdmin.value = element.getAttribute('data-is-admin') === 'true';
        globalFormState.submissionBgUrl.value = element.getAttribute('data-submission-bg-url') || '';
      }
    } catch (error) {
      console.error('Failed to fetch initial form data:', error);
      showErrorMessage.value = true;
      errorMessage.value = 'Failed to load form options. Some features may not work properly.';
    }

    // Load initial data from the HTML element attributes first (for editing existing products)
    // This ensures that if we're editing an existing product, we load that data first
    await loadInitialDataFromElement();

    // Then load saved data (from session storage) which might override initial data
    // Only load saved data if we're not editing an existing product (to prevent override)
    const element = document.getElementById('product-submit-app');
    if (element) {
      const displayData = element.getAttribute('data-display-data');
      const isAdmin = element.getAttribute('data-is-admin');
      // If we're editing an existing product (either as admin or regular user), don't load saved data as it may override the loaded product data
      if (!displayData) {
        // When we're on a create page (not editing), we should clear any old saved form data to start fresh
        // This prevents users from seeing old data they were previously editing
        productFormService.clearFormData();
        await loadSavedData();
      }
    } else {
      // If element is not available yet, try loading saved data after a short delay
      setTimeout(async () => {
        // If we are on the create page (no display data attributes found even after delay),
        // we should probably NOT restore the ID to prevent switching to "edit" mode inadvertently.
        // However, verifying absence of attributes entirely via getElementById again is safer.
        const el = document.getElementById('product-submit-app');
        if (el && !el.getAttribute('data-display-data')) {
          // We are likely adding a product. Ensure we don't restore a stale ID.
          const saved = productFormService.loadFormData();
          if (saved && saved.id) {
            console.log('Clearing stale ID from saved data for new submission');
            saved.id = null;
            sessionStorage.setItem('productFormData', JSON.stringify(saved));
          }
        }
        await loadSavedData();
      }, 10);
    }

    // Capture query parameters from URL and store them in form data if applicable
    const urlParams = new URLSearchParams(window.location.search);
    const fromParam = urlParams.get('from');
    if (fromParam) {
      form.fromSource = fromParam;
    }
  };

  // Load initial data from HTML element attributes (for editing existing products)
  const loadInitialDataFromElement = async () => {
    // Try to load immediately
    await tryLoadInitialData();

    // If element is not found, set up a MutationObserver to wait for it to be added to the DOM
    if (!document.getElementById('product-submit-app')) {
      const observer = new MutationObserver(async (mutationsList) => {
        for (const mutation of mutationsList) {
          if (mutation.type === 'childList') {
            const element = document.getElementById('product-submit-app');
            if (element) {
              observer.disconnect(); // Stop observing once we find the element
              await tryLoadInitialData();
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
  const tryLoadInitialData = async () => {
    const element = document.getElementById('product-submit-app');
    if (element) {
      console.log('Found product-submit-app element, attempting to load initial data');

      // Get data attributes from the element
      const displayData = element.getAttribute('data-display-data');
      const isAdmin = element.getAttribute('data-is-admin');
      const allUseCases = element.getAttribute('data-use-case-categories');
      const allPricing = element.getAttribute('data-pricing-categories');
      const allPlatforms = element.getAttribute('data-platform-categories');
      const selectedUseCaseCategories = element.getAttribute('data-selected-use-case-categories');
      const selectedBestForCategories = element.getAttribute('data-selected-best-for-categories');
      const selectedPlatformCategories = element.getAttribute('data-selected-platform-categories');

      console.log('Data attributes:', { displayData, isAdmin, allUseCases, allPricing, allPlatforms, selectedUseCaseCategories, selectedBestForCategories, selectedPlatformCategories });

      if (displayData) {
        try {
          const initialData = JSON.parse(displayData);
          console.log('Parsed initial data:', initialData);

          // For admin users, always load the original product data regardless of pending edits
          if (isAdmin === 'true') {
            console.log('Loading data for admin user');

            const separatedCategories = splitSelectedCategoryIds(initialData.current_categories || [], {
              useCaseCategories: allUseCases,
              pricingCategories: allPricing,
              platformCategories: allPlatforms,
              selectedUseCaseCategories,
              selectedBestForCategories,
              selectedPlatformCategories,
            });

            console.log('Category IDs - Regular:', separatedCategories.categories, 'UseCases:', separatedCategories.useCases, 'Pricing:', separatedCategories.pricing, 'BestFor:', separatedCategories.bestFor, 'Platforms:', separatedCategories.platforms);

            // Format the logo preview URL if it's a relative path
            let logoUrl = initialData.logo_url || initialData.logo;
            if (logoUrl && !logoUrl.startsWith('http') && !logoUrl.startsWith('/storage')) {
              logoUrl = `/storage/${logoUrl}`;
            }

            // Parse video URL if it's in JSON format
            let parsedVideoUrl = initialData.video_url;
            if (typeof parsedVideoUrl === 'string' && (parsedVideoUrl.startsWith('{') || parsedVideoUrl.startsWith('"'))) {
              try {
                if (parsedVideoUrl.startsWith('"')) {
                  parsedVideoUrl = JSON.parse(parsedVideoUrl);
                }
                const parsed = typeof parsedVideoUrl === 'string' ? JSON.parse(parsedVideoUrl) : parsedVideoUrl;
                if (parsed && typeof parsed === 'object') {
                  if (parsed.embed_url) {
                    parsedVideoUrl = parsed.embed_url;
                  } else if (parsed.url) {
                    parsedVideoUrl = parsed.url;
                  }
                }
              } catch (e) {
                console.error('Error parsing video URL JSON:', e);
                parsedVideoUrl = initialData.video_url;
              }
            }

            // Capture the from parameter if present in URL
            const urlParams = new URLSearchParams(window.location.search);
            const fromParam = urlParams.get('from');

            console.log('Admin logo path:', initialData.logo, 'Preview URL:', logoUrl);

            await updateFormMultiple({
              name: initialData.name || '',
              slug: initialData.slug || '',
              tagline: initialData.tagline || '',
              tagline_detailed: initialData.product_page_tagline || initialData.tagline_detailed || '',
              description: initialData.description || '',
              link: initialData.link || '',
              categories: separatedCategories.categories,
              useCases: separatedCategories.useCases,
              platforms: separatedCategories.platforms,
              bestFor: separatedCategories.bestFor,
              pricing: separatedCategories.pricing,
              tech_stack: (initialData.current_tech_stacks || []).map(id => parseInt(id)),
              video_url: parsedVideoUrl,
              id: initialData.id, // Set the product ID
              maker_links: initialData.maker_links || [],
              sell_product: !!initialData.sell_product,
              asking_price: initialData.asking_price,
              pricing_page_url: initialData.pricing_page_url || '',
              x_account: initialData.x_account,
              fromSource: fromParam || null,
              comparison_overrides_input: initialData.comparison_overrides_input || '',
              alternative_overrides_input: initialData.alternative_overrides_input || '',
              logo: initialData.logo || null,
              favicon: initialData.logo_url || logoUrl || null,
              logos: initialData.logos || [],
            });

            if (logoUrl) {
              globalFormState.logoPreview.value = logoUrl;
              console.log('Set logoPreview to:', globalFormState.logoPreview.value);
            }

            // Populate gallery previews from initial data
            if (initialData.gallery && Array.isArray(initialData.gallery)) {
              const galleryPreviews = [null];
              initialData.gallery.forEach((url, index) => {
                if (index < 1) galleryPreviews[index] = url;
              });
              globalFormState.galleryPreviews.value = galleryPreviews;
              console.log('Set galleryPreviews to:', globalFormState.galleryPreviews.value);
            }

            console.log('Updated form with initial data for admin');

            // Set step to 2 to show the form
            globalFormState.step.value = 2;
            globalFormState.isRestored.value = true;
            globalFormState.isMounted.value = true;
            console.log('Set step to 2 for admin user editing');
          } else {
            console.log('Loading data for regular user');
            console.log('Initial data:', initialData);
            console.log('All use case categories (raw):', allUseCases);
            console.log('All pricing categories (raw):', allPricing);
            console.log('Selected use case categories (raw):', selectedUseCaseCategories);
            console.log('Selected best for categories (raw):', selectedBestForCategories);

            // Format the logo preview URL if it's a relative path
            let logoUrl = initialData.logo_url || initialData.logo;
            if (logoUrl && !logoUrl.startsWith('http') && !logoUrl.startsWith('/storage')) {
              logoUrl = `/storage/${logoUrl}`;
            }

            const separatedCategories = splitSelectedCategoryIds(initialData.current_categories || [], {
              useCaseCategories: allUseCases,
              pricingCategories: allPricing,
              platformCategories: allPlatforms,
              selectedUseCaseCategories,
              selectedBestForCategories,
              selectedPlatformCategories,
            });

            console.log('All category IDs:', initialData.current_categories || []);
            console.log('Separated category IDs:', separatedCategories);

            // Parse video URL if it's in JSON format
            let parsedVideoUrl = initialData.video_url;
            if (typeof parsedVideoUrl === 'string' && (parsedVideoUrl.startsWith('{') || parsedVideoUrl.startsWith('"'))) {
              try {
                if (parsedVideoUrl.startsWith('"')) {
                  parsedVideoUrl = JSON.parse(parsedVideoUrl);
                }
                const parsed = typeof parsedVideoUrl === 'string' ? JSON.parse(parsedVideoUrl) : parsedVideoUrl;
                if (parsed && typeof parsed === 'object') {
                  if (parsed.embed_url) {
                    parsedVideoUrl = parsed.embed_url;
                  } else if (parsed.url) {
                    parsedVideoUrl = parsed.url;
                  }
                }
              } catch (e) {
                console.error('Error parsing video URL JSON:', e);
                parsedVideoUrl = initialData.video_url;
              }
            }

            // Capture the from parameter if present in URL
            const urlParams = new URLSearchParams(window.location.search);
            const fromParam = urlParams.get('from');

            const formUpdates = {
              name: initialData.name || '',
              slug: initialData.slug || '',
              tagline: initialData.tagline || '',
              tagline_detailed: initialData.product_page_tagline || initialData.tagline_detailed || '',
              description: initialData.description || '',
              link: initialData.link || '',
              categories: separatedCategories.categories,
              useCases: separatedCategories.useCases,
              platforms: separatedCategories.platforms,
              bestFor: separatedCategories.bestFor,
              pricing: separatedCategories.pricing,
              tech_stack: (initialData.current_tech_stacks || []).map(id => parseInt(id)),
              video_url: parsedVideoUrl,
              id: initialData.id, // Set the product ID
              maker_links: initialData.maker_links || [],
              sell_product: !!initialData.sell_product,
              asking_price: initialData.asking_price,
              pricing_page_url: initialData.pricing_page_url || '',
              x_account: initialData.x_account,
              fromSource: fromParam || null,
              comparison_overrides_input: initialData.comparison_overrides_input || '',
              alternative_overrides_input: initialData.alternative_overrides_input || '',
              logo: initialData.logo || null,
              favicon: initialData.logo_url || logoUrl || null,
              logos: initialData.logos || [],
            };

            console.log('Form updates:', formUpdates);

            try {
              await updateFormMultiple(formUpdates);
            } catch (e) {
              console.error('Error updating form with initial data:', e);
            }

            if (logoUrl) {
              globalFormState.logoPreview.value = logoUrl;
            }

            // Populate gallery previews from initial data
            if (initialData.gallery && Array.isArray(initialData.gallery)) {
              const galleryPreviews = [null];
              initialData.gallery.forEach((url, index) => {
                if (index < 1) galleryPreviews[index] = url;
              });
              globalFormState.galleryPreviews.value = galleryPreviews;
            }

            // Set step to 2 to show the form ONLY if we have a link (meaning we're editing or restoring)
            if (initialData.link) {
              globalFormState.step.value = 2;
            }
            globalFormState.isRestored.value = true;
            globalFormState.isMounted.value = true;
          }
        } catch (error) {
          console.error('Error parsing initial data from element:', error);
          // Fallback: try to initialize with basic data
          const element = document.getElementById('product-submit-app');
          if (element) {
            const displayData = element.getAttribute('data-display-data');
            const isAdmin = element.getAttribute('data-is-admin');
            const allUseCases = element.getAttribute('data-use-case-categories');
            const allPricing = element.getAttribute('data-pricing-categories');
            const allPlatforms = element.getAttribute('data-platform-categories');
            const selectedUseCaseCategories = element.getAttribute('data-selected-use-case-categories');
            const selectedBestForCategories = element.getAttribute('data-selected-best-for-categories');
            const selectedPlatformCategories = element.getAttribute('data-selected-platform-categories');

            if (displayData) {
              try {
                const initialData = JSON.parse(displayData);
                const separatedCategories = splitSelectedCategoryIds(initialData.current_categories || [], {
                  useCaseCategories: allUseCases,
                  pricingCategories: allPricing,
                  platformCategories: allPlatforms,
                  selectedUseCaseCategories,
                  selectedBestForCategories,
                  selectedPlatformCategories,
                });

                if (isAdmin === 'true') {
                  let parsedVideoUrl = initialData.video_url || '';
                  if (typeof parsedVideoUrl === 'string' && (parsedVideoUrl.startsWith('{') || parsedVideoUrl.startsWith('"'))) {
                    try {
                      if (parsedVideoUrl.startsWith('"')) {
                        parsedVideoUrl = JSON.parse(parsedVideoUrl);
                      }
                      const parsed = typeof parsedVideoUrl === 'string' ? JSON.parse(parsedVideoUrl) : parsedVideoUrl;
                      if (parsed && typeof parsed === 'object') {
                        if (parsed.embed_url) {
                          parsedVideoUrl = parsed.embed_url;
                        } else if (parsed.url) {
                          parsedVideoUrl = parsed.url;
                        }
                      }
                    } catch (e) {
                      console.error('Error parsing video URL JSON in fallback:', e);
                      parsedVideoUrl = initialData.video_url || '';
                    }
                  }

                  // Capture the from parameter if present in URL
                  const urlParams = new URLSearchParams(window.location.search);
                  const fromParam = urlParams.get('from');

                  await updateFormMultiple({
                    name: initialData.name || '',
                    tagline: initialData.tagline || '',
                    tagline_detailed: initialData.product_page_tagline || initialData.tagline_detailed || '',
                    description: initialData.description || '',
                    link: initialData.link || '',
                    categories: separatedCategories.categories,
                    useCases: separatedCategories.useCases,
                    platforms: separatedCategories.platforms,
                    bestFor: separatedCategories.bestFor,
                    pricing: separatedCategories.pricing,
                    tech_stack: initialData.current_tech_stacks || [],
                    video_url: parsedVideoUrl,
                    fromSource: fromParam || null,
                    comparison_overrides_input: initialData.comparison_overrides_input || '',
                    alternative_overrides_input: initialData.alternative_overrides_input || '',
                  });
                } else {
                  // Capture the from parameter if present in URL
                  const urlParams = new URLSearchParams(window.location.search);
                  const fromParam = urlParams.get('from');

                  await updateFormMultiple({
                    name: initialData.name || '',
                    tagline: initialData.tagline || '',
                    tagline_detailed: initialData.product_page_tagline || initialData.tagline_detailed || '',
                    description: initialData.description || '',
                    link: initialData.link || '',
                    categories: separatedCategories.categories,
                    useCases: separatedCategories.useCases,
                    platforms: separatedCategories.platforms,
                    bestFor: separatedCategories.bestFor,
                    pricing: separatedCategories.pricing,
                    tech_stack: initialData.current_tech_stacks || [],
                    video_url: initialData.video_url || '',
                    id: initialData.id, // Set the product ID
                    maker_links: initialData.maker_links || [],
                    sell_product: !!initialData.sell_product,
                    asking_price: initialData.asking_price,
                    pricing_page_url: initialData.pricing_page_url || '',
                    x_account: initialData.x_account,
                    fromSource: fromParam || null,
                    comparison_overrides_input: initialData.comparison_overrides_input || '',
                    alternative_overrides_input: initialData.alternative_overrides_input || '',
                    logo: initialData.logo || null,
                    favicon: initialData.logo_url || null,
                  });
                }
                globalFormState.step.value = 2;
                globalFormState.isRestored.value = true;
                globalFormState.isMounted.value = true;
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

  // Function to clear saved form data from session storage
  const clearSavedData = () => {
    sessionStorage.removeItem('productFormData');
    console.log('Cleared saved form data from session storage');
  };

  // Load saved data from session storage
  const loadSavedData = async () => {
    // Don't load saved data if we're currently editing an existing product (to prevent override)
    const element = document.getElementById('product-submit-app');
    if (element) {
      const displayData = element.getAttribute('data-display-data');

      // If we're editing an existing product (either as admin or regular user), skip loading saved data
      if (displayData) {
        console.log('Skipping loading saved data because we are editing an existing product');
        return;
      }
    }

    const savedData = productFormService.loadFormData();
    if (savedData) {
      if (savedData.link) {
        await updateFormMultiple(savedData);
        globalFormState.logoPreview.value = savedData.logoPreview || null;
        globalFormState.galleryPreviews.value = savedData.galleryPreviews
          ? [savedData.galleryPreviews[0] || null]
          : [null];
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
    submitState: globalFormState.submitState,
    urlExistsError: globalFormState.urlExistsError,
    existingProduct: globalFormState.existingProduct,
    sandboxNotice: globalFormState.sandboxNotice,
    showPreviewModal: globalFormState.showPreviewModal,
    submissionBgUrl: globalFormState.submissionBgUrl,
    extractionErrors: globalFormState.extractionErrors,
    loadingProgress: globalFormState.loadingProgress,
    loadingMessage: globalFormState.loadingMessage,
    loadingStates,
    logoPreview: globalFormState.logoPreview,
    galleryPreviews: globalFormState.galleryPreviews,
    allCategories: globalFormState.allCategories,
    allUseCases: globalFormState.allUseCases,
    allPlatforms: globalFormState.allPlatforms,
    allBestFor: globalFormState.allBestFor,
    allPricing: globalFormState.allPricing,
    allTechStacks: globalFormState.allTechStacks,
    isAdmin: globalFormState.isAdmin,
    form,
    sidebarSteps,
    errorMessage,
    showErrorMessage,
    isUrlInvalid,
    urlTrimSuggestion,
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
    simulateSandboxAutofill,
    extractLogos,
    rewriteProductDescription,
    updateForm,
    updateFormMultiple,
    resetForm,
    initializeFormData,
    loadSavedData,
    saveFormData,
    clearSavedData,
    checkUrlExists,
    markManualLogoChosen,
    markManualScreenshotChosen,
    resetManualMediaChoices
  };
}

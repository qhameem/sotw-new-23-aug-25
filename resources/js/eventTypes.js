// Define event types for better type safety and documentation

export const EVENT_TYPES = {
 // Form-related events
  FORM_UPDATED: 'form-updated',
  
  // Tab switching events
  SWITCH_TAB: 'switch-tab',
  
  // Field-related events
 FIELD_CLICKED: 'field-clicked',
  
  // Product submission events
  SUBMIT_PRODUCT: 'submit-product',
  CONFIRM_SUBMIT: 'confirm-submit',
  CLOSE_MODAL: 'close-modal',
  
  // Navigation events
  GET_STARTED: 'get-started',
  CLEAR_URL: 'clear-url',
  GO_BACK: 'go-back',
  GO_NEXT: 'go-next',
  
  // Data fetching events
  FETCH_INITIAL_DATA: 'fetch-initial-data',
  FETCH_REMAINING_DATA: 'fetch-remaining-data',
  
  // Error handling events
  SHOW_ERROR: 'show-error',
  HIDE_ERROR: 'hide-error',
};

// Define event payload schemas for better validation
export const EVENT_PAYLOAD_SCHEMAS = {
  [EVENT_TYPES.FORM_UPDATED]: {
    link: 'string',
    name: 'string',
    tagline: 'string',
    tagline_detailed: 'string',
    description: 'string',
    logo: 'any', // Accept any type including objects (File objects) and strings (URLs)
    selectedPricing: 'array'
  },
  
  [EVENT_TYPES.SWITCH_TAB]: {
    tabName: 'string' // Expected to be one of: 'mainInfo', 'imagesAndMedia', 'extras', 'launchChecklist'
  },
  
  [EVENT_TYPES.FIELD_CLICKED]: {
    fieldKey: 'string'
  }
};
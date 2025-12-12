# Product Submission Form Documentation

This document provides a comprehensive overview of the product submission form system, including its components, flow, and functionality.

## Overview

The product submission form is a multi-step form that allows users to submit their products to the platform. It's built using Vue.js 3 composition API and follows a step-by-step approach to collect product information.

## Architecture

The form is composed of several Vue.js components that work together:

### Main Components

1. **ProductSubmit.vue** - The main orchestrator component that manages the overall flow
2. **ProductURLInput.vue** - Handles initial URL input and validation
3. **ProductDetailsForm.vue** - Collects basic product information (name, tagline, description, etc.)
4. **ProductMediaForm.vue** - Handles media uploads (logo, gallery, video)
5. **ProductMakersForm.vue** - Manages maker information
6. **ProductExtrasForm.vue** - Collects additional product details
7. **LaunchChecklistForm.vue** - Final verification step
8. **SubmissionSidebar.vue** - Sidebar navigation for steps
9. **ProductPreviewModal.vue** - Final preview before submission

## Form Flow

The form follows this sequence:

1. **Step 1**: Product URL Input
2. **Step 2**: Main Information
3. **Step 3**: Images and Media
4. **Step 4**: Makers
5. **Step 5**: Extras
6. **Step 6**: Launch Checklist
7. **Final**: Preview and Submit

## Component Details

### ProductSubmit.vue (Main Controller)

This is the parent component that manages the state and flow between different steps. It maintains:

- Form data in a reactive `form` object
- Current step state (`step` ref)
- Current tab state (`currentTab` ref)
- Session storage persistence
- API communication with backend

### ProductURLInput.vue

Handles the initial product URL input with:
- URL validation
- Duplicate checking
- Initial metadata fetching
- Error handling for invalid URLs

### ProductDetailsForm.vue

Collects core product information:
- Product name (max 40 characters)
- Tagline (max 60 characters)
- Detailed tagline (max 160 characters)
- Product links (primary + additional)
- X/Twitter account
- Product description (using WYSIWYG editor)
- Categories (1-3 selections)
- Best for (1-3 selections)
- Pricing models (checkbox selection)
- "Next Step: Images and media" button

### ProductMediaForm.vue

Handles media uploads:
- Logo upload (JPG, PNG, GIF, SVG, WEBP, AVIF)
- Gallery images (up to 3 images)
- Video URL with thumbnail preview
- "Next Step: Makers" button

### ProductMakersForm.vue

Manages maker information:
- Maker name
- Maker URL
- Maker description
- "Next Step: Extras" button

### ProductExtrasForm.vue

Collects additional information:
- Tech stack selection
- Selling product option
- Asking price (if selling)
- "Next Step: Launch Checklist" button

### LaunchChecklistForm.vue

Final verification step:
- Displays completion percentage
- Shows status of required fields
- Pricing options (Free vs Paid launch)
- Submit button

### SubmissionSidebar.vue

Provides navigation between steps:
- Visual indicators for each step
- Progress tracking
- Ability to jump between completed steps

## Data Structure

The form data is stored in a reactive object with these properties:

```javascript
form = {
  link: '',                    // Product URL
  name: '',                    // Product name
 tagline: '',                 // Short tagline
  tagline_detailed: '',        // Detailed tagline
  description: '',             // Product description
  categories: [],              // Selected categories
  bestFor: [],                 // Target audience
  pricing: [],                 // Pricing models
  tech_stack: [],              // Technology stack
  favicon: '',                 // Favicon URL
  logo: null,                  // Logo file object
  gallery: Array(3).fill(null), // Gallery images
  video_url: '',               // Video URL
  logos: [],                   // Suggested logos
  maker_links: [],             // Maker information
  sell_product: false,         // Selling product flag
  asking_price: null,          // Asking price
  additionalLinks: []          // Additional product links
}
```

## API Endpoints

The form communicates with these backend endpoints:

- `/api/fetch-initial-metadata` - Fetches initial product metadata
- `/api/process-url` - Processes URL and extracts additional data
- `/api/categories` - Fetches available categories, bestFor, and pricing options
- `/api/tech-stacks` - Fetches available tech stacks
- `/api/submit-product` - Submits the final product

## State Management

- Form data is persisted in sessionStorage to prevent data loss during navigation
- Loading states are tracked for different fields (name, tagline, description, etc.)
- AI generation flags (for tagline, description, and categories) are stored in localStorage with a unique key per product URL to prevent repeated API calls
- Validation is performed at each step before allowing progression

## Event Flow

Components communicate through Vue.js events:

- `@next` - Progress to next step
- `@back` - Return to previous step
- `@update:modelValue` - Update form data
- `@submit` - Submit the product for review

## Validation

- URL validation with proper format checking
- Required field validation at each step
- Character limits for text fields
- File type validation for media uploads
- Category and pricing model selection requirements

## Error Handling

- URL duplication checking
- Network error handling
- Input validation feedback
- Session restoration on page reload

## Special Features

- Auto-saving to sessionStorage
- Image preview and management
- Responsive design for different screen sizes
- Progress tracking and visual indicators
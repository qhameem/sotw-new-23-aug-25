<template>
  <div class="p-6 border border-stone-300 rounded-lg">
    <h2 class="text-sm font-semibold mb-4 text-gray-600">Submission Checklist</h2>
    <!-- Progress bar -->
    <div class="mb-4">
      <div class="w-full bg-gray-200 rounded-full h-2" role="progressbar" :aria-valuenow="completionPercentage" aria-valuemin="0" aria-valuemax="100" :aria-label="`Progress: ${completionPercentage}% complete`">
        <div
          class="h-2 rounded-full bg-green-400 bg-opacity-75"
          :style="{ width: completionPercentage + '%' }"
          :aria-hidden="true"
        ></div>
      </div>
      <div class="text-xs text-gray-500 mt-1">{{ completionPercentage }}% complete</div>
    </div>
    <ul class="space-y-2" role="list">
      <li v-for="item in checklistItems" :key="item.id" class="flex items-center rounded text-sm">
        <button
          type="button"
          class="flex items-center w-full cursor-pointer hover:bg-gray-50 p-1 rounded text-left"
          @click="scrollToField(item.id)"
          :aria-label="`${item.label} ${item.completed ? 'completed' : 'not completed'}`"
          :aria-pressed="item.completed"
        >
          <svg v-if="item.completed" class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          <svg v-else class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2" fill="none"/></svg>
          <span :class="{ 'text-gray-400': !item.completed, 'text-gray-800': item.completed }" class="text-sm">{{ item.label }}</span>
          <!-- Validation indicator -->
          <span v-if="!item.completed" class="ml-auto text-xs text-red-500 font-medium">Incomplete</span>
          <span v-else class="ml-auto text-xs text-green-500 font-medium">Valid</span>
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import emitter from '../eventBus';
import { EVENT_TYPES } from '../eventTypes';

const props = defineProps({
  fieldMappings: {
    type: Array,
    default: () => [
      { id: 'link', label: 'Product URL', completed: false },
      { id: 'name', label: 'Product Name', completed: false },
      { id: 'tagline', label: 'Tagline (List Page)', completed: false },
      { id: 'tagline_detailed', label: 'Tagline (Details Page)', completed: false },
      { id: 'selectedPricing', label: 'Pricing Model', completed: false },
      { id: 'logo', label: 'Logo', completed: false },
      { id: 'description', label: 'Detailed Description', completed: false },
    ]
  },
  fieldValidators: {
    type: Object,
    default: () => ({
      link: (value) => !!value,
      name: (value) => !!value,
      tagline: (value) => !!value,
      tagline_detailed: (value) => !!value,
      selectedPricing: (value) => value && value.length > 0,
      logo: (value) => !!value,
      description: (value) => value && value.length >= 10,
    })
 }
});

const form = ref({
    link: '',
    name: '',
    tagline: '',
    tagline_detailed: '',
    description: '',
    logo: null,
    selectedPricing: [],
});

const checklistItems = ref([...props.fieldMappings]);

const updateChecklist = (newForm) => {
    checklistItems.value = props.fieldMappings.map(field => {
        const validator = props.fieldValidators[field.id] || ((value) => !!value);
        const completed = validator(newForm[field.id]);
        return {
            ...field,
            completed
        };
    });
};

onMounted(() => {
    emitter.on(EVENT_TYPES.FORM_UPDATED, updateChecklist);
});

onUnmounted(() => {
    emitter.off(EVENT_TYPES.FORM_UPDATED, updateChecklist);
});

const scrollToField = (fieldId) => {
  // For the logo field, emit an event to switch to the 'imagesAndMedia' tab instead of trying to find the element
  if (fieldId === 'logo') {
    emitter.emit(EVENT_TYPES.SWITCH_TAB, { tabName: 'imagesAndMedia' });
    return;
  }
  
  let elementId = '';
  
  // Map the checklist field IDs to the actual input field IDs
  switch(fieldId) {
    case 'link':
      elementId = 'product-link'; // From ProductDetailsForm
      break;
    case 'name':
      elementId = 'name'; // From ProductDetailsForm
      break;
    case 'tagline':
      elementId = 'tagline'; // From ProductDetailsForm
      break;
    case 'tagline_detailed':
      elementId = 'tagline_detailed'; // From ProductDetailsForm (textarea)
      break;
    case 'selectedPricing':
      elementId = 'price-1'; // From ProductDetailsForm (first pricing checkbox, but we'll look for any pricing checkbox)
      break;
    case 'description':
      // Description uses a WysiwygEditor, so we need to find the editor container
      elementId = 'description'; // This doesn't have a direct ID, so we'll target the editor
      break;
    default:
      elementId = fieldId;
  }
  
  let element = null;
  
  // Try to find the element using different strategies
  if (elementId) {
    element = document.getElementById(elementId);
  }
  
  // Special handling for specific fields
  if (!element) {
    switch(fieldId) {
      case 'link':
        element = document.getElementById('product-link');
        break;
      case 'selectedPricing':
        // Look for any pricing checkbox
        element = document.querySelector('input[type="checkbox"][id^="price-"]');
        break;
      case 'description':
        // Look for the WysiwygEditor container
        element = document.querySelector('.ProseMirror');
        if (!element) {
          element = document.querySelector('.wysiwyg-editor'); // fallback class
        }
        break;
      default:
        element = document.querySelector(`[name="${fieldId}"]`) || document.querySelector(`[data-field="${fieldId}"]`);
    }
  }
  
  if (element) {
    // Scroll to the element
    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Focus the element if it's focusable
    if (element.focus) {
      element.focus();
    }
 } else {
    // Show user-friendly notification when element is not found
    showNotification(`Unable to locate the ${getLabelForField(fieldId)} field. Please navigate to it manually.`);
  }
};

// Helper function to get user-friendly label for field
const getLabelForField = (fieldId) => {
  const fieldLabels = {
    'link': 'Product URL',
    'name': 'Product Name',
    'tagline': 'Tagline',
    'tagline_detailed': 'Tagline for product details page',
    'selectedPricing': 'Pricing Model',
    'logo': 'Logo',
    'description': 'Description'
  };
  return fieldLabels[fieldId] || fieldId;
};

// Function to show user notification
const showNotification = (message) => {
  // Create a temporary notification element
  const notification = document.createElement('div');
  notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50';
  notification.textContent = message;
  
  // Add close button
 const closeButton = document.createElement('button');
  closeButton.textContent = '×';
  closeButton.className = 'ml-4 text-white font-bold';
  closeButton.onclick = () => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  };
  notification.appendChild(closeButton);
  
  document.body.appendChild(notification);
  
  // Remove the notification after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 5000);
};

// Calculate completion percentage
const completionPercentage = computed(() => {
  if (checklistItems.value.length === 0) return 0;
  const completedItems = checklistItems.value.filter(item => item.completed).length;
 return Math.round((completedItems / checklistItems.value.length) * 100);
});
</script>
<template>
  <div class="bg-sky-50/50 p-6 rounded-2xl border border-sky-100 mb-8">
    <div class="mb-3 flex items-center gap-2">
      <label for="product-url" class="block text-sm font-bold text-gray-900">Website URL <span class="text-red-500">*</span></label>
      <span class="text-xs text-gray-500 flex items-center">
        Enter your URL <span class="mx-1">👇</span> and we'll auto-fill the rest
      </span>
    </div>
    
    <div class="flex items-center gap-3">
      <div class="relative flex-grow">
        <input 
          id="product-url" 
          ref="inputRef" 
          :value="modelValue" 
          @input="handleInput" 
          type="url" 
          required 
          class="block w-full px-6 py-1.5 bg-white border-2 border-sky-200 rounded-xl text-sm shadow-sm placeholder-gray-400
                 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all"
          placeholder="https://your-website.com"
        >
        <!-- Clear Button -->
        <button v-if="modelValue" @click="$emit('clear')" class="absolute inset-y-0 right-4 flex items-center text-gray-400 hover:text-gray-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      
      <button
        @click="performValidationAndFetch"
        :disabled="isLoading"
        class="px-6 py-2 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-md text-sm
               flex items-center gap-2 transition-colors shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 whitespace-nowrap"
      >
        <span v-if="isLoading" class="flex items-center gap-2">
          <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Fetching...
        </span>
        <span v-else class="flex items-center gap-2">
          <!-- <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wand-2">
            <path d="M15 2l-2 2"/>
            <path d="M11 7l-2 2"/>
            <path d="M19 6l-2 2"/>
            <path d="M4 22L11 15L15 19L22 12"/>
          </svg> -->
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" stroke="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M7 7L5.5 5.5M15 7L16.5 5.5M5.5 16.5L7 15M11 5L11 3M5 11L3 11M17.1603 16.9887L21.0519 15.4659C21.4758 15.3001 21.4756 14.7003 21.0517 14.5346L11.6992 10.8799C11.2933 10.7213 10.8929 11.1217 11.0515 11.5276L14.7062 20.8801C14.8719 21.304 15.4717 21.3042 15.6375 20.8803L17.1603 16.9887Z" stroke="currentColor"stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
          AI Auto-fill
        </span>
      </button>
    </div>


    <!-- Loading State Message -->
    <div v-if="isLoading" class="mt-4">
      <div class="flex items-center justify-between text-xs text-sky-600 font-medium mb-1.5">
        <span class="animate-pulse">{{ loadingMessage || 'Analyzing...' }}</span>
        <span>{{ Math.round(loadingProgress || 0) }}%</span>
      </div>
      <div class="w-full bg-sky-100 rounded-full h-1.5 mb-2 overflow-hidden">
        <div class="bg-sky-500 h-1.5 rounded-full transition-all duration-300 ease-out" :style="{ width: `${loadingProgress || 0}%` }"></div>
      </div>
    </div>
    
    <!-- Error Message -->
    <transition name="fade">
      <div v-if="urlExistsError" class="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-600 flex items-start gap-2">
        <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>
          This URL already exists as <a :href="`/product/${existingProduct.slug}`" target="_blank" class="font-bold hover:underline underline-offset-2">"{{ existingProduct.name }}"</a>.
        </span>
      </div>
    </transition>
    
    <!-- Debug Info (Temporary - Hidden for production look) -->
    <!-- <div class="text-xs text-gray-400 mt-2">Debug: Value="{{ modelValue }}" ...</div> -->
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { productFormService } from '../../services/productFormService';

const props = defineProps({
  modelValue: String,
  isLoading: Boolean,
  loadingProgress: Number,
  loadingMessage: String,
  isUrlInvalid: Boolean,
  urlExistsError: Boolean,
  existingProduct: Object,
  submissionBgUrl: String,
});

const emit = defineEmits(['update:modelValue', 'getStarted', 'clear']);

const handleInput = (event) => {
  const value = event.target.value;
  console.log('[ProductURLInput] Input event:', value);
  emit('update:modelValue', value);
};

const logButtonConditions = () => {
  console.log('[ProductURLInput] Button conditions:', {
    isLoading: props.isLoading,
    isUrlInvalid: props.isUrlInvalid,
    urlExistsError: props.urlExistsError,
    urlValue: props.modelValue
  });
};

const performValidationAndFetch = async () => {
  console.log('[ProductURLInput] Starting validation sequence...');
  
  // Get the current value directly from the input element to avoid timing issues
  const inputValue = document.getElementById('product-url')?.value || props.modelValue;
  console.log('[ProductURLInput] Using URL value:', inputValue);
  
  // Step 1: Check if anything is loading
  console.log('[ProductURLInput] Step 1: Checking if anything is loading...');
  if (props.isLoading) {
    console.log('[ProductURLInput] Validation failed: Something is loading');
    return;
  }
  console.log('[ProductURLInput] Step 1 passed: Nothing is loading');
  
  // Step 2: Check if URL is valid
  console.log('[ProductURLInput] Step 2: Checking if URL is valid...');
  console.log('[ProductURLInput] URL to validate:', inputValue);
  const isUrlValid = !productFormService.isUrlInvalid(inputValue);
  if (!isUrlValid) {
    console.log('[ProductURLInput] Validation failed: URL is invalid');
    return;
  }
  console.log('[ProductURLInput] Step 2 passed: URL is valid');
  
  // Step 3: Check if URL exists in database
  console.log('[ProductURLInput] Step 3: Checking if URL exists in database...');
  if (props.urlExistsError) {
    console.log('[ProductURLInput] Validation failed: URL already exists in database');
    return;
  }
  console.log('[ProductURLInput] Step 3 passed: URL does not exist in database');
  
  // Update the model value if needed before proceeding
  if (inputValue !== props.modelValue) {
    emit('update:modelValue', inputValue);
  }
  
  // All validations passed, proceed with fetching data
  console.log('[ProductURLInput] All validations passed, proceeding to fetch data...');
  emit('getStarted', inputValue);
};


// Focus the input when component is mounted
const inputRef = ref(null);
onMounted(() => {
  // Use nextTick to ensure the DOM is fully rendered
 setTimeout(() => {
    if (inputRef.value) {
      inputRef.value.focus();
    }
 }, 100);
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
@keyframes wave {
  0%, 60%, 100% {
    transform: initial;
  }
  30% {
    transform: translateY(-5px);
  }
}
.dot-one {
  animation: wave 1.2s infinite;
  animation-delay: 0s;
}
.dot-two {
  animation: wave 1.2s infinite;
  animation-delay: 0.2s;
}
.dot-three {
  animation: wave 1.2s infinite;
  animation-delay: 0.4s;
}
</style>
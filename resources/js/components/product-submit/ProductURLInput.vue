<template>
  <!-- Fixed positioning to break out of parent layout flow and eliminate window scroll -->
  <!-- top-[3.7rem] matches the header height defined in top-bar.blade.php -->
  <div class="fixed inset-x-0 bottom-0 top-[3.7rem] z-0 bg-white">
    
    <!-- Right side background pattern - 50% of Viewport Width -->
    <div class="absolute top-0 right-0 w-1/2 h-full hidden md:block">
      <img src="/images/submission-pattern.png" alt="Background Pattern" class="w-full h-full object-cover">
    </div>

    <!-- Main Content Container - Aligned with site standard (max-w-7xl centered) -->
    <!-- h-full and overflow-y-auto allow the form to scroll internally if height is too small, without scrolling the whole page -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full relative z-10">
      <div class="flex flex-col md:flex-row h-full">
        <!-- Left Column: Form -->
        <!-- md:w-1/2 ensures it takes left half of the content area -->
        <div class="w-full md:w-1/2 h-full overflow-y-auto no-scrollbar pt-10 pb-12">
          <div class="max-w-md w-full space-y-8">
            <div>
              <h2 class="mt-6 text-3xl font-extrabold text-gray-800">Submit a product</h2>
              <p class="mt-2 text-base text-gray-600">
                Found great software to share? Or built something yourself? Perfect. Enter the URL below and we'll automatically fetch the details. Just follow the steps.
              </p>
            </div>
            <div class="mt-8 space-y-6">
              <div class="rounded-md shadow-sm">
                <div>
                  <label for="product-url" class="sr-only">Link to the product</label>
                  <div class="relative group">
                    <input id="product-url" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" type="url" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-md focus:outline-none focus:ring-rose-500 focus:border-rose-500 focus:z-10 sm:text-sm" placeholder="https://softwareontheweb.com">
                    <button v-if="modelValue" @click="$emit('clear')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                  </div>
                </div>
              </div>
              <div class="flex items-center">
                <button @click="$emit('getStarted')" :disabled="isLoading || isUrlInvalid || urlExistsError" class="group relative w-1/2 flex justify-center py-1.5 px-4 text-base
         font-semibold rounded-lg text-white bg-rose-500
                   hover:bg-rose-600 hover:shadow-sm
                   transition-colors duration-300
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 disabled:opacity-50">
                  <span>Get started</span>
                </button>
                <span v-if="isLoading" class="ml-4 text-sm text-gray-600">{{ loadingMessage }}<span class="dot-one">.</span><span class="dot-two">.</span><span class="dot-three">.</span></span>
              </div>
              
              <div class="mt-2">
                <transition name="fade">
                  <p v-if="urlExistsError" class="text-sm text-gray-700">
                    This URL already exists as
                    <a :href="`/product/${existingProduct.slug}`" target="_blank" class="underline font-semibold">"{{ existingProduct.name }}"</a>
                    . You cannot add the same product twice.
                  </p>
                </transition>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: String,
  isLoading: Boolean,
  isUrlInvalid: Boolean,
  urlExistsError: Boolean,
  existingProduct: Object,
});

defineEmits(['update:modelValue', 'getStarted', 'clear']);

const loadingMessages = [
  "Fetching data...",
  "Fetching meta description...",
  "Rewriting tagline for product details page...",
  "Analyzing page content...",
  "Getting logo...",
  "Finalizing details..."
];

const loadingMessage = ref(loadingMessages[0]);
let messageInterval = null;

watch(() => props.isLoading, (newValue) => {
  if (newValue) {
    let messageIndex = 0;
    loadingMessage.value = loadingMessages[messageIndex];
    messageInterval = setInterval(() => {
      messageIndex = (messageIndex + 1) % loadingMessages.length;
      loadingMessage.value = loadingMessages[messageIndex];
    }, 2000);
  } else {
    clearInterval(messageInterval);
    loadingMessage.value = loadingMessages[0];
  }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter, .fade-leave-to {
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
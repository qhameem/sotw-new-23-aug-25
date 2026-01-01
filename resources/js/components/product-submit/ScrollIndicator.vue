<template>
  <transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-4"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-4"
  >
    <div
      v-if="isVisible"
      class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 pointer-events-none"
    >
      <div 
        @click="scrollDown"
        class="bg-gray-800 text-white text-sm font-medium py-2 px-4 rounded-full shadow-lg flex items-center gap-2 animate-bounce cursor-pointer pointer-events-auto hover:bg-gray-700 transition-colors"
      >
        <span>Scroll for more</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isVisible = ref(false);

const scrollDown = () => {
  window.scrollBy({
    top: window.innerHeight * 0.7, // Scroll down by 70% of viewport height
    behavior: 'smooth'
  });
};

const checkScroll = () => {
  const scrollTop = window.scrollY || document.documentElement.scrollTop;
  const windowHeight = window.innerHeight;
  const documentHeight = document.documentElement.scrollHeight;

  // Show if we are not at the bottom (with some buffer) and the page is actually scrollable
  const isScrollable = documentHeight > windowHeight + 50; // 50px buffer
  const isAtBottom = scrollTop + windowHeight >= documentHeight - 100; // 100px buffer form bottom

  isVisible.value = isScrollable && !isAtBottom;
};

onMounted(() => {
  window.addEventListener('scroll', checkScroll);
  window.addEventListener('resize', checkScroll);
  // Initial check after a short delay to allow content to render
  setTimeout(checkScroll, 500);
  
  // Also check periodically in case content changes dynamically
  const intervalId = setInterval(checkScroll, 2000);
  
  onUnmounted(() => {
    window.removeEventListener('scroll', checkScroll);
    window.removeEventListener('resize', checkScroll);
    clearInterval(intervalId);
  });
});
</script>

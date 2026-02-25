<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden p-5">
    <div class="flex items-start gap-4 mb-4">
      <!-- Logo Placeholder/Preview -->
      <!-- Logo Placeholder/Preview -->
        <div class="relative group" @click="!logoPreview && !form.favicon ? triggerLogoUpload() : null">
            <div
                class="h-16 w-16 flex-shrink-0 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center overflow-hidden relative"
                :class="(logoPreview || form.favicon) ? 'cursor-pointer' : 'cursor-pointer'"
                @click="(logoPreview || form.favicon) ? triggerLogoUpload() : null"
            >
                 <img v-if="logoPreview || form.favicon" :src="logoPreview || form.favicon" alt="Project Logo" class="h-full w-full object-contain transition-opacity duration-200 group-hover:opacity-30">

                 <!-- Empty state: show restore icon if original favicon exists, else '+' -->
                 <template v-else>
                   <button
                     v-if="originalFavicon"
                     @click.stop="restoreLogo"
                     class="absolute inset-0 w-full h-full flex flex-col items-center justify-center text-gray-300 hover:text-emerald-500 transition-colors"
                     title="Restore original logo"
                   >
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                     </svg>
                     <span class="text-xs mt-0.5 font-medium">Restore</span>
                   </button>
                   <span v-else @click.stop="triggerLogoUpload" class="text-2xl text-gray-300 hover:text-sky-500 transition-colors">+</span>
                 </template>

                 <!-- Green Upload Overlay (Visible on Hover when logo exists) -->
                 <div v-if="logoPreview || form.favicon" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <div class="bg-emerald-500 text-white rounded-lg p-1.5 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                 </div>
            </div>

            <!-- Persistent Remove Button (Visible when logo exists) -->
            <button 
                v-if="logoPreview || form.favicon"
                @click.stop="removeLogo" 
                class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full p-0.5 shadow-sm hover:bg-rose-600 transition-colors z-10 border-2 border-white" 
                title="Remove Logo"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Hidden File Input -->
            <input 
                type="file" 
                ref="logoInput" 
                @change="handleLogoChange" 
                accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp,image/avif" 
                class="hidden"
                @click.stop
            >
        </div>
      <div>
        <h3 class="font-bold text-gray-900 text-lg leading-tight">{{ form.name || 'Project Name' }}</h3>
        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ form.tagline || 'Your catchy tagline will appear here.' }}</p>
      </div>
    </div>
    
    <!-- Screenshot Preview Mock (if available) -->
    <div class="aspect-video bg-gray-50 rounded-lg border border-gray-100 mb-4 flex items-center justify-center overflow-hidden relative group">
        <img v-if="galleryPreviews && galleryPreviews[0]" :src="galleryPreviews[0]" class="w-full h-full object-cover">
        <div v-else class="text-center p-4">
           <div class="w-12 h-8 bg-gray-200 rounded mx-auto mb-2"></div>
           <div class="h-2 w-20 bg-gray-200 rounded mx-auto mb-1"></div>
           <div class="h-2 w-12 bg-gray-200 rounded mx-auto"></div>
        </div>
    </div>
    
    <!-- Tags Preview -->
    <div class="flex flex-wrap gap-2">
         <!-- Mock tags based on selection -->
         <span v-for="(catId, index) in (form.categories || []).slice(0, 3)" :key="catId" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
            {{ allCategories.find(c => c.id === catId)?.name || 'Category' }}
         </span>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  logoPreview: {
    type: String,
    default: ''
  },
  galleryPreviews: {
    type: Array,
    default: () => []
  },
  allCategories: {
    type: Array,
    default: () => []
  },
  originalFavicon: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['update:logo', 'update:logoPreview', 'update:favicon', 'restore:favicon']);
const logoInput = ref(null);

function triggerLogoUpload() {
    logoInput.value.click();
}

function handleLogoChange(e) {
    const file = e.target.files[0];
    if (file) {
        emit('update:logo', file);
        
        const reader = new FileReader();
        reader.onload = (e) => {
             emit('update:logoPreview', e.target.result);
        };
        reader.readAsDataURL(file);
    }
}

function removeLogo() {
    emit('update:logo', null);
    emit('update:logoPreview', null);
    emit('update:favicon', null);
}

function restoreLogo() {
    emit('restore:favicon');
}
</script>

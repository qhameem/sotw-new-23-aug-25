<template>
  <div class="space-y-8 mt-4">
    
    <!-- Project Name -->
    <div id="field-name" :class="autofillLockClass('name')">
      <div class="mb-1 flex items-start justify-between gap-4">
        <div class="flex items-start gap-3">
          <label for="name" class="block text-xs font-bold text-gray-900">Project Name <span class="text-red-500">*</span></label>
          <span class="text-xs text-gray-400">{{ (modelValue.name || '').length }}/40</span>
        </div>
        <p v-if="validationErrors.name" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.name }}</p>
      </div>
      <div class="mb-2 text-[11px] text-gray-500">What is your product called? This name will be used across the site and in the product URL.</div>
      <input 
        ref="nameInput" 
        type="text" 
        id="name" 
        :value="modelValue.name" 
        @input="updateProductName($event.target.value)" 
        maxlength="40" 
        placeholder="e.g. Smooth Capture"
        class="block w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
        :class="{
          'opacity-50 pointer-events-none': loadingStates.name,
          '!border-red-400 !ring-red-100': validationErrors.name
        }"
      >
      <!-- Slug preview -->
      <div v-if="generatedSlug" class="mt-2 text-xs text-gray-500 flex items-center gap-1">
        <span class="text-gray-400">softwareontheweb.com/product/</span>
        <span class="font-medium text-gray-700">{{ generatedSlug }}</span>
      </div>
      <p v-if="extractionErrors.name" class="mt-1 text-xs text-red-500">{{ extractionErrors.name }}</p>
    </div>

    <!-- Tagline -->
     <div id="field-tagline" :class="autofillLockClass('tagline')">
        <div class="mb-1 flex items-start justify-between gap-4">
          <div class="flex items-start gap-3">
            <label for="tagline" class="block text-xs font-bold text-gray-900">Tagline <span class="text-red-500">*</span></label>
            <span class="text-xs text-gray-400">{{ (modelValue.tagline || '').length }}/140</span>
          </div>
          <p v-if="validationErrors.tagline" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.tagline }}</p>
        </div>
        <div class="mb-2 text-[11px] text-gray-500">Use one clear 140-character tagline. It will appear on both the list page and the product page.</div>
        <input
          type="text"
          id="tagline" 
          :value="modelValue.tagline" 
          @input="updateField('tagline', $event.target.value)" 
          maxlength="140" 
          placeholder="Short Product Hunt-style one-liner"
          class="block w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
          :class="{ '!border-red-400 !ring-red-100': validationErrors.tagline }"
        >
        <p v-if="extractionErrors.tagline" class="mt-1 text-xs text-red-500">{{ extractionErrors.tagline }}</p>
     </div>

    <!-- Description -->
    <div id="field-description" class="relative" :class="[autofillLockClass('description'), {'opacity-50 pointer-events-none': loadingStates.description}]">
        <div class="mb-1 flex items-start justify-between gap-4">
          <label class="block text-xs font-bold text-gray-900">Description <span class="text-red-500">*</span></label>
          <div class="flex items-center gap-3">
            <p v-if="validationErrors.description" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.description }}</p>
            <button
              v-if="showRewriteDescriptionButton"
              type="button"
              :disabled="loadingStates.description || !modelValue.link"
              @click="$emit('rewrite-description')"
              class="inline-flex items-center gap-1 rounded-md border border-sky-200 px-2.5 py-1 text-[11px] font-semibold text-sky-700 transition-colors hover:border-sky-300 hover:bg-sky-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400 disabled:hover:bg-transparent"
            >
              <svg v-if="loadingStates.description" class="h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ loadingStates.description ? 'Rewriting...' : 'Rewrite product description' }}
            </button>
            <div v-else-if="loadingStates.description" class="flex items-center text-xs text-sky-600">
               <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
               Generating...
            </div>
          </div>
        </div>
        <div class="mb-2 text-[11px] text-gray-500">What does your product do, who is it for, and why would someone choose it?</div>
        <div
          class="prose-editor-wrapper border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-sky-500 focus-within:border-transparent transition-all"
          :class="{ '!border-red-400 !ring-red-100': validationErrors.description }"
        >
           <WysiwygEditor :modelValue="modelValue.description" @update:modelValue="updateField('description', $event)" />
        </div>
        <p v-if="extractionErrors.description" class="mt-1 text-xs text-red-500">{{ extractionErrors.description }}</p>
    </div>

    <!-- Categories (Chip Selection) -->
    <div id="field-categories" :class="autofillLockClass('taxonomy')">
       <div class="mb-1 flex items-start justify-between gap-4">
          <div class="flex items-start gap-3">
            <label class="block text-xs font-bold text-gray-900">Categories <span class="text-red-500">*</span> <span class="text-gray-400 font-normal text-xs ml-1">(Max 3)</span></label>
            <div v-if="loadingStates.categories" class="animate-pulse h-2 w-20 rounded bg-gray-200"></div>
          </div>
          <p v-if="validationErrors.categories" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.categories }}</p>
       </div>
       <div v-if="modelValue.categories.length === 0 && (!modelValue.categories_custom || modelValue.categories_custom.length === 0)" class="mb-2 text-xs text-gray-500">What categories best describe your product? If you can't find a good match, add a custom category.</div>

       <!-- Category Search -->
       <div class="relative mb-3">
         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
           <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
           </svg>
         </div>
         <input 
           type="text" 
           v-model="categorySearch" 
           placeholder="Search categories..." 
           class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
           :class="{'pr-32': showAddCategoryButton, 'pr-8': !showAddCategoryButton && categorySearch.length >= 2}"
         >
         <button
           v-if="categorySearch.length >= 2 && !showAddCategoryButton"
           type="button"
           @click="categorySearch = ''"
           class="absolute inset-y-0 right-0 px-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
         >
           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
         </button>
         <button
           v-if="showAddCategoryButton"
           type="button"
           @click="addCustomCategoryFromSearch"
           class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
         >
           + Add "{{ categorySearch.trim() }}"
         </button>
       </div>
       
       <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
          <button 
            v-for="category in filteredCategories" 
            :key="category.id"
            type="button"
            @click="toggleCategory(category.id)"
            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
            :class="modelValue.categories.includes(category.id) 
              ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm' 
              : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
          >
            {{ category.name }}
            <svg v-if="modelValue.categories.includes(category.id)" class="ml-1.5 h-3 w-3 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
          <div v-if="filteredCategories.length === 0 && !showAddCategoryButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
             No categories found matching "{{ categorySearch }}"
          </div>
       </div>
       
       <!-- Display selected custom categories -->
       <div v-if="modelValue.categories_custom && modelValue.categories_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
         <span
           v-for="customCat in modelValue.categories_custom"
           :key="customCat.id"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
         >
           {{ customCat.name }} (pending)
           <button
             type="button"
             @click="removeCustomCategory(customCat.id)"
             class="ml-2 text-purple-500 hover:text-purple-700"
           >
             &times;
           </button>
         </span>
       </div>
    </div>

    <!-- Use Cases (Chip Selection) -->
    <div id="field-use-cases" :class="autofillLockClass('taxonomy')">
       <div class="mb-1 flex items-start justify-between gap-4">
          <label class="block text-xs font-bold text-gray-900">Use Cases <span class="text-red-500">*</span> <span class="text-gray-400 font-normal text-xs ml-1">(Min 1, max 3)</span></label>
          <p v-if="validationErrors.useCases" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.useCases }}</p>
       </div>
       <div v-if="modelValue.useCases.length === 0 && (!modelValue.useCases_custom || modelValue.useCases_custom.length === 0)" class="mb-2 text-xs text-gray-500">What do people use your product for? If you can't find a good match, add a custom use case.</div>

       <div class="relative mb-3">
         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
           <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
           </svg>
         </div>
         <input
           type="text"
           v-model="useCaseSearch"
           placeholder="Search use cases..."
           class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
           :class="{'pr-32': showAddUseCaseButton, 'pr-8': !showAddUseCaseButton && useCaseSearch.length >= 2}"
         >
         <button
           v-if="useCaseSearch.length >= 2 && !showAddUseCaseButton"
           type="button"
           @click="useCaseSearch = ''"
           class="absolute inset-y-0 right-0 px-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
         >
           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
         </button>
         <button
           v-if="showAddUseCaseButton"
           type="button"
           @click="addCustomUseCaseFromSearch"
           class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
         >
           + Add "{{ useCaseSearch.trim() }}"
         </button>
       </div>

       <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
          <button
            v-for="item in filteredUseCases"
            :key="item.id"
            type="button"
            @click="toggleUseCase(item.id)"
            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
            :class="modelValue.useCases.includes(item.id)
              ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm'
              : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
          >
            {{ item.name }}
             <span v-if="modelValue.useCases.includes(item.id)" class="ml-1.5 text-sky-600 font-bold">&times;</span>
          </button>
          <div v-if="filteredUseCases.length === 0 && !showAddUseCaseButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
             {{ useCaseSearch.trim() ? `No use cases found matching "${useCaseSearch}"` : 'No approved use cases available yet.' }}
          </div>
       </div>

       <div v-if="modelValue.useCases_custom && modelValue.useCases_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
         <span
           v-for="customUseCase in modelValue.useCases_custom"
           :key="customUseCase.id"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
         >
           {{ customUseCase.name }} (pending)
           <button
             type="button"
             @click="removeCustomUseCase(customUseCase.id)"
             class="ml-2 text-purple-500 hover:text-purple-700"
           >
             &times;
           </button>
         </span>
       </div>
       <p v-if="extractionErrors.useCases" class="mt-1 text-xs text-red-500">{{ extractionErrors.useCases }}</p>
    </div>

    <!-- Platform (Chip Selection) -->
    <div :class="autofillLockClass('taxonomy')">
       <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-bold text-gray-900">Platform <span class="text-gray-400 font-normal text-xs ml-1">(Optional)</span></label>
       </div>
       <div class="mb-2 text-xs text-gray-500">Where does your product run? Choose the platform your product is built for, or add a custom one if needed.</div>

       <div class="relative mb-3">
         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
           <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
           </svg>
         </div>
         <input
           type="text"
           v-model="platformSearch"
           placeholder="Search platforms..."
           class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
           :class="{'pr-32': showAddPlatformButton, 'pr-8': !showAddPlatformButton && platformSearch.length >= 2}"
         >
         <button
           v-if="platformSearch.length >= 2 && !showAddPlatformButton"
           type="button"
           @click="platformSearch = ''"
           class="absolute inset-y-0 right-0 px-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
         >
           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
         </button>
         <button
           v-if="showAddPlatformButton"
           type="button"
           @click="addCustomPlatformFromSearch"
           class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
         >
           + Add "{{ platformSearch.trim() }}"
         </button>
       </div>

       <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
          <button
            v-for="platform in filteredPlatforms"
            :key="platform.id"
            type="button"
            @click="togglePlatform(platform.id)"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
            :class="modelValue.platforms.includes(platform.id)
              ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm'
              : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
          >
            <template v-if="platformIconKey(platform.name) === 'android'">
              <svg
                class="h-4 w-4 flex-shrink-0"
                :class="modelValue.platforms.includes(platform.id) ? 'text-sky-800' : 'text-gray-700'"
                viewBox="0 0 256 256"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                aria-hidden="true"
              >
                <g>
                  <path d="M172,156a8,8,0,1,1-8-8A7.99993,7.99993,0,0,1,172,156Zm-80-8a8,8,0,1,0,8,8A7.99993,7.99993,0,0,0,92,148Zm144,20v24a12.01375,12.01375,0,0,1-12,12H32a12.01375,12.01375,0,0,1-12-12V169.12893a109.42633,109.42633,0,0,1,37.18213-82.29L29.17139,58.82863a4.00026,4.00026,0,0,1,5.65722-5.65722L63.41016,81.753a106.64706,106.64706,0,0,1,64.20849-21.75244C127.74561,60,127.876,60,128.00342,60a107.15753,107.15753,0,0,1,64.77392,21.56592l28.394-28.394a3.99992,3.99992,0,0,1,5.65722,5.65625L199.01953,86.63723q2.67152,2.33862,5.21485,4.8623A107.27637,107.27637,0,0,1,236,168Zm-8,0A99.99959,99.99959,0,0,0,128.00244,68c-.11914,0-.23682,0-.35644.00049C72.70117,68.19045,28,113.55666,28,169.12893V192a4.00458,4.00458,0,0,0,4,4H224a4.00458,4.00458,0,0,0,4-4Z" />
                </g>
              </svg>
            </template>
            <template v-else-if="platformIconKey(platform.name) === 'browser'">
              <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke-width="1.7"/>
                <path d="M3.5 9h17M7 7h.01M10 7h.01M13 7h.01" stroke-width="1.7" stroke-linecap="round"/>
              </svg>
            </template>
            <template v-else-if="platformIconKey(platform.name) === 'chrome-extension'">
              <svg
                class="h-3.5 w-3.5 flex-shrink-0"
                viewBox="0 0 1024 1024"
                xmlns="http://www.w3.org/2000/svg"
                xml:space="preserve"
                fill="currentColor"
                aria-hidden="true"
              >
                <g>
                  <path d="M938.67 512.01c0-44.59-6.82-87.6-19.54-128H682.67a212.372 212.372 0 0 1 42.67 128c.06 38.71-10.45 76.7-30.42 109.87l-182.91 316.8c235.65-.01 426.66-191.02 426.66-426.67z" />
                  <path d="M576.79 401.63a127.92 127.92 0 0 0-63.56-17.6c-22.36-.22-44.39 5.43-63.89 16.38s-35.79 26.82-47.25 46.02a128.005 128.005 0 0 0-2.16 127.44l1.24 2.13a127.906 127.906 0 0 0 46.36 46.61 127.907 127.907 0 0 0 63.38 17.44c22.29.2 44.24-5.43 63.68-16.33a127.94 127.94 0 0 0 47.16-45.79v-.01l1.11-1.92a127.984 127.984 0 0 0 .29-127.46 127.957 127.957 0 0 0-46.36-46.91z" />
                  <path d="M394.45 333.96A213.336 213.336 0 0 1 512 298.67h369.58A426.503 426.503 0 0 0 512 85.34a425.598 425.598 0 0 0-171.74 35.98 425.644 425.644 0 0 0-142.62 102.22l118.14 204.63a213.397 213.397 0 0 1 78.67-94.21zm117.56 604.72H512zm-97.25-236.73a213.284 213.284 0 0 1-89.54-86.81L142.48 298.6c-36.35 62.81-57.13 135.68-57.13 213.42 0 203.81 142.93 374.22 333.95 416.55h.04l118.19-204.71a213.315 213.315 0 0 1-122.77-21.91z" />
                </g>
              </svg>
            </template>
            <template v-else-if="platformIconKey(platform.name) === 'ios'">
              <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <rect x="7.5" y="3.5" width="9" height="17" rx="2.5" stroke-width="1.7"/>
                <path d="M11 6.5h2M11.75 17.5h.5" stroke-width="1.7" stroke-linecap="round"/>
              </svg>
            </template>
            <template v-else-if="platformIconKey(platform.name) === 'macos'">
              <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M16.37 12.47c.02 2.44 2.14 3.25 2.16 3.26-.02.06-.34 1.17-1.12 2.32-.68.99-1.38 1.98-2.49 2-.99.02-1.31-.58-2.44-.58-1.13 0-1.48.56-2.42.6-1 .04-1.77-1-2.45-1.98-1.39-2.01-2.46-5.69-1.03-8.18.71-1.23 1.97-2.02 3.34-2.04.98-.02 1.9.66 2.44.66.54 0 1.72-.82 2.9-.7.49.02 1.86.2 2.74 1.49-.07.04-1.64.96-1.63 2.85Zm-2.05-8.07c.57-.69.95-1.64.84-2.59-.82.03-1.82.54-2.41 1.23-.53.61-.99 1.58-.86 2.51.91.07 1.85-.47 2.43-1.15Z"/>
              </svg>
            </template>
            <template v-else-if="platformIconKey(platform.name) === 'windows'">
              <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M4.5 6.5 10.5 5.5v6H4.5v-5ZM13.5 5l6-1v7.5h-6V5ZM4.5 12.5h6v6l-6-1v-5ZM13.5 12.5h6V20l-6-1v-6.5Z" stroke-width="1.4" stroke-linejoin="round"/>
              </svg>
            </template>
            <template v-else>
              <svg class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M3.75 4.75A1.75 1.75 0 0 1 5.5 3h9A1.75 1.75 0 0 1 16.25 4.75v5.5A1.75 1.75 0 0 1 14.5 12h-9a1.75 1.75 0 0 1-1.75-1.75v-5.5ZM7.5 15.5h5M8.5 12v3.5M11.5 12v3.5" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </template>
            {{ platform.name }}
            <svg v-if="modelValue.platforms.includes(platform.id)" class="ml-1.5 h-3 w-3 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
          <div v-if="filteredPlatforms.length === 0 && !showAddPlatformButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
             No platforms found matching "{{ platformSearch }}"
          </div>
       </div>

       <div v-if="modelValue.platforms_custom && modelValue.platforms_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
         <span
           v-for="customPlatform in modelValue.platforms_custom"
           :key="customPlatform.id"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
         >
           {{ customPlatform.name }} (pending)
           <button
             type="button"
             @click="removeCustomPlatform(customPlatform.id)"
             class="ml-2 text-purple-500 hover:text-purple-700"
           >
             &times;
           </button>
         </span>
       </div>
    </div>



    <!-- Best For / Tags (Chip Selection) -->
    <div id="field-pricing" :class="autofillLockClass('taxonomy')">
       <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-bold text-gray-900">Tags / Best For <span class="text-gray-400 font-normal text-xs ml-1">(Max 5)</span></label>
       </div>
       <div class="mb-2 text-xs text-gray-500">Who is your product best for? Add tags that describe the audience, role, or situation it fits best.</div>

       <!-- Tag Search -->
       <div class="relative mb-3">
         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
           <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
           </svg>
         </div>
         <input
           type="text"
           v-model="tagSearch"
           placeholder="Search tags..."
           class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
           :class="{'pr-32': showAddTagButton, 'pr-8': !showAddTagButton && tagSearch.length >= 2}"
         >
         <button
           v-if="tagSearch.length >= 2 && !showAddTagButton"
           type="button"
           @click="tagSearch = ''"
           class="absolute inset-y-0 right-0 px-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
         >
           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
         </button>
         <button
           v-if="showAddTagButton"
           type="button"
           @click="addCustomTagFromSearch"
           class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
         >
           + Add "{{ tagSearch.trim() }}"
         </button>
       </div>
       
       <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
          <button
            v-for="item in filteredBestFor"
            :key="item.id"
            type="button"
            @click="toggleBestFor(item.id)"
            class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
            :class="modelValue.bestFor.includes(item.id)
              ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm'
              : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
          >
            {{ item.name }}
             <span v-if="modelValue.bestFor.includes(item.id)" class="ml-1.5 text-sky-600 font-bold">&times;</span>
          </button>
          <div v-if="filteredBestFor.length === 0 && !showAddTagButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
             No tags found matching "{{ tagSearch }}"
          </div>
       </div>
       
       <!-- Display selected custom tags -->
       <div v-if="modelValue.bestFor_custom && modelValue.bestFor_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
         <span
           v-for="customTag in modelValue.bestFor_custom"
           :key="customTag.id"
           class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
         >
           {{ customTag.name }} (pending)
           <button
             type="button"
             @click="removeCustomTag(customTag.id)"
             class="ml-2 text-purple-500 hover:text-purple-700"
           >
             &times;
           </button>
         </span>
       </div>
    </div>

    <!-- Pricing (Cards) -->
    <div :class="autofillLockClass('taxonomy')">
       <div class="mb-1 flex items-start justify-between gap-4">
         <label class="block text-xs font-bold text-gray-900">Pricing <span class="text-red-500">*</span></label>
         <p v-if="validationErrors.pricing" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.pricing }}</p>
       </div>
       <div class="mb-2 text-xs text-gray-500">How do people pay for your product? Select the pricing models that apply.</div>
       <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div 
            v-for="price in allPricing" 
            :key="price.id"
            @click="togglePricing(price.id)"
            class="cursor-pointer relative rounded-full border px-4 py-2 transition-all duration-200 hover:shadow-md flex flex-col justify-between h-full"
            :class="modelValue.pricing.includes(price.id)
              ? 'bg-sky-50 border-sky-500'
              : 'bg-white border-gray-200 hover:border-sky-300'"
          >
             <div class="flex items-start justify-between">
                <span class="font-medium text-xs text-gray-700">{{ price.name }}</span>
                <div class="h-4 w-4 rounded-full border flex items-center justify-center"
                     :class="modelValue.pricing.includes(price.id) ? 'bg-sky-500 border-sky-500' : 'border-gray-300'"
                >
                   <svg v-if="modelValue.pricing.includes(price.id)" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                   </svg>
                </div>
             </div>
             <!-- Optional description for pricing if we had it -->
             <!-- <p class="text-xs text-gray-500">Description here...</p> -->
          </div>
       </div>
    </div>

    <!-- Pricing Page URL -->
    <div id="field-pricing-page-url" :class="autofillLockClass('links')">
      <div class="mb-1 flex items-start justify-between gap-4">
        <label for="pricing_page_url" class="block text-xs font-bold text-gray-900">Pricing Page URL <span class="text-gray-400 font-normal text-xs ml-1">(Optional)</span></label>
        <p v-if="validationErrors.pricing_page_url" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.pricing_page_url }}</p>
      </div>
      <div class="mb-2 text-[11px] text-gray-500">Do you have a pricing page? Add the direct link so visitors can compare plans faster.</div>
      <input 
        type="url" 
        id="pricing_page_url" 
        :value="modelValue.pricing_page_url || ''" 
        @input="updateField('pricing_page_url', $event.target.value)" 
        placeholder="https://example.com/pricing"
        class="block w-full px-4 py-3 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
        :class="{ '!border-red-400 !ring-red-100': validationErrors.pricing_page_url }"
      >
    </div>

    <!-- Social Links -->
    <div class="pt-4 border-t border-gray-100" :class="autofillLockClass('links')">
        <label class="block text-xs font-bold text-gray-900 mb-4">Social Links</label>
        <div class="grid grid-cols-1 gap-6">
            <div>
                 <label class="block text-xs font-bold text-gray-900 mb-1">Twitter / X</label>
                 <div class="mb-2 text-[11px] text-gray-500">Add your main product or founder profile so people can find updates and reach out.</div>
                 <input 
                    type="url" 
                    :value="modelValue.x_account || ''"
                    @input="updateField('x_account', $event.target.value)"
                    placeholder="https://x.com/username"
                    class="block w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
                 >
            </div>
            <!-- Maker Links / GitHub etc could go here if we expand the model -->
        </div>
         
         <!-- Dynamic Maker Links (Existing functionality preserved but styled) -->
         <div id="field-maker-links" class="mt-4">
             <div class="mb-1 flex items-start justify-between gap-4">
                 <label class="block text-xs font-bold text-gray-900">Other Profile / Store Links</label>
                 <div class="flex items-center gap-3">
                   <p v-if="validationErrors.maker_links" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.maker_links }}</p>
                   <button type="button" @click="addMoreLink" class="flex items-center text-xs font-bold text-sky-600 hover:text-sky-700">
                      <svg class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                      Add Link
                   </button>
                 </div>
             </div>
             <div class="mb-2 text-xs text-gray-500">Use profile, social, app store, or browser extension links like GitHub, LinkedIn, App Store, Play Store, or Chrome Web Store.</div>
             <div class="space-y-2">
                 <div v-for="(link, index) in makerLinks" :key="index" class="flex items-center gap-2">
                    <input
                      type="url"
                      :value="link"
                      @input="updateMakerLink(index, $event.target.value)"
                      placeholder="https://github.com/username"
                      class="block flex-1 px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-xs"
                    >
                    <button type="button" @click="removeLink(index)" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1 -1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" /></svg>
                    </button>
                 </div>
             </div>
         </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import WysiwygEditor from '../WysiwygEditor.vue';
import { getTabProgress } from '../../services/productFormService';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
    default: () => ({
      name: '',
      tagline: '',
      tagline_detailed: '',
      description: '',
      categories: [],
      categories_custom: [], // Custom categories
      useCases: [],
      useCases_custom: [], // Custom use cases
      platforms: [],
      platforms_custom: [],
      bestFor: [],
      bestFor_custom: [], // Custom bestFor
      pricing: [],
      pricing_page_url: '',
      link: '',
      maker_links: [],
      x_account: ''
    })
  },
  allCategories: { type: Array, default: () => [] },
  allUseCases: { type: Array, default: () => [] },
  allPlatforms: { type: Array, default: () => [] },
  allBestFor: { type: Array, default: () => [] },
  allPricing: { type: Array, default: () => [] },
  loadingStates: { type: Object, default: () => ({}) },
  extractionErrors: { type: Object, default: () => ({}) },
  validationErrors: {
    type: Object,
    default: () => ({}),
  },
  autofillReveal: {
    type: Object,
    default: () => ({
      active: false,
      unlocked: {
        name: false,
        tagline: false,
        description: false,
        taxonomy: false,
        links: false
      }
    })
  },
  isAdmin: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue', 'rewrite-description']);

onMounted(() => {
  console.log('[ProductDetailsForm] Mounted. Initial modelValue:', props.modelValue);
  if (props.allCategories.length > 0) {
     console.log('[ProductDetailsForm] First category ID type:', typeof props.allCategories[0].id, props.allCategories[0].id);
  }
});

watch(() => props.modelValue, (newVal) => {
  console.log('[ProductDetailsForm] modelValue updated:', newVal);
  if (newVal.categories.length > 0) {
      console.log('[ProductDetailsForm] modelValue.categories[0] type:', typeof newVal.categories[0], newVal.categories[0]);
  }
}, { deep: true });

// Local refs
const nameInput = ref(null);
const makerLinks = ref(props.modelValue.maker_links?.length ? [...props.modelValue.maker_links] : ['']);
const categorySearch = ref('');
const useCaseSearch = ref('');
const platformSearch = ref('');
const tagSearch = ref('');
const techStackSearch = ref(''); // For tech stack search

const isAutofillLocked = (group) => (
  props.autofillReveal?.active === true
  && props.autofillReveal?.unlocked?.[group] !== true
);

const autofillLockClass = (group) => (
  isAutofillLocked(group) ? 'autofill-locked-group' : ''
);

// Computed: show "Add as custom" button when search text has no exact match
const showAddCategoryButton = computed(() => {
  const search = categorySearch.value.trim();
  if (!search) return false;
  // Don't show if already added as custom
  if (props.modelValue.categories_custom?.some(c => c.name.toLowerCase() === search.toLowerCase())) return false;
  // Don't show if limit reached
  if ((props.modelValue.categories_custom?.length || 0) >= 3) return false;
  // Show if no existing category matches exactly
  return !props.allCategories?.some(cat => cat.name.toLowerCase() === search.toLowerCase());
});

const showAddTagButton = computed(() => {
  const search = tagSearch.value.trim();
  if (!search) return false;
  if (props.modelValue.bestFor_custom?.some(t => t.name.toLowerCase() === search.toLowerCase())) return false;
  if ((props.modelValue.bestFor_custom?.length || 0) >= 5) return false;
  return !props.allBestFor?.some(item => item.name.toLowerCase() === search.toLowerCase());
});

const showAddUseCaseButton = computed(() => {
  const search = useCaseSearch.value.trim();
  if (!search) return false;
  if (props.modelValue.useCases_custom?.some(item => item.name.toLowerCase() === search.toLowerCase())) return false;
  if ((props.modelValue.useCases_custom?.length || 0) >= 3) return false;
  return !props.allUseCases?.some(item => item.name.toLowerCase() === search.toLowerCase());
});

const showAddPlatformButton = computed(() => {
  const search = platformSearch.value.trim();
  if (!search) return false;
  if (props.modelValue.platforms_custom?.some(p => p.name.toLowerCase() === search.toLowerCase())) return false;
  if ((props.modelValue.platforms_custom?.length || 0) >= 3) return false;
  return !props.allPlatforms?.some(item => item.name.toLowerCase() === search.toLowerCase());
});

// Computed filtered categories
const filteredCategories = computed(() => {
  if (!props.allCategories) return [];
  
  const search = categorySearch.value.toLowerCase().trim();
  
  // Filter out already selected custom categories
  const existingCustomCategories = props.modelValue.categories_custom.map(c => c.name.toLowerCase());
  
  // Sort and filter: Selected items should be boosted but also match search
  return props.allCategories
    .filter(cat => cat.name.toLowerCase().includes(search) && !existingCustomCategories.includes(cat.name.toLowerCase()))
    .sort((a, b) => {
      const aSelected = props.modelValue.categories.includes(a.id);
      const bSelected = props.modelValue.categories.includes(b.id);
      if (aSelected && !bSelected) return -1;
      if (!aSelected && bSelected) return 1;
      return a.name.localeCompare(b.name);
    });
});

// Computed filtered tags
const filteredBestFor = computed(() => {
  if (!props.allBestFor) return [];
  
  const search = tagSearch.value.toLowerCase().trim();
  
  // Filter out already selected custom tags
  const existingCustomTags = props.modelValue.bestFor_custom.map(t => t.name.toLowerCase());
  
  // Sort and filter: Selected items should be boosted but also match search
  return props.allBestFor
    .filter(item => item.name.toLowerCase().includes(search) && !existingCustomTags.includes(item.name.toLowerCase()))
    .sort((a, b) => {
      const aSelected = props.modelValue.bestFor.includes(a.id);
      const bSelected = props.modelValue.bestFor.includes(b.id);
      if (aSelected && !bSelected) return -1;
      if (!aSelected && bSelected) return 1;
      return a.name.localeCompare(b.name);
    });
});

const filteredUseCases = computed(() => {
  if (!props.allUseCases) return [];

  const search = useCaseSearch.value.toLowerCase().trim();
  const existingCustomUseCases = (props.modelValue.useCases_custom || []).map(item => item.name.toLowerCase());

  return props.allUseCases
    .filter(item => item.name.toLowerCase().includes(search) && !existingCustomUseCases.includes(item.name.toLowerCase()))
    .sort((a, b) => {
      const aSelected = props.modelValue.useCases.includes(a.id);
      const bSelected = props.modelValue.useCases.includes(b.id);
      if (aSelected && !bSelected) return -1;
      if (!aSelected && bSelected) return 1;
      return a.name.localeCompare(b.name);
    });
});

const filteredPlatforms = computed(() => {
  if (!props.allPlatforms) return [];

  const search = platformSearch.value.toLowerCase().trim();
  const existingCustomPlatforms = (props.modelValue.platforms_custom || []).map((item) => item.name.toLowerCase());

  return props.allPlatforms
    .filter((item) => item.name.toLowerCase().includes(search) && !existingCustomPlatforms.includes(item.name.toLowerCase()))
    .sort((a, b) => {
      const aSelected = props.modelValue.platforms.includes(a.id);
      const bSelected = props.modelValue.platforms.includes(b.id);
      if (aSelected && !bSelected) return -1;
      if (!aSelected && bSelected) return 1;
      return a.name.localeCompare(b.name);
    });
});

function platformIconKey(name) {
  const normalized = String(name || '').trim().toLowerCase();

  if (normalized === 'android') return 'android';
  if (normalized === 'browser' || normalized === 'web' || normalized === 'web app') return 'browser';
  if (normalized === 'chrome extension' || normalized === 'browser extension') return 'chrome-extension';
  if (normalized === 'ios' || normalized === 'iphone' || normalized === 'ipad') return 'ios';
  if (normalized === 'macos' || normalized === 'mac' || normalized === 'mac app') return 'macos';
  if (normalized === 'windows') return 'windows';

  return 'default';
}

// Sync makerLinks with props
watch(() => props.modelValue.maker_links, (newVal) => {
  if (JSON.stringify(newVal) !== JSON.stringify(makerLinks.value.filter(l => l.trim() !== ''))) {
    makerLinks.value = newVal?.length ? [...newVal] : (makerLinks.value.length ? makerLinks.value : ['']);
  }
}, { deep: true });

// Sync local makerLinks to model
watch(makerLinks, (newVal) => {
  const filtered = newVal.filter(link => link.trim() !== '');
  if (JSON.stringify(filtered) !== JSON.stringify(props.modelValue.maker_links)) {
    updateField('maker_links', filtered);
  }
}, { deep: true });


const generatedSlug = computed(() => {
  return props.modelValue.name ? generateSlug(props.modelValue.name) : '';
});

const showRewriteDescriptionButton = computed(() => props.isAdmin && !!props.modelValue.id);

function generateSlug(text) {
  return text.toString().toLowerCase().trim()
    .replace(/\s+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-');
}

function updateProductName(value) {
  emit('update:modelValue', {
    ...props.modelValue,
    name: value,
    slug: generateSlug(value)
 });
}

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}

// Chip Logic for Categories
function toggleCategory(id) {
    const current = [...props.modelValue.categories];
    const index = current.indexOf(id);
    if (index === -1) {
        if (current.length >= 3) return; // Max 3 limit
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('categories', current);
    categorySearch.value = ''; // Clear search after selection
}

// Checkbox/Chip Logic for Best For (Tags)
function toggleBestFor(id) {
    const current = [...props.modelValue.bestFor];
    const index = current.indexOf(id);
    if (index === -1) {
        if (current.length >= 5) return; // Max 5 limit
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('bestFor', current);
    tagSearch.value = ''; // Clear search after selection
}

function toggleUseCase(id) {
    const current = [...props.modelValue.useCases];
    const index = current.indexOf(id);
    if (index === -1) {
        if (current.length >= 3) return;
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('useCases', current);
    useCaseSearch.value = '';
}

function togglePlatform(id) {
    const current = [...props.modelValue.platforms];
    const index = current.indexOf(id);
    if (index === -1) {
        if (current.length >= 3) return;
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('platforms', current);
    platformSearch.value = '';
}

// Card Logic for Pricing
function togglePricing(id) {
    const current = [...props.modelValue.pricing];
    const index = current.indexOf(id);
    if (index === -1) {
        current.push(id);
    } else {
        current.splice(index, 1);
    }
    updateField('pricing', current);
}

function addMoreLink() {
   makerLinks.value.push('');
}
function removeLink(index) {
   makerLinks.value.splice(index, 1);
}
function updateMakerLink(index, value) {
   makerLinks.value[index] = value;
}

// Functions to handle custom categories (triggered from search inline button)
function addCustomCategoryFromSearch() {
  const name = categorySearch.value.trim();
  if (!name) return;
  if (props.modelValue.categories_custom?.length >= 3) return;
  
  const newCustomCategory = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };
  
  const updatedCustomCategories = [...(props.modelValue.categories_custom || []), newCustomCategory];
  emit('update:modelValue', { ...props.modelValue, categories_custom: updatedCustomCategories });
  categorySearch.value = '';
}

function removeCustomCategory(customCategoryId) {
  const updatedCustomCategories = props.modelValue.categories_custom.filter(cat => cat.id !== customCategoryId);
  emit('update:modelValue', { ...props.modelValue, categories_custom: updatedCustomCategories });
}

function addCustomUseCaseFromSearch() {
  const name = useCaseSearch.value.trim();
  if (!name) return;
  if ((props.modelValue.useCases_custom?.length || 0) >= 3) return;

  const newCustomUseCase = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };

  const updatedCustomUseCases = [...(props.modelValue.useCases_custom || []), newCustomUseCase];
  emit('update:modelValue', { ...props.modelValue, useCases_custom: updatedCustomUseCases });
  useCaseSearch.value = '';
}

function removeCustomUseCase(customUseCaseId) {
  const updatedCustomUseCases = (props.modelValue.useCases_custom || []).filter(item => item.id !== customUseCaseId);
  emit('update:modelValue', { ...props.modelValue, useCases_custom: updatedCustomUseCases });
}

function addCustomPlatformFromSearch() {
  const name = platformSearch.value.trim();
  if (!name) return;
  if ((props.modelValue.platforms_custom?.length || 0) >= 3) return;

  const newCustomPlatform = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };

  const updatedCustomPlatforms = [...(props.modelValue.platforms_custom || []), newCustomPlatform];
  emit('update:modelValue', { ...props.modelValue, platforms_custom: updatedCustomPlatforms });
  platformSearch.value = '';
}

function removeCustomPlatform(customPlatformId) {
  const updatedCustomPlatforms = (props.modelValue.platforms_custom || []).filter(platform => platform.id !== customPlatformId);
  emit('update:modelValue', { ...props.modelValue, platforms_custom: updatedCustomPlatforms });
}

// Functions to handle custom tags/bestFor (triggered from search inline button)
function addCustomTagFromSearch() {
  const name = tagSearch.value.trim();
  if (!name) return;
  if ((props.modelValue.bestFor_custom?.length || 0) >= 5) return;
  
  const newCustomTag = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };
  
  const updatedCustomTags = [...(props.modelValue.bestFor_custom || []), newCustomTag];
  emit('update:modelValue', { ...props.modelValue, bestFor_custom: updatedCustomTags });
  tagSearch.value = '';
}

function removeCustomTag(customTagId) {
  const updatedCustomTags = props.modelValue.bestFor_custom.filter(tag => tag.id !== customTagId);
  emit('update:modelValue', { ...props.modelValue, bestFor_custom: updatedCustomTags });
}

// Function to handle custom tech stack
function addCustomTechStack() {
  if (!customTechStackInput.value.trim()) return;
  
  // Check if already added as custom tech stack
  const alreadyAdded = props.modelValue.techStacks_custom?.some(ts =>
    ts.name.toLowerCase() === customTechStackInput.value.trim().toLowerCase()
  ) || false;
  
  if (alreadyAdded) {
    alert(`Tech stack "${customTechStackInput.value}" is already added as custom tech stack`);
    return;
  }
  
  // Check if we already have 3 custom tech stacks
  const currentCustomTechStacks = props.modelValue.techStacks_custom || [];
  if (currentCustomTechStacks.length >= 3) {
    alert('You can only add up to 3 custom tech stacks');
    return;
  }
  
  const newCustomTechStack = {
    id: `custom-${Date.now()}`, // Temporary ID
    name: customTechStackInput.value.trim(),
    is_custom: true
  };
  
  const updatedCustomTechStacks = [...currentCustomTechStacks, newCustomTechStack];
  emit('update:modelValue', { ...props.modelValue, techStacks_custom: updatedCustomTechStacks });
  
  customTechStackInput.value = '';
}

function removeCustomTechStack(customTechStackId) {
  const currentCustomTechStacks = props.modelValue.techStacks_custom || [];
  const updatedCustomTechStacks = currentCustomTechStacks.filter(ts => ts.id !== customTechStackId);
  emit('update:modelValue', { ...props.modelValue, techStacks_custom: updatedCustomTechStacks });
}

</script>

<style scoped>
.autofill-locked-group {
  filter: blur(1.25px);
  opacity: 0.58;
  pointer-events: none;
  user-select: none;
}
</style>

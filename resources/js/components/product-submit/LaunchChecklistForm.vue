<template>
  <div>
    <div class="space-y-6">
      <!-- Back button at top removed for Single Step Form -->
      <div class="mb-2">
        
      </div>
      
      <!-- Extras Section -->
      <section>
        <div class="space-y-6">
          <!-- Tech Stack Section -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <h4 class="text-xs font-bold text-gray-900">Tech Stack <span class="text-gray-400 font-normal text-xs ml-1">(Max 5)</span></h4>
            </div>
            <div class="mb-2 text-[11px] text-gray-500">Which technologies were used to build your product?</div>

            <!-- Tech Stack Search -->
            <div class="relative mb-3">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                type="text"
                v-model="techSearch"
                placeholder="Search technologies..."
                class="block w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"
                :class="{'pr-36': showAddTechStackButton}"
              >
              <button
                v-if="showAddTechStackButton"
                type="button"
                @click="addCustomTechStackFromSearch"
                class="absolute inset-y-0 right-0 px-3 flex items-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors"
              >
                + Add "{{ techSearch.trim() }}"
              </button>
            </div>

            <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto p-1 custom-scrollbar">
              <button
                v-for="tech in filteredTechStacks"
                :key="tech.id"
                type="button"
                @click="toggleTechStack(tech.id)"
                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-200"
                :class="modelValue.tech_stack && modelValue.tech_stack.includes(tech.id)
                  ? 'bg-sky-50 border-sky-500 text-sky-700 shadow-sm'
                  : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
              >
                {{ tech.name }}
                <svg v-if="modelValue.tech_stack && modelValue.tech_stack.includes(tech.id)" class="ml-1.5 h-3 w-3 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
              <div v-if="filteredTechStacks.length === 0 && !showAddTechStackButton" class="w-full py-4 text-center text-xs text-gray-400 italic">
                No technologies found matching "{{ techSearch }}"
              </div>
            </div>
            
            <!-- Display selected custom tech stacks -->
            <div v-if="modelValue.tech_stack_custom && modelValue.tech_stack_custom.length > 0" class="flex flex-wrap gap-2 mt-2">
              <span
                v-for="customTech in modelValue.tech_stack_custom"
                :key="customTech.id"
                class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-purple-50 border border-purple-200 text-purple-700"
              >
                {{ customTech.name }} (pending)
                <button
                  type="button"
                  @click="removeCustomTechStack(customTech.id)"
                  class="ml-2 text-purple-500 hover:text-purple-700"
                >
                  &times;
                </button>
              </span>
            </div>
          </div>
          
          <!-- Sell Product Option -->
          <div>
            <h4 class="text-md font-medium text-gray-700 mb-3">Product Sale</h4>
            <div class="flex items-center">
              <input
                type="checkbox"
                id="sell-product"
                :checked="modelValue.sell_product || false"
                @change="updateField('sell_product', $event.target.checked)"
                class="h-4 w-4 text-rose-600 border-gray-300 rounded focus:ring-sky-400"
              >
              <label for="sell-product" class="ml-2 block text-sm text-gray-900">I am looking to sell this product</label>
            </div>
            
            <!-- Asking Price Input (shown only if sell_product is true) -->
            <div v-if="modelValue.sell_product" class="mt-3 ml-6">
              <label for="asking-price" class="block text-sm font-semibold text-gray-700 mb-2">Asking Price (USD)</label>
              <input
                type="number"
                id="asking-price"
                :value="modelValue.asking_price || ''"
                @input="updateField('asking_price', $event.target.value)"
                placeholder="Enter price in USD"
                min="0"
                step="0.01"
                class="mt-1 block w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
              >
            </div>
          </div>
        </div>
      </section>
      

      
      <!-- Horizontal separator -->
      <hr class="border-t border-gray-200 my-6">
      
      <!-- Pricing Options / Save Button -->
      <section>
        <!-- Show save button only when editing an existing product (has ID) -->
        <div v-if="!!modelValue.id && !isAdmin" class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Save Changes</h3>
          <p class="text-sm text-gray-600 mb-6">You can save your edits directly without selecting a pricing option.</p>
          <div class="flex flex-col items-start gap-4">
            <div v-if="!isAllRequiredFilled" class="text-sm text-amber-600 font-medium">
              Note: Some required fields are missing, but you can still save.
            </div>
            <button
              type="button"
              @click="$emit('submit')"
              :disabled="isLoading"
              :class="{
                'cursor-wait': isLoading,
                'hover:bg-rose-700': !isLoading
              }"
              class="relative inline-flex min-h-12 items-center justify-center rounded-lg bg-rose-600 px-8 py-3 text-sm font-bold text-white shadow-md transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
            >
              <span
                class="whitespace-nowrap transition-opacity duration-150"
                :class="isLoading ? 'opacity-0' : 'opacity-100'"
              >
                Save All Changes
              </span>
              <span
                v-if="isLoading"
                class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current"
                aria-live="polite"
              >
                <span class="flex items-center gap-1.5" aria-hidden="true">
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.3s]"></span>
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.15s]"></span>
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                </span>
                <span>Saving</span>
              </span>
            </button>
          </div>
        </div>
        
        <!-- Show pricing options only when creating a new product (no ID) -->
        <div v-else-if="!isAdmin">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Submission</h3>
          <div v-if="progress.completed < progress.total" class="text-xs font-semibold text-gray-400 mb-4 transition-all duration-300">
            {{ progress.completed }} of {{ progress.total }} total required fields filled
          </div>
          <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6">
              <div class="space-y-4">
                <div>
                  <div class="flex items-center gap-3">
                    <h4 class="text-2xl font-semibold text-gray-900">Free Submission</h4>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">$0</span>
                  </div>
                  <p class="mt-3 text-sm text-gray-700">
                    Current queue time ~10 weeks or <strong>add our badge to skip the wait</strong>
                  </p>
                </div>

                <ul class="space-y-2 text-sm text-gray-600">
                  <li v-for="(feature, index) in freeLaunchFeatures" :key="index" class="flex items-start">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="ml-2">{{ feature }}</span>
                  </li>
                </ul>
              </div>

              <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5">
                <div class="flex items-start gap-3">
                  <input
                    id="badge-opt-in"
                    type="checkbox"
                    :checked="wantsBadgeLaunch"
                    @change="toggleBadgeLaunch($event.target.checked)"
                    class="mt-1 h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                  >
                  <div class="min-w-0 flex-1">
                    <label for="badge-opt-in" class="text-sm font-semibold text-gray-900">
                      Add our badge to skip the wait
                    </label>
                    <p class="mt-1 text-sm text-gray-600">
                      If your badge is verified, you can choose a launch week and we’ll publish on that week’s Monday.
                    </p>
                    <a
                      href="/get-the-badge"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="mt-3 inline-flex items-center text-sm font-medium text-emerald-700 hover:text-emerald-800"
                    >
                      Open badge instructions
                    </a>
                  </div>
                </div>

                <div v-if="wantsBadgeLaunch" class="mt-5 space-y-5 border-t border-emerald-200 pt-5">
                  <div v-if="badgeSnippet" class="rounded-xl border border-emerald-200 bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                      <p class="text-sm font-semibold text-gray-900">Badge code</p>
                      <button
                        type="button"
                        @click="copyBadgeSnippet"
                        class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                      >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-8-4h8M8 8V6a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2h-2m-4 0H6a2 2 0 01-2-2V8a2 2 0 012-2h8a2 2 0 012 2v10z" />
                        </svg>
                        <span>{{ hasCopiedBadgeSnippet ? 'Copied' : 'Copy code' }}</span>
                      </button>
                    </div>
                    <pre class="mt-3 overflow-x-auto whitespace-pre-wrap break-all rounded-lg bg-gray-950 px-4 py-3 text-xs text-emerald-100">{{ badgeSnippet }}</pre>
                    <div class="mt-3 space-y-2 text-xs text-gray-600">
                      <p v-if="hasCopiedBadgeSnippet" class="font-semibold text-emerald-700">
                        Badge code copied. Paste it onto your homepage, then verify the homepage URL below.
                      </p>
                      <p>
                        Paste this on your homepage. The easiest placements are inside your site footer, inside your header, or in an "As Seen On" section.
                      </p>
                      <p class="font-medium text-gray-700">
                        Recommended: add it to your homepage footer so it stays visible and easy for us to verify.
                      </p>
                    </div>
                  </div>

                  <div>
                    <div class="mb-2 flex items-start justify-between gap-4">
                      <label for="badge-placement-url" class="block text-sm font-semibold text-gray-700">
                        Badge page URL
                      </label>
                      <div class="flex flex-col items-end gap-1">
                        <p v-if="validationErrors.badge_placement_url" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.badge_placement_url }}</p>
                        <p v-if="validationErrors.badge_verified" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.badge_verified }}</p>
                      </div>
                    </div>
                    <div id="field-badge-placement-url" class="flex flex-col gap-3 md:flex-row">
                      <input
                        id="badge-placement-url"
                        type="url"
                        :value="modelValue.badge_placement_url || ''"
                        @input="handleBadgeUrlInput($event.target.value)"
                        placeholder="https://your-site.com/"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 placeholder-gray-400 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                        :class="{ '!border-red-400 !ring-red-100': validationErrors.badge_placement_url }"
                      >
                      <button
                        id="field-badge-verified"
                        type="button"
                        @click="verifyBadgePlacement"
                        :disabled="isVerifyingBadge || !badgePlacementUrlReady"
                        :class="{
                          'cursor-wait': isVerifyingBadge,
                          'opacity-50 cursor-not-allowed': !badgePlacementUrlReady && !isVerifyingBadge,
                          'hover:bg-emerald-600': badgePlacementUrlReady && !isVerifyingBadge
                        }"
                        class="relative inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                      >
                        <span
                          class="whitespace-nowrap transition-opacity duration-150"
                          :class="isVerifyingBadge ? 'opacity-0' : 'opacity-100'"
                        >
                          Verify Badge
                        </span>
                        <span
                          v-if="isVerifyingBadge"
                          class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current"
                          aria-live="polite"
                        >
                          <span class="flex items-center gap-1.5" aria-hidden="true">
                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.3s]"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.15s]"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                          </span>
                          <span>Verifying</span>
                        </span>
                      </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                      Put the badge on your homepage, then enter your homepage URL here so we can verify it.
                    </p>
                  </div>

                  <div
                    v-if="badgeVerificationMessage"
                    :class="badgeVerificationSuccess ? 'border-emerald-200 bg-emerald-100 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800'"
                    class="rounded-xl border px-4 py-3 text-sm"
                  >
                    {{ badgeVerificationMessage }}
                  </div>

                  <div id="field-badge-week-start">
                    <div class="mb-2 flex items-start justify-between gap-4">
                      <label for="badge-week-start" class="block text-sm font-semibold text-gray-700">
                        Launch week
                      </label>
                      <p v-if="validationErrors.badge_week_start" class="inline-flex max-w-xs items-center justify-end rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-right !text-[11px] font-medium !text-amber-800 shadow-sm">{{ validationErrors.badge_week_start }}</p>
                    </div>
                    <select
                      id="badge-week-start"
                      :value="modelValue.badge_week_start || ''"
                      @change="updateField('badge_week_start', $event.target.value)"
                      :disabled="!modelValue.badge_verified"
                      style="color-scheme: light;"
                      class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                    >
                      <option value="">Select a week</option>
                      <option v-for="week in launchWeekOptions" :key="week.value" :value="week.value">
                        {{ week.label }}
                      </option>
                    </select>
                    <p class="mt-2 text-xs text-gray-500">
                      {{ modelValue.badge_verified ? 'Your product will publish on Monday of the selected week.' : 'Verify the badge first to unlock week selection.' }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="flex flex-col items-start gap-4">
                <div v-if="validationSummary.length || generalErrorMessage" class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                  <p class="text-xs font-semibold !text-amber-800">Please fix these before submitting:</p>
                  <ul class="mt-2 space-y-1.5 !text-[11px] !text-amber-700">
                    <li v-for="item in validationSummary" :key="item.field">
                      <button
                        type="button"
                        class="block w-full rounded-xl border border-amber-300 bg-amber-100 px-3 py-2 text-left !text-[11px] font-medium !text-amber-800 shadow-sm transition-colors hover:bg-amber-200"
                        @click="$emit('focus-field', item.field)"
                      >
                        {{ item.message }}
                      </button>
                    </li>
                    <li v-if="generalErrorMessage">{{ generalErrorMessage }}</li>
                  </ul>
                </div>
                <div v-if="!isAllRequiredFilled" class="text-sm font-medium text-amber-600">
                  Fill all required fields before submitting.
                </div>
                <div v-else-if="wantsBadgeLaunch && (!modelValue.badge_verified || !modelValue.badge_week_start)" class="text-sm font-medium text-amber-600">
                  Verify the badge and choose a week to skip the wait.
                </div>
                <AnimatedSubmitButton
                  :label="submitButtonLabel"
                  :state="submitButtonVisualState"
                  :disabled="submitButtonDisabled"
                  @click="handleSubmission"
                />
              </div>
            </div>
          </div>
        </div>
        
        <div v-else-if="!!modelValue.id" class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Admin Controls</h3>
          <p class="text-sm text-gray-600 mb-6">{{ adminDescription }}</p>
          <div v-if="!!modelValue.id" class="space-y-4 mb-6">
            <div>
              <label for="comparison-overrides" class="block text-sm font-semibold text-gray-700 mb-1">
                Curated Comparisons
              </label>
              <textarea
                id="comparison-overrides"
                :value="modelValue.comparison_overrides_input || ''"
                @input="updateField('comparison_overrides_input', $event.target.value)"
                rows="3"
                placeholder="Comma or newline separated product IDs or slugs (e.g. 12, ai-agent-flow, another-product)"
                class="w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">
                These are shown first in the sidebar "Compare with" section.
              </p>
            </div>

            <div>
              <label for="alternative-overrides" class="block text-sm font-semibold text-gray-700 mb-1">
                Curated Alternatives
              </label>
              <textarea
                id="alternative-overrides"
                :value="modelValue.alternative_overrides_input || ''"
                @input="updateField('alternative_overrides_input', $event.target.value)"
                rows="3"
                placeholder="Comma or newline separated product IDs or slugs"
                class="w-full px-3 py-2 bg-white text-gray-600 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-sky-400 focus:border-sky-400 sm:text-sm"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">
                These are shown first on the alternatives page.
              </p>
            </div>
          </div>
          <div class="flex flex-col items-start gap-4">
            <div v-if="validationSummary.length || generalErrorMessage" class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
              <p class="text-xs font-semibold !text-amber-800">Please fix these before submitting:</p>
              <ul class="mt-2 space-y-1.5 !text-[11px] !text-amber-700">
                <li v-for="item in validationSummary" :key="item.field">
                  <button
                    type="button"
                    class="block w-full rounded-xl border border-amber-300 bg-amber-100 px-3 py-2 text-left !text-[11px] font-medium !text-amber-800 shadow-sm transition-colors hover:bg-amber-200"
                    @click="$emit('focus-field', item.field)"
                  >
                    {{ item.message }}
                  </button>
                </li>
                <li v-if="generalErrorMessage">{{ generalErrorMessage }}</li>
              </ul>
            </div>
            <div v-if="isSandboxAvailable && modelValue.sandbox_mode" class="text-sm text-amber-700 font-medium">
              Sandbox mode ignores all required fields and keeps this run out of the database.
            </div>
            <div v-else-if="!isAllRequiredFilled" class="text-sm text-amber-600 font-medium">
              Note: Some required fields are missing, but you can still save as admin.
            </div>
            <AnimatedSubmitButton
              v-if="isSandboxAvailable && modelValue.sandbox_mode"
              :label="adminActionLabel"
              :state="submitButtonVisualState"
              :disabled="isLoading"
              @click="emitAdminSubmit"
            />
            <button
              v-else
              type="button"
              @click="emitAdminSubmit"
              :disabled="isLoading"
              :class="{
                'cursor-wait': isLoading,
                'hover:bg-rose-700': !isLoading
              }"
              class="relative inline-flex min-h-12 items-center justify-center rounded-lg bg-rose-600 px-8 py-3 text-sm font-bold text-white shadow-md transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
            >
              <span
                class="whitespace-nowrap transition-opacity duration-150"
                :class="isLoading ? 'opacity-0' : 'opacity-100'"
              >
                Save All Changes
              </span>
              <span
                v-if="isLoading"
                class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current"
                aria-live="polite"
              >
                <span class="flex items-center gap-1.5" aria-hidden="true">
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.3s]"></span>
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.15s]"></span>
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                </span>
                <span>Saving</span>
              </span>
            </button>
          </div>
        </div>
        
        <div v-else class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Admin Submission</h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ adminCreateDescription }}
          </p>
          <div class="flex flex-col items-start gap-4">
            <div v-if="validationSummary.length || generalErrorMessage" class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
              <p class="text-xs font-semibold !text-amber-800">Please fix these before submitting:</p>
              <ul class="mt-2 space-y-1.5 !text-[11px] !text-amber-700">
                <li v-for="item in validationSummary" :key="item.field">
                  <button
                    type="button"
                    class="block w-full rounded-xl border border-amber-300 bg-amber-100 px-3 py-2 text-left !text-[11px] font-medium !text-amber-800 shadow-sm transition-colors hover:bg-amber-200"
                    @click="$emit('focus-field', item.field)"
                  >
                    {{ item.message }}
                  </button>
                </li>
                <li v-if="generalErrorMessage">{{ generalErrorMessage }}</li>
              </ul>
            </div>
            <div v-if="isSandboxAvailable && modelValue.sandbox_mode" class="text-sm text-amber-700 font-medium">
              Sandbox mode is active, so this button will simulate submission without saving anything.
            </div>
            <div v-else-if="!isAllRequiredFilled" class="text-sm text-amber-600 font-medium">
              Fill the required fields to submit this product.
            </div>
            <AnimatedSubmitButton
              v-if="isSandboxAvailable && modelValue.sandbox_mode"
              :label="adminCreateActionLabel"
              :state="submitButtonVisualState"
              :disabled="isLoading"
              @click="emitAdminSubmit"
            />
              <button
              v-else
              type="button"
              @click="emitAdminSubmit"
              :disabled="isLoading"
              :class="{
                'cursor-wait': isLoading,
                'hover:bg-primary-600': !isLoading
              }"
              class="relative inline-flex min-h-12 items-center justify-center rounded-lg bg-primary-500 px-8 py-3 text-sm font-bold text-white shadow-md transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            >
              <span
                class="whitespace-nowrap transition-opacity duration-150"
                :class="isLoading ? 'opacity-0' : 'opacity-100'"
              >
                Submit Product
              </span>
              <span
                v-if="isLoading"
                class="absolute inset-0 flex items-center justify-center gap-2 whitespace-nowrap text-current"
                aria-live="polite"
              >
                <span class="flex items-center gap-1.5" aria-hidden="true">
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.3s]"></span>
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse [animation-delay:-0.15s]"></span>
                  <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                </span>
                <span>Submitting</span>
              </span>
            </button>
          </div>
        </div>
      </section> <!-- End Pricing Options / Save Button -->
      
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import AnimatedSubmitButton from './AnimatedSubmitButton.vue';
import { getTabProgress } from '../../services/productFormService';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  logoPreview: {
    type: String,
    default: null
  },
  allTechStacks: Array,
  isAdmin: Boolean,
  adminSandboxEnabled: {
    type: Boolean,
    default: true,
  },
  isLoading: Boolean,
  submitState: {
    type: String,
    default: 'idle',
  },
  validationErrors: {
    type: Object,
    default: () => ({}),
  },
  validationSummary: {
    type: Array,
    default: () => [],
  },
  generalErrorMessage: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'submit', 'focus-field']);

const progress = computed(() => getTabProgress('launchChecklist', props.modelValue, props.logoPreview));
const wantsBadgeLaunch = ref(!!props.modelValue.badge_opt_in || props.modelValue.submission_type === 'badge');
const isVerifyingBadge = ref(false);
const badgeVerificationMessage = ref('');
const badgeVerificationSuccess = ref(false);
const badgeSnippet = ref('');
const hasCopiedBadgeSnippet = ref(false);

// Tech Stack search + toggle
const techSearch = ref('');

const showAddTechStackButton = computed(() => {
  const search = techSearch.value.trim();
  if (!search) return false;
  if (props.modelValue.tech_stack_custom?.some(ts => ts.name.toLowerCase() === search.toLowerCase())) return false;
  if ((props.modelValue.tech_stack_custom?.length || 0) >= 3) return false;
  return !props.allTechStacks?.some(ts => ts.name.toLowerCase() === search.toLowerCase());
});

const filteredTechStacks = computed(() => {
  if (!props.allTechStacks) return [];
  if (!techSearch.value.trim()) return props.allTechStacks;
  
  const existingCustomTechStacks = props.modelValue.tech_stack_custom?.map(ts => ts.name.toLowerCase()) || [];
  
  return props.allTechStacks.filter(t =>
    t.name.toLowerCase().includes(techSearch.value.toLowerCase()) &&
    !existingCustomTechStacks.includes(t.name.toLowerCase())
  );
});

function toggleTechStack(id) {
  const current = Array.isArray(props.modelValue.tech_stack) ? [...props.modelValue.tech_stack] : [];
  const idx = current.indexOf(id);
  if (idx === -1) {
    if (current.length < 5) current.push(id);
  } else {
    current.splice(idx, 1);
  }
  updateField('tech_stack', current);
}

// Functions to handle custom tech stacks (triggered from search inline button)
function addCustomTechStackFromSearch() {
  const name = techSearch.value.trim();
  if (!name) return;
  if ((props.modelValue.tech_stack_custom?.length || 0) >= 3) return;
  
  const newCustomTechStack = {
    id: `custom-${Date.now()}`,
    name,
    is_custom: true
  };
  
  const updatedCustomTechStacks = [...(props.modelValue.tech_stack_custom || []), newCustomTechStack];
  updateField('tech_stack_custom', updatedCustomTechStacks);
  techSearch.value = '';
}

function removeCustomTechStack(customTechStackId) {
  const currentCustomTechStacks = props.modelValue.tech_stack_custom || [];
  const updatedCustomTechStacks = currentCustomTechStacks.filter(ts => ts.id !== customTechStackId);
  updateField('tech_stack_custom', updatedCustomTechStacks);
}

function updateField(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}

watch(
  () => props.modelValue.submission_type,
  (value) => {
    wantsBadgeLaunch.value = !!props.modelValue.badge_opt_in || value === 'badge';
  },
  { immediate: true }
);

watch(
  () => props.modelValue.badge_verified,
  (value) => {
    if (value) {
      badgeVerificationSuccess.value = true;
      if (!badgeVerificationMessage.value) {
        badgeVerificationMessage.value = 'Badge verified. You can now choose a launch week.';
      }
      return;
    }

    if (badgeVerificationSuccess.value) {
      badgeVerificationSuccess.value = false;
      badgeVerificationMessage.value = 'Badge URL changed. Verify again to unlock week selection.';
    }
  }
);

// Check if all required fields are filled
const isAllRequiredFilled = computed(() => {
  const { link, name, tagline, description, categories, bestFor, pricing, logo, logos } = props.modelValue;
  const categoriesCustom = props.modelValue.categories_custom || [];
  
  // Check if actual pricing categories are selected (not submission options like 'free' or 'paid')
  const actualPricingCategories = (pricing || []).filter(id => id !== null && id !== undefined && id !== '' && !isNaN(id));
  
  const requiredFields = [
    link,
    name,
    tagline,
    description,
    (categories && Array.isArray(categories) && categories.length > 0) || categoriesCustom.length > 0,
    // bestFor is optional — not checked here
    actualPricingCategories.length > 0, // Only count actual pricing categories, not submission options
    logo || (logos && Array.isArray(logos) && logos.length > 0) || props.logoPreview // Check for logo preview as well
 ];
  
  return requiredFields.every(field => field);
});

const freeLaunchFeatures = [
  'Free to submit',
  'Join the standard review queue',
  'Skip the wait by sharing our badge and verifying it'
];

const badgePlacementUrlReady = computed(() => {
  const value = (props.modelValue.badge_placement_url || '').trim();
  return /^https?:\/\//i.test(value);
});

const launchWeekOptions = computed(() => {
  const weeks = [];
  const today = new Date();
  const nextMonday = new Date(today);
  const daysUntilNextMonday = ((8 - nextMonday.getDay()) % 7) || 7;
  nextMonday.setDate(nextMonday.getDate() + daysUntilNextMonday);
  nextMonday.setHours(0, 0, 0, 0);

  for (let i = 0; i < 12; i += 1) {
    const start = new Date(nextMonday);
    start.setDate(nextMonday.getDate() + (i * 7));

    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    weeks.push({
      value: formatLocalDateValue(start),
      label: formatWeekLabel(start, end),
    });
  }

  return weeks;
});

const submitButtonDisabled = computed(() => {
  if (props.isLoading || isVerifyingBadge.value) {
    return true;
  }
  return false;
});

const submitButtonVisualState = computed(() => {
  if (props.submitState === 'loading' || props.submitState === 'success') {
    return props.submitState;
  }

  return 'idle';
});

const isSandboxAvailable = computed(() => !props.modelValue.id && props.isAdmin && props.adminSandboxEnabled);

const adminDescription = computed(() => {
  if (isSandboxAvailable.value) {
    return 'As an admin, you can save directly here, and Sandbox mode can be controlled from the top of the page.';
  }

  return 'As an admin, you can save your edits directly without selecting a pricing option.';
});

const adminActionLabel = computed(() => {
  if (props.modelValue.sandbox_mode) {
    return props.modelValue.id ? 'Run Sandbox Save' : 'Run Sandbox Submit';
  }

  return 'Save All Changes';
});

const adminCreateActionLabel = computed(() => (
  props.modelValue.sandbox_mode ? 'Run Sandbox Submit' : 'Submit Product'
));

const adminCreateDescription = computed(() => {
  if (isSandboxAvailable.value) {
    return 'Submit the product from the bottom of the form. Use Sandbox mode from the top of the page if you only want to test button states.';
  }

  return 'Submit the product from the bottom of the form. Sandbox mode is currently disabled in admin settings.';
});

const submitButtonLabel = computed(() => {
  if (wantsBadgeLaunch.value && props.modelValue.badge_verified) {
    return 'Submit And Schedule Week';
  }

  return 'Submit For Free';
});

const formatLocalDateValue = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formatWeekLabel = (start, end) => {
  const startMonthLabel = start.toLocaleDateString('en-US', {
    month: 'short',
  });
  const endMonthLabel = end.toLocaleDateString('en-US', {
    month: 'short',
  });

  if (startMonthLabel === endMonthLabel) {
    return `${startMonthLabel} ${start.getDate()} - ${end.getDate()}, ${end.getFullYear()}`;
  }

  return `${startMonthLabel} ${start.getDate()} - ${endMonthLabel} ${end.getDate()}, ${end.getFullYear()}`;
};

const loadBadgeSnippet = async () => {
  if (badgeSnippet.value) {
    return;
  }

  try {
    const response = await axios.get('/api/badge-snippet-preview');
    badgeSnippet.value = response.data?.snippet || '';
  } catch (error) {
    console.error('Failed to load badge snippet preview:', error);
  }
};

const copyBadgeSnippet = async () => {
  if (!badgeSnippet.value) {
    return;
  }

  try {
    await navigator.clipboard.writeText(badgeSnippet.value);
    hasCopiedBadgeSnippet.value = true;
    badgeVerificationSuccess.value = true;
    badgeVerificationMessage.value = 'Badge code copied. Add it to your site, then verify the page URL below.';
    window.setTimeout(() => {
      hasCopiedBadgeSnippet.value = false;
    }, 2000);
  } catch (error) {
    console.error('Failed to copy badge snippet:', error);
  }
};

const toggleBadgeLaunch = (checked) => {
  wantsBadgeLaunch.value = checked;

  if (checked) {
    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: true,
      submissionOption: props.modelValue.badge_verified ? 'badge' : 'free',
      submission_type: props.modelValue.badge_verified ? 'badge' : 'free',
      badge_placement_url: props.modelValue.badge_placement_url || props.modelValue.link || '',
    });
    loadBadgeSnippet();
    return;
  }

  badgeVerificationSuccess.value = false;
  badgeVerificationMessage.value = '';
  emit('update:modelValue', {
    ...props.modelValue,
    badge_opt_in: false,
    submissionOption: 'free',
    submission_type: 'free',
    badge_placement_url: '',
    badge_week_start: '',
    badge_verified: false,
  });
};

const handleBadgeUrlInput = (value) => {
  const badgeUrlChanged = (props.modelValue.badge_placement_url || '') !== value;
  emit('update:modelValue', {
    ...props.modelValue,
    badge_placement_url: value,
    badge_verified: badgeUrlChanged ? false : props.modelValue.badge_verified,
    badge_week_start: badgeUrlChanged ? '' : props.modelValue.badge_week_start,
  });

  if (badgeUrlChanged) {
    badgeVerificationSuccess.value = false;
    badgeVerificationMessage.value = '';
  }
};

const verifyBadgePlacement = async () => {
  if (!badgePlacementUrlReady.value) {
    badgeVerificationSuccess.value = false;
    badgeVerificationMessage.value = 'Enter the full badge page URL, including https://.';
    return;
  }

  isVerifyingBadge.value = true;
  badgeVerificationSuccess.value = false;
  badgeVerificationMessage.value = '';

  try {
    const response = await axios.post('/api/verify-badge-placement', {
      url: props.modelValue.badge_placement_url,
    });

    badgeVerificationSuccess.value = true;
    badgeVerificationMessage.value = response.data?.message || 'Badge verified. You can now choose a launch week.';

    emit('update:modelValue', {
      ...props.modelValue,
      badge_opt_in: true,
      submissionOption: 'badge',
      submission_type: 'badge',
      badge_placement_url: response.data?.checked_url || props.modelValue.badge_placement_url,
      badge_verified: true,
    });
  } catch (error) {
    badgeVerificationSuccess.value = false;
    badgeVerificationMessage.value = error.response?.data?.message || 'We could not verify the badge on that page yet.';
    emit('update:modelValue', {
      ...props.modelValue,
      submissionOption: 'free',
      submission_type: 'free',
      badge_verified: false,
      badge_week_start: '',
    });
  } finally {
    isVerifyingBadge.value = false;
  }
};

const handleSubmission = () => {
  const isBadgeSubmission = wantsBadgeLaunch.value && props.modelValue.badge_verified;

  emit('update:modelValue', {
    ...props.modelValue,
    badge_opt_in: wantsBadgeLaunch.value,
    submissionOption: isBadgeSubmission ? 'badge' : 'free',
    submission_type: isBadgeSubmission ? 'badge' : 'free',
    badge_week_start: isBadgeSubmission ? props.modelValue.badge_week_start : '',
    badge_placement_url: wantsBadgeLaunch.value ? props.modelValue.badge_placement_url : '',
    badge_verified: isBadgeSubmission,
    tech_stack_custom: props.modelValue.tech_stack_custom,
  });

  emit('submit');
};

const emitAdminSubmit = () => {
  emit('submit');
};

onMounted(() => {
  if (wantsBadgeLaunch.value) {
    loadBadgeSnippet();
  }
});


// Handle field click - navigate to the corresponding form field
const handleFieldClick = (fieldKey) => {
 // Add a small delay to ensure the tab switch happens before scrolling
 setTimeout(() => {
    // Try to focus the corresponding input field if it exists
    const fieldElement = document.querySelector(`[data-field="${fieldKey}"]`);
    if (fieldElement) {
      fieldElement.focus();
      fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }, 100);
};

// Helper function to format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(parseFloat(amount));
};
</script>

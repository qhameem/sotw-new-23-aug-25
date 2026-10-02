<template>
  <div id="field-link" :class="{ 'sticky top-0 z-30': reviewMode }" class="mb-4 bg-slate-50 border border-slate-300 rounded-xl p-6">
    <div class="mb-3 flex flex-wrap items-start justify-between gap-4">
      <div class="flex items-center gap-2">
        <label for="product-url" class="block text-sm font-bold text-gray-900">Website URL <span class="text-red-500">*</span></label>
      </div>
      <span v-if="extractionTiming.started" class="ml-auto shrink-0 text-xs font-medium tabular-nums text-gray-700">
        {{ extractionTiming.running ? 'Elapsed' : 'Total' }}: {{ extractionTiming.seconds.toFixed(1) }}s
      </span>
    </div>

    <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-start">
      <div class="min-w-0 flex-1">
        <div class="relative">
          <input
            id="product-url"
            ref="inputRef"
            :value="modelValue"
            @input="handleInput"
            @keydown.enter.prevent="handleAutoFillClick"
            :aria-invalid="inlineError ? 'true' : 'false'"
            :aria-busy="isUrlChecking"
            :aria-describedby="inlineError ? 'product-url-help product-url-error' : 'product-url-help'"
            @focus="refreshCaret"
            @blur="refreshCaret"
            @input.capture="refreshCaret"
            @keyup="refreshCaret"
            @click="refreshCaret"
            @select="refreshCaret"
            @scroll="refreshCaret"
            @compositionstart="refreshCaret"
            @compositionend="refreshCaret"
            :style="{ caretColor: caretStyle ? 'transparent' : 'black' }"
            type="text"
            inputmode="url"
            autocomplete="url"
            :spellcheck="false"
            required
            class="block w-full h-11 pl-4 pr-20 py-2 bg-white border-2 rounded-xl text-sm caret-black placeholder-gray-400
                   shadow-none focus:outline-none focus:ring-0 focus:shadow-none transition-colors"
            :class="urlExistsError || urlMatchesDraft
              ? 'border-amber-500 focus:border-amber-600'
              : 'border-slate-400 focus:border-primary-500'"
            placeholder="https://your-website.com"
          >
          <span v-if="caretStyle" aria-hidden="true" class="url-caret" :style="caretStyle" />
          <button
            type="button"
            @click="pasteFromClipboard"
            :disabled="isLoading"
            class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-sky-600 disabled:text-gray-300 transition-colors"
            aria-label="Paste from clipboard"
            title="Paste from clipboard"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </button>
          <button v-if="modelValue" type="button" @click="$emit('clear')" class="absolute inset-y-0 right-10 flex items-center text-gray-400 hover:text-gray-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        <p id="product-url-help" class="mt-3 text-xs text-slate-600">Paste your site. AI fills in the name, tagline, description, logo, screenshot, and the rest. Review and tweak before submitting.</p>
        <div role="status" aria-live="polite" aria-atomic="true">
          <p v-if="isUrlChecking" class="mt-2 flex items-center gap-2 text-xs text-slate-600">
            <svg class="h-3.5 w-3.5 shrink-0 animate-spin text-primary-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Checking for duplicates
          </p>
        </div>
        <p v-if="inlineError && !urlExistsError" id="product-url-error" role="alert" class="mt-2 text-sm !text-red-600">{{ inlineError }}</p>
      </div>

      <div class="relative shrink-0">
        <button
          type="button"
          @click="handleAutoFillClick"
          :aria-disabled="isAutoFillDisabled ? 'true' : 'false'"
          :disabled="isAutoFillDisabled"
          :aria-busy="isLoading"
          class="h-11 w-full sm:w-[168px] px-6 rounded-xl border-2 border-primary-700 bg-primary-500 shadow-[0_4px_0_var(--color-primary-700),0_6px_10px_rgba(15,23,42,0.12)] enabled:active:translate-y-1 enabled:active:shadow-none motion-safe:transition-all motion-safe:duration-150 text-white font-bold text-sm flex items-center justify-center gap-2 whitespace-nowrap hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
        <span v-if="isLoading" class="flex items-center gap-2">
          <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loadingProgress > 0 && loadingProgress < 10 ? 'Starting...' : 'Working...' }}
        </span>
        <span v-else class="flex items-center gap-2">
          <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 36 36" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M34.1,4,31.71,1.6a1.83,1.83,0,0,0-1.31-.54h0a2.05,2.05,0,0,0-1.45.62L1.76,29.23A2,2,0,0,0,1.68,32l2.4,2.43A1.83,1.83,0,0,0,5.39,35h0a2.05,2.05,0,0,0,1.45-.62L34,6.79A2,2,0,0,0,34.1,4ZM5.42,32.93,3.16,30.65h0L24.11,9.43l2.25,2.28ZM32.61,5.39l-5.12,5.18L25.24,8.29l5.13-5.2,2.25,2.28Z" />
            <path d="M32.53,20.47l2.09-2.09a.8.8,0,0,0-1.13-1.13l-2.09,2.09-2.09-2.09a.8.8,0,0,0-1.13,1.13l2.09,2.09-2.09,2.09a.8.8,0,0,0,1.13,1.13l2.09-2.09,2.09,2.09a.8.8,0,0,0,1.13-1.13Z" />
            <path d="M14.78,6.51a.8.8,0,0,0,1.13,0L17.4,5l1.49,1.49A.8.8,0,0,0,20,5.38L18.54,3.89,20,2.4a.8.8,0,0,0-1.13-1.13L17.4,2.76,15.91,1.27A.8.8,0,1,0,14.78,2.4l1.49,1.49L14.78,5.38A.8.8,0,0,0,14.78,6.51Z" />
            <path d="M8.33,15.26a.8.8,0,0,0,1.13,0l1.16-1.16,1.16,1.16a.8.8,0,1,0,1.13-1.13L11.76,13l1.16-1.16a.8.8,0,1,0-1.13-1.13l-1.16,1.16L9.46,10.68a.8.8,0,1,0-1.13,1.13L9.49,13,8.33,14.13A.8.8,0,0,0,8.33,15.26Z" />
          </svg>
          Fill with AI →
        </span>
        </button>
        <div
          v-if="showDisabledTooltip"
          role="tooltip"
          class="absolute right-0 top-full z-20 mt-2 w-64 rounded-md bg-gray-900 px-3 py-2 text-left text-xs font-medium leading-4 text-white shadow-lg"
        >
          {{ disabledReason }}
        </div>
      </div>
    </div>
    <div v-if="!reviewMode && !isLoading" class="mt-5">
      <button
        type="button"
        class="text-xs font-semibold text-sky-700 underline underline-offset-2 hover:text-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-500"
        @click="$emit('manual')"
      >
        <span class="font-bold">Or</span> skip auto-fill and enter the other details manually.
      </button>
    </div>
    <details v-if="showPhaseTimings && Object.keys(extractionTiming.phases).length" class="mt-3 text-xs text-gray-600">
      <summary class="cursor-pointer">Extraction phase timings</summary>
      <dl class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
        <div v-for="(seconds, phase) in extractionTiming.phases" :key="phase" class="flex justify-between gap-4">
          <dt>{{ phaseLabel(phase) }}</dt><dd class="tabular-nums">{{ seconds.toFixed(2) }}s</dd>
        </div>
      </dl>
      <p class="mt-2">Total includes network and form updates.</p>
    </details>
    <transition name="fade">
      <p
        v-if="clipboardFeedback"
        :class="[
          'mt-3 text-xs',
          clipboardFeedbackType === 'error' ? 'text-red-600' : 'text-sky-600'
        ]"
      >
        {{ clipboardFeedback }}
      </p>
    </transition>

    <transition name="fade">
      <p
        v-if="isSandboxMode && !isLoading"
        class="mt-3 text-xs text-amber-700"
      >
        Sandbox mode: click AI Auto-fill to run a simulated product autofill without using a real URL.
      </p>
    </transition>

    <transition name="fade">
      <div
        v-if="urlTrimSuggestion && !isLoading"
        class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800 flex items-start justify-between gap-3"
      >
        <div class="flex items-start gap-2 min-w-0">
          <svg class="h-5 w-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="min-w-0">
            <span class="font-medium">Tip:</span> you can trim
            {{ urlTrimSuggestion.removedParts.join(', ') }}.
            <span class="block text-xs mt-1 font-mono break-all">{{ urlTrimSuggestion.suggestedUrl }}</span>
          </span>
        </div>
        <button
          type="button"
          @click="applyTrimSuggestion"
          class="px-2.5 py-1 rounded-md bg-amber-100 hover:bg-amber-200 text-amber-900 text-xs font-semibold whitespace-nowrap"
        >
          Use trimmed URL
        </button>
      </div>
    </transition>

    <!-- Error Message -->
    <transition name="fade">
      <div v-if="urlExistsError" class="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-600 flex items-start gap-2">
        <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="min-w-0">
          <p>
            This exact URL already exists as
            <a :href="existingProduct?.view_url || `/product/${existingProduct.slug}`" target="_blank" class="font-bold hover:underline underline-offset-2">"{{ existingProduct.name }}"</a>.
          </p>
          <div v-if="existingProduct?.can_edit && existingProduct?.edit_url" class="mt-2">
            <a :href="existingProduct.edit_url" class="inline-flex items-center text-xs font-semibold text-gray-700 underline underline-offset-2 hover:text-gray-950">
              Edit existing product
            </a>
          </div>
        </div>
      </div>
    </transition>

    <slot name="details" />

    <!-- Debug Info (Temporary - Hidden for production look) -->
    <!-- <div class="text-xs text-gray-400 mt-2">Debug: Value="{{ modelValue }}" ...</div> -->
  </div>
</template>

<script setup>
import { useExtractionTimer } from '../../composables/useExtractionTimer';
const { timing: extractionTiming } = useExtractionTimer();
const phaseLabel = (key) => {
  const [scope, phase] = key.split(':');
  const labels = { url_check: 'URL check', metadata: 'Website metadata', logo: 'Logo fetching', screenshot: 'Screenshot', tagline: 'Tagline generation', description: 'Description writing', categories: 'Category classification', tech_stack: 'Tech stack detection', context: 'Additional context', research: 'Description research', name: 'Product name' };
  const prefix = { initial: 'Initial', details: 'Detailed', fallback: 'Retry', client: '' }[scope] || '';
  return [prefix, labels[phase] || phase].filter(Boolean).join(' · ');
};
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { useInputCaret } from '../../composables/useInputCaret';
import { productFormService } from '../../services/productFormService';

const props = defineProps({
  modelValue: String,
  isLoading: Boolean,
  showPhaseTimings: Boolean,
  isUrlChecking: Boolean,
  urlCheckFailed: Boolean,
  loadingProgress: Number,
  loadingMessage: String,
  isUrlInvalid: Boolean,
  urlTrimSuggestion: Object,
  urlExistsError: Boolean,
  urlMatchesDraft: Boolean,
  existingProduct: Object,
  fieldError: {
    type: String,
    default: '',
  },
  submissionBgUrl: String,
  isSandboxMode: {
    type: Boolean,
    default: false,
  },
  reviewMode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'getStarted', 'clear', 'validate-field', 'manual']);
const clipboardFeedback = ref('');
const clipboardFeedbackType = ref('info');
const inputRef = ref(null);
const { caretStyle, refreshCaret } = useInputCaret(inputRef);
watch(() => props.modelValue, refreshCaret, { flush: 'post' });
const inlineError = computed(() => props.fieldError || (props.isUrlInvalid && String(props.modelValue || '').trim() ? 'Enter a valid website URL.' : props.urlCheckFailed ? 'Unable to check this URL. Please try again.' : ''));
const showDisabledTooltip = ref(false);
let disabledTooltipTimeout = null;
const isAutoFillDisabled = computed(() => props.isLoading || (
  !props.isSandboxMode && (
    props.isUrlChecking
    || props.urlCheckFailed
    || props.urlExistsError
    || props.isUrlInvalid
    || !String(props.modelValue || '').trim()
  )
));
const disabledReason = computed(() => {
  if (props.isLoading) return 'AI Auto-fill is already running.';
  if (props.urlExistsError) return 'AI Auto-fill is unavailable because this product URL already exists.';
  if (props.urlCheckFailed) return 'The duplicate check failed. Change the URL or try again shortly.';
  if (props.isUrlChecking) return 'Wait until the product URL duplicate check finishes.';
  if (!String(props.modelValue || '').trim()) return 'Enter a product URL first.';
  if (props.isUrlInvalid) return 'Enter a valid product URL first.';
  return 'AI Auto-fill is not available yet.';
});

const handleAutoFillClick = () => {
  emit('validate-field', 'link');
  if (!isAutoFillDisabled.value) {
    performValidationAndFetch();
    return;
  }

  showDisabledTooltip.value = true;
  window.clearTimeout(disabledTooltipTimeout);
  disabledTooltipTimeout = window.setTimeout(() => {
    showDisabledTooltip.value = false;
  }, 3500);
};

const handleInput = (event) => {
  const value = event.target.value;
  clipboardFeedback.value = '';
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

const performValidationAndFetch = async (explicitValue = null) => {
  console.log('[ProductURLInput] Starting validation sequence...');
  clipboardFeedback.value = '';

  const normalizedExplicitValue = explicitValue instanceof Event ? null : explicitValue;

  // Get the current value directly from the input element to avoid timing issues
  const inputValue = normalizedExplicitValue ?? inputRef.value?.value ?? document.getElementById('product-url')?.value ?? props.modelValue;
  console.log('[ProductURLInput] Using URL value:', inputValue);

  if (inputValue !== props.modelValue) {
    emit('update:modelValue', inputValue);
  }
  emit('validate-field', 'link');

  // Step 1: Check if anything is loading
  console.log('[ProductURLInput] Step 1: Checking if anything is loading...');
  if (isAutoFillDisabled.value) {
    console.log('[ProductURLInput] Validation failed: Something is loading');
    return;
  }
  console.log('[ProductURLInput] Step 1 passed: Nothing is loading');

  if (props.isSandboxMode) {
    console.log('[ProductURLInput] Sandbox mode active, skipping URL validation');
    emit('getStarted', inputValue || '__sandbox__');
    return;
  }

  // Step 2: Check if URL is valid
  console.log('[ProductURLInput] Step 2: Checking if URL is valid...');
  console.log('[ProductURLInput] URL to validate:', inputValue);
  const isUrlValid = !productFormService.isUrlInvalid(inputValue);
  if (!isUrlValid) {
    console.log('[ProductURLInput] Validation failed: URL is invalid');
    return;
  }
  console.log('[ProductURLInput] Step 2 passed: URL is valid');

  // All validations passed, proceed with fetching data
  console.log('[ProductURLInput] All validations passed, proceeding to fetch data...');
  emit('getStarted', inputValue);
};

const pasteFromClipboard = async () => {
  clipboardFeedback.value = '';

  if (props.isLoading) {
    return;
  }

  if (!navigator?.clipboard?.readText) {
    clipboardFeedbackType.value = 'error';
    clipboardFeedback.value = 'Clipboard access is not available in this browser. Paste the URL manually, then click AI Auto-fill.';
    return;
  }

  try {
    const clipboardText = (await navigator.clipboard.readText()).trim();

    if (!clipboardText) {
      clipboardFeedbackType.value = 'error';
      clipboardFeedback.value = 'Your clipboard is empty. Copy a website URL and try again.';
      return;
    }

    if (inputRef.value) {
      inputRef.value.value = clipboardText;
    }

    emit('update:modelValue', clipboardText);
    clipboardFeedbackType.value = 'info';
    clipboardFeedback.value = 'URL pasted from clipboard.';
  } catch (error) {
    console.error('[ProductURLInput] Clipboard read failed:', error);
    clipboardFeedbackType.value = 'error';
    clipboardFeedback.value = 'Clipboard permission was denied. Paste the URL manually, then click AI Auto-fill.';
  }
};

const applyTrimSuggestion = () => {
  const suggestedUrl = props.urlTrimSuggestion?.suggestedUrl;
  if (!suggestedUrl) {
    return;
  }

  emit('update:modelValue', suggestedUrl);
};


// Focus the input when component is mounted
onMounted(() => {
  // Use nextTick to ensure the DOM is fully rendered
 setTimeout(() => {
    if (inputRef.value) {
      inputRef.value.focus();
    }
 }, 100);
});

onUnmounted(() => window.clearTimeout(disabledTooltipTimeout));
</script>

<style scoped>
.url-caret {
  position: absolute;
  width: 2px;
  background: #000;
  pointer-events: none;
  animation: caret-blink 1s step-end infinite;
}
@keyframes caret-blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .url-caret { animation: none; }
}
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

@keyframes progress-shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(200%);
  }
}

.progress-bar-shimmer {
  background: linear-gradient(
    100deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.12) 35%,
    rgba(255, 255, 255, 0.45) 50%,
    rgba(255, 255, 255, 0.12) 65%,
    rgba(255, 255, 255, 0) 100%
  );
  animation: progress-shimmer 1.6s linear infinite;
}
</style>

<template>
  <div
    role="button"
    tabindex="0"
    class="relative flex h-full w-full cursor-pointer flex-col rounded-lg border p-5 text-left transition-all duration-200 hover:-translate-y-1 hover:shadow-md md:p-6"
    :class="selected ? 'border-primary-400 bg-primary-50 shadow-sm hover:bg-primary-50 hover:shadow-lg' : 'border-gray-200 hover:border-primary-300 hover:bg-primary-50'"
    @click="$emit('select')"
    @keydown.enter.prevent="$emit('select')"
    @keydown.space.prevent="$emit('select')"
  >
    <div
      v-if="selected"
      class="absolute -right-2 -top-2 inline-flex items-center rounded-lg bg-primary-500 px-3 py-1 text-xs font-medium text-white"
    >
      Selected
    </div>

    <div class="flex items-center gap-2.5 text-gray-900">
      <svg class="h-5 w-5 shrink-0" viewBox="0 0 92.375 92.375" fill="currentColor" aria-hidden="true">
        <path d="M20.068,48.292c-1.014,5.625,6.817,27.28,7.713,29.729c0.118,0.322,0.391,0.562,0.725,0.635 c0.335,0.074,0.683-0.028,0.924-0.271c1.002-1.01,2.053-2.875,3.27-5.037c1.61-2.865,3.547-6.309,5.474-7.729v0.899 c0,0.553,0.447,1,1,1h14.021c0.553,0,1-0.447,1-1v-0.633c1.928,1.422,3.862,4.863,5.475,7.729c1.219,2.162,2.27,4.031,3.271,5.037 c0.188,0.189,0.446,0.295,0.709,0.295c0.071,0,0.146-0.008,0.216-0.021c0.334-0.073,0.605-0.313,0.727-0.635 c0.896-2.449,8.729-24.104,7.714-29.729c-0.369-2.047-2.146-3.224-3.709-4.261c-0.318-0.212-0.63-0.417-0.916-0.623 c0.729-2.924,1.096-5.904,1.096-8.868c0-5.669-1.343-11.168-3.787-16.192c0.004-0.035,0.021-0.066,0.021-0.103 c0-0.364-0.204-0.669-0.495-0.844c-3.791-7.266-9.938-13.46-17.833-17.558c-0.289-0.149-0.633-0.149-0.922,0 c-6.46,3.351-11.74,8.111-15.527,13.707c-0.115,0.104-0.203,0.232-0.26,0.379c-0.783,1.186-1.494,2.41-2.139,3.665 c-0.102,0.117-0.167,0.257-0.206,0.411c-2.553,5.116-3.959,10.735-3.959,16.534c0,2.876,0.346,5.766,1.028,8.599 c-0.285,0.202-0.59,0.404-0.905,0.613C22.219,45.063,20.438,46.24,20.068,48.292z M31.473,15.555h29.491 c0.416,0.643,0.81,1.297,1.185,1.961H30.289C30.664,16.852,31.056,16.197,31.473,15.555z M46.217,2.131 c5.412,2.893,9.937,6.828,13.357,11.423H32.861C36.282,8.959,40.806,5.024,46.217,2.131z M24.894,45.689 c0.526-0.349,1.071-0.709,1.558-1.1c0.308-0.247,0.44-0.65,0.342-1.031c-0.748-2.877-1.127-5.82-1.127-8.748 c0-5.351,1.268-10.545,3.576-15.295h33.951c2.311,4.75,3.576,9.944,3.576,15.295c0,3.003-0.4,6.026-1.191,8.984 c-0.053,0.196-0.029,0.438,0.037,0.629c0.063,0.174,0.172,0.328,0.314,0.444c0.483,0.39,1.028,0.751,1.557,1.1 c1.354,0.896,2.627,1.741,2.844,2.948c0.722,3.976-4.344,19.478-7.006,26.976c-0.588-0.902-1.236-2.06-1.912-3.257 c-2.258-4.014-4.814-8.565-7.988-9.303c-0.074-0.021-0.15-0.024-0.229-0.024c-0.225,0-0.441,0.073-0.623,0.217 c-0.236,0.188-0.377,0.479-0.377,0.783v1.215H40.172v-1.482c0-0.305-0.14-0.594-0.378-0.783c-0.238-0.188-0.549-0.26-0.849-0.188 c-3.173,0.736-5.732,5.287-7.99,9.306c-0.674,1.194-1.324,2.353-1.913,3.256c-2.661-7.5-7.723-23.002-7.006-26.978 C22.255,47.438,23.537,46.588,24.894,45.689z"></path>
        <path d="M46.291,72c-2.354,0-6.022,0.215-7.229,1.48c-0.172,0.059-0.327,0.162-0.446,0.307c-0.19,0.229-0.27,0.527-0.217,0.818 c0.756,4.168,3.558,17.77,7.891,17.77c4.332,0,7.135-13.602,7.893-17.77c0.054-0.293-0.024-0.592-0.219-0.818 c-0.117-0.145-0.273-0.248-0.445-0.307C52.311,72.214,48.642,72,46.291,72z M46.291,90.373c-1.553,0-4.209-7.113-5.79-15.396 c0.04-0.063,0.073-0.127,0.099-0.195c0.521-0.301,2.513-0.776,5.688-0.776c3.176,0,5.168,0.479,5.688,0.776 c0.022,0.068,0.062,0.138,0.102,0.195C50.499,83.257,47.842,90.373,46.291,90.373z"></path>
      </svg>
      <h4 class="text-[18px] font-semibold leading-none">Free Launch</h4>
    </div>

    <p class="mt-5 text-4xl font-semibold leading-none tracking-[-0.04em] text-gray-950">$0</p>

    <ul class="mt-6 space-y-3 text-[13px] text-gray-500">
      <li
        v-for="(feature, index) in normalizedFeatures"
        :key="index"
        class="flex items-start gap-2.5"
      >
        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-500 text-white">
          <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.312a1 1 0 0 1-1.42 0L3.29 9.268a1 1 0 0 1 1.414-1.415l4.046 4.047 6.54-6.604a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
          </svg>
        </span>

        <div class="min-w-0">
          <p v-if="isBadgeFeature(feature)" class="leading-5 text-gray-500">
            <span class="inline-flex flex-nowrap items-baseline gap-1 whitespace-nowrap">
              <span>{{ feature.textBefore }}</span>
            <button
              type="button"
              class="inline text-left align-baseline font-medium text-primary-700 underline underline-offset-4 transition hover:text-primary-800"
              @click.stop="$emit('open-badge-modal')"
            >
              {{ feature.linkText }}
            </button>
            </span>
          </p>
          <p v-else class="leading-5 text-gray-500">{{ feature.text }}</p>

          <ol v-if="feature.children.length" class="mt-2 space-y-1 pl-7 text-[12px] text-gray-500">
            <li v-for="(child, childIndex) in feature.children" :key="child">
              {{ childIndex + 1 }}. {{ child }}
            </li>
          </ol>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  features: {
    type: Array,
    default: () => [],
  },
  selected: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['select', 'open-badge-modal']);

const isBadgeFeature = (feature) => feature?.action === 'badge-modal';

const normalizedFeatures = computed(() => props.features.map((feature) => (
  typeof feature === 'string'
    ? { text: feature, children: [] }
    : {
        text: feature?.text || '',
        textBefore: feature?.textBefore || '',
        linkText: feature?.linkText || '',
        action: feature?.action || null,
        children: Array.isArray(feature?.children) ? feature.children : [],
      }
)));
</script>

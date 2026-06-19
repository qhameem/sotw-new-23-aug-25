<template>
  <section>
    <div class="space-y-6">
      <div>
        <div class="mb-1 flex items-center justify-between">
          <h4 class="text-xs font-bold text-gray-900">Tech Stack <span class="ml-1 text-xs font-normal text-gray-400">(Max 5)</span></h4>
        </div>
        <div class="mb-2 text-[11px] text-gray-500">Which technologies were used to build your product?</div>

        <div class="relative mb-3">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input
            v-model="techSearch"
            type="text"
            placeholder="Search technologies..."
            class="block w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-xs placeholder-gray-400 transition-all focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/20"
            :class="{ 'pr-36': showAddTechStackButton }"
          >
          <button
            v-if="showAddTechStackButton"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-xs font-medium text-purple-600 transition-colors hover:text-purple-800"
            @click="addCustomTechStackFromSearch"
          >
            + Add "{{ techSearch.trim() }}"
          </button>
        </div>

        <div class="custom-scrollbar flex max-h-48 flex-wrap gap-2 overflow-y-auto p-1">
          <button
            v-for="tech in filteredTechStacks"
            :key="tech.id"
            type="button"
            class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-medium transition-all duration-200"
            :class="isSelectedTechStack(tech.id)
              ? 'border-sky-500 bg-sky-50 text-sky-700 shadow-sm'
              : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
            @click="toggleTechStack(tech.id)"
          >
            {{ tech.name }}
            <svg v-if="isSelectedTechStack(tech.id)" class="ml-1.5 h-3 w-3 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
          <div v-if="filteredTechStacks.length === 0 && !showAddTechStackButton" class="w-full py-4 text-center text-xs italic text-gray-400">
            No technologies found matching "{{ techSearch }}"
          </div>
        </div>

        <div v-if="modelValue.tech_stack_custom && modelValue.tech_stack_custom.length > 0" class="mt-2 flex flex-wrap gap-2">
          <span
            v-for="customTech in modelValue.tech_stack_custom"
            :key="customTech.id"
            class="inline-flex items-center rounded-full border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-700"
          >
            {{ customTech.name }} (pending)
            <button
              type="button"
              class="ml-2 text-purple-500 hover:text-purple-700"
              @click="removeCustomTechStack(customTech.id)"
            >
              &times;
            </button>
          </span>
        </div>
      </div>

      <div>
        <h4 class="mb-3 text-md font-medium text-gray-700">Product Sale</h4>
        <div class="flex items-center">
          <input
            id="sell-product"
            type="checkbox"
            :checked="modelValue.sell_product || false"
            class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-sky-400"
            @change="updateField('sell_product', $event.target.checked)"
          >
          <label for="sell-product" class="ml-2 block text-sm text-gray-900">I am looking to sell this product</label>
        </div>

        <div v-if="modelValue.sell_product" class="mt-3 ml-6">
          <label for="asking-price" class="mb-2 block text-sm font-semibold text-gray-700">Asking Price (USD)</label>
          <input
            id="asking-price"
            type="number"
            :value="modelValue.asking_price || ''"
            min="0"
            step="0.01"
            placeholder="Enter price in USD"
            class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-600 shadow-sm placeholder-gray-400 focus:border-sky-400 focus:outline-none focus:ring-sky-400 sm:text-sm"
            @input="updateField('asking_price', $event.target.value)"
          >
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  allTechStacks: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

const techSearch = ref('');

const updateField = (field, value) => {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
};

const showAddTechStackButton = computed(() => {
  const search = techSearch.value.trim();

  if (!search) {
    return false;
  }

  if (props.modelValue.tech_stack_custom?.some((techStack) => techStack.name.toLowerCase() === search.toLowerCase())) {
    return false;
  }

  if ((props.modelValue.tech_stack_custom?.length || 0) >= 3) {
    return false;
  }

  return !props.allTechStacks.some((techStack) => techStack.name.toLowerCase() === search.toLowerCase());
});

const filteredTechStacks = computed(() => {
  if (!techSearch.value.trim()) {
    return props.allTechStacks;
  }

  const existingCustomTechStacks = props.modelValue.tech_stack_custom?.map((techStack) => techStack.name.toLowerCase()) || [];
  const searchTerm = techSearch.value.toLowerCase();

  return props.allTechStacks.filter((techStack) => (
    techStack.name.toLowerCase().includes(searchTerm)
      && !existingCustomTechStacks.includes(techStack.name.toLowerCase())
  ));
});

const isSelectedTechStack = (id) => props.modelValue.tech_stack?.includes(id);

const toggleTechStack = (id) => {
  const current = Array.isArray(props.modelValue.tech_stack) ? [...props.modelValue.tech_stack] : [];
  const index = current.indexOf(id);

  if (index === -1) {
    if (current.length < 5) {
      current.push(id);
    }
  } else {
    current.splice(index, 1);
  }

  updateField('tech_stack', current);
};

const addCustomTechStackFromSearch = () => {
  const name = techSearch.value.trim();

  if (!name || (props.modelValue.tech_stack_custom?.length || 0) >= 3) {
    return;
  }

  const updatedCustomTechStacks = [
    ...(props.modelValue.tech_stack_custom || []),
    {
      id: `custom-${Date.now()}`,
      name,
      is_custom: true,
    },
  ];

  updateField('tech_stack_custom', updatedCustomTechStacks);
  techSearch.value = '';
};

const removeCustomTechStack = (customTechStackId) => {
  const updatedCustomTechStacks = (props.modelValue.tech_stack_custom || []).filter((techStack) => techStack.id !== customTechStackId);
  updateField('tech_stack_custom', updatedCustomTechStacks);
};
</script>

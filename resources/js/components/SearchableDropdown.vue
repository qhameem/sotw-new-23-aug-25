<template>
  <div class="relative" ref="dropdown">
    <div class="flex justify-between items-center mb-1">
      <label class="block text-sm font-semibold text-gray-700">{{ label }}</label>
      <span v-if="max && max !== Infinity" class="text-sm text-gray-500">{{ selectedItems.length }}/{{ max }}</span>
    </div>
    <slot name="description"></slot>
    <div class="relative">
      <div class="flex items-center px-2 py-1 border border-gray-300 rounded-md bg-white focus-within:ring-1 focus-within:ring-sky-400 focus-within:border-sky-400">
        <div :class="[selectedItems.length > 2 ? 'flex flex-wrap' : 'flex', 'gap-2']">
          <div v-for="item in selectedItems" :key="item.id" class="flex items-center bg-sky-100 text-sky-700 text-xs font-light px-2 py-1 rounded-lg">
            <span class="whitespace-nowrap">{{ item.name }}</span>
            <button @click="removeItem(item)" class="ml-2 text-gray-500 hover:text-gray-700 focus:outline-none">
              <svg class="h-3 w-3 text-sky-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>
        </div>
        <input
          ref="inputField"
          type="text"
          v-model="searchTerm"
          @focus="isOpen = true"
          @keydown.down.prevent="moveDown"
          @keydown.up.prevent="moveUp"
          @keydown.enter.prevent="selectItem(highlightedIndex)"
          @input="isOpen = true"
          class="flex-grow bg-transparent text-sm focus:outline-none ml-2 ring-0 shadow-none focus:ring-0 focus:shadow-none"
          style="outline: none; border: none;"
          :placeholder="selectedItems.length === 0 ? placeholder : ''"
        />
      </div>
      <ul v-if="isOpen && filteredItems.length" class="absolute z-10 w-full bg-white border border-gray-300 text-xs text-gray-600 rounded-md mt-1 max-h-60 overflow-y-auto shadow-lg">
        <li
          v-for="(item, index) in filteredItems"
          :key="item.id"
          @click.stop="selectItem(index)"
          :class="['p-2 cursor-pointer hover:bg-sky-100', { 'bg-sky-200': highlightedIndex === index }]"
        >
          {{ item.name }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
  label: String,
  items: Array,
  modelValue: Array,
  placeholder: String,
  min: {
    type: Number,
    default: 0,
  },
  max: {
    type: Number,
    default: Infinity,
  },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchTerm = ref('');
const selectedItems = ref([]);
const highlightedIndex = ref(-1);
const dropdown = ref(null);
const inputField = ref(null);

const filteredItems = computed(() => {
  if (!searchTerm.value) {
    return props.items.filter(item => !selectedItems.value.some(selected => selected.id === item.id));
  }
  return props.items.filter(item =>
    item.name.toLowerCase().includes(searchTerm.value.toLowerCase()) &&
    !selectedItems.value.some(selected => selected.id === item.id)
  );
});

watch(() => [props.modelValue, props.items], ([newModelValue, newItems]) => {
  selectedItems.value = newItems.filter(item => newModelValue.includes(item.id));
}, { immediate: true, deep: true });

function selectItem(index) {
  if (index < 0 || index >= filteredItems.value.length) return;

  if (props.max && selectedItems.value.length >= props.max) {
    // Optional: Provide feedback that the limit is reached
    return;
  }

  const item = filteredItems.value[index];
  if (!selectedItems.value.some(selected => selected.id === item.id)) {
    selectedItems.value.push(item);
    emit('update:modelValue', selectedItems.value.map(i => i.id));
    searchTerm.value = '';
    highlightedIndex.value = -1;
    if (inputField.value) {
      inputField.value.focus();
    }
    nextTick(() => {
      isOpen.value = true;
    });
  }
}

function removeItem(itemToRemove) {
  selectedItems.value = selectedItems.value.filter(item => item.id !== itemToRemove.id);
  emit('update:modelValue', selectedItems.value.map(i => i.id));
}

function moveDown() {
  if (highlightedIndex.value < filteredItems.value.length - 1) {
    highlightedIndex.value++;
  }
}

function moveUp() {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--;
  }
}

function handleClickOutside(event) {
  if (dropdown.value && !dropdown.value.contains(event.target)) {
    isOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
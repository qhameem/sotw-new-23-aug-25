<template>
  <div class="relative">
    <button 
      id="list-switcher-button" 
      class="text-2xl font-bold text-gray-800 focus:outline-none flex items-center"
      @click="toggleListDropdown"
    >
      <span id="active-list-title">{{ activeList ? activeList.title : '' }}</span>
      <svg class="w-6 h-6 ml-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>
    <div 
      id="list-dropdown" 
      class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10" 
      :class="{ hidden: !showListDropdown }"
    >
      <div 
        v-for="list in lists" 
        :key="list.id" 
        class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
      >
        <a 
          href="#" 
          @click.prevent="switchList(list.id)"
          class="flex-grow"
        >
          {{ list.title }}
        </a>
        <button 
          @click="deleteList(list.id)"
          class="ml-2"
        >
          <svg class="w-4 h-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 0-1 1v3M4 7h16"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ListDropdown',
  props: {
    lists: {
      type: Array,
      required: true
    },
    activeList: {
      type: Object,
      default: null
    },
    showListDropdown: {
      type: Boolean,
      required: true
    }
  },
  methods: {
    toggleListDropdown() {
      this.$emit('toggle-dropdown');
    },
    switchList(listId) {
      this.$emit('switch-list', listId);
    },
    deleteList(listId) {
      this.$emit('delete-list', listId);
    }
  }
}
</script>
<template>
  <div class="relative">
    <button 
      @click="toggleDropdown" 
      class="text-2xl font-bold text-gray-80 focus:outline-none flex items-center"
    >
      <span>{{ activeList ? activeList.title : 'Select List' }}</span>
      <svg 
        class="w-6 h-6 ml-2 text-gray-50 transition-transform duration-200" 
        :class="{ 'rotate-180': showDropdown }"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24" 
        xmlns="http://www.w3.org/2000/svg"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M19 9l-7 7-7-7"
        ></path>
      </svg>
    </button>
    
    <!-- List dropdown -->
    <div 
      v-if="showDropdown"
      class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10 border border-gray-200"
    >
      <div 
        v-for="list in lists" 
        :key="list.id"
        class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
        @click="selectList(list.id)"
      >
        <span>{{ list.title }}</span>
        <button 
          @click.stop="deleteList(list.id)"
          class="ml-2 text-gray-40 hover:text-red-500"
        >
          <svg 
            class="w-4 h-4" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 24 24" 
            xmlns="http://www.w3.org/2000/svg"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1 -1.995 -1.858L5 7m5 4v6m4 -6v6m1 -10V4a1 1 0 0 0 -1 -1h-4a1 1 0 0 0 -1 1v3M4 7h16"
            ></path>
          </svg>
        </button>
      </div>
      
      <!-- New list creation -->
      <div v-if="showCreateInput" class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-2">
          <input 
            v-model="newListTitle" 
            type="text" 
            placeholder="New list name" 
            class="flex-grow px-2 py-1 border border-gray-30 rounded-md focus:outline-none focus:ring-1 focus:ring-sky-500 text-sm"
            @keyup.enter="createNewList"
            ref="newListInputRef"
          >
          <button 
            @click="createNewList"
            class="p-2 text-green-500 hover:bg-green-50 rounded-full"
          >
            <svg 
              class="w-5 h-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 24 24" 
              xmlns="http://www.w3.org/2000/svg"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M5 13l4 4L19 7"
              ></path>
            </svg>
          </button>
          <button 
            @click="cancelCreateList"
            class="p-2 text-red-500 hover:bg-red-50 rounded-full"
          >
            <svg 
              class="w-5 h-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24" 
              xmlns="http://www.w3.org/2000/svg"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M6 18L18 6M6 6l12 12"
              ></path>
            </svg>
          </button>
        </div>
      </div>
      
      <div 
        v-if="!showCreateInput"
        @click="showCreateInput = true"
        class="px-4 py-2 text-sm text-sky-500 hover:bg-sky-50 cursor-pointer flex items-center"
      >
        <span class="mr-2">+</span> New List
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
    baseUrl: {
      type: String,
      required: true
    },
    csrfToken: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      showDropdown: false,
      showCreateInput: false,
      newListTitle: '',
      editInputRefs: {}
    }
 },
 methods: {
    async apiCall(url, options = {}) {
      const response = await fetch(url, {
        method: options.method || 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'Accept': 'application/json',
          ...options.headers
        },
        body: options.body ? JSON.stringify(options.body) : undefined
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return response.json();
    },
    
    toggleDropdown() {
      this.showDropdown = !this.showDropdown;
      this.$emit('toggle-dropdown', this.showDropdown);
    },
    
    selectList(listId) {
      this.showDropdown = false;
      this.$emit('select-list', listId);
    },
    
    async createNewList() {
      if (!this.newListTitle.trim()) return;
      
      try {
        const newList = await this.apiCall(this.baseUrl, {
          method: 'POST',
          body: { title: this.newListTitle }
        });
        
        this.$emit('add-list', newList);
        this.newListTitle = '';
        this.showCreateInput = false;
      } catch (error) {
        console.error('Failed to create new list:', error);
        alert('Could not create the new list.');
      }
    },
    
    cancelCreateList() {
      this.newListTitle = '';
      this.showCreateInput = false;
    },
    
    async deleteList(listId) {
      if (!confirm('Are you sure you want to delete this list? This action cannot be undone.')) {
        return;
      }
      
      try {
        await this.apiCall(`${this.baseUrl}/${listId}`, {
          method: 'DELETE'
        });
        
        this.$emit('delete-list', listId);
      } catch (error) {
        console.error('Failed to delete list:', error);
        alert('Could not delete the list.');
      }
    },
    
    setEditInputRef(el, id) {
      if (el) {
        this.editInputRefs[id] = el;
      }
    }
  },
  
  mounted() {
    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
      const dropdownElement = this.$el;
      if (!dropdownElement.contains(event.target)) {
        this.showDropdown = false;
        this.showCreateInput = false;
        this.$emit('toggle-dropdown', false);
      }
    });
  }
}
</script>
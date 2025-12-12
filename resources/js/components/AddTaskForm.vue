<template>
  <div class="flex flex-col sm:flex-row gap-2 mb-6">
    <input 
      v-model="newItemTitle" 
      type="text" 
      placeholder="Add new task" 
      class="flex-grow px-4 py-2 bg-transparent border-2 rounded-md border-gray-500 focus:outline-none focus:ring-0 focus:border-gray-800 placeholder-gray-500 placeholder:text-sm"
      @keyup.enter="addItem"
    >
    <button 
      @click="addItem"
      class="flex-grow border-2 border-black text-sm text-white bg-green-700 opacity-75 font-semibold w-full sm:w-1 flex items-center justify-center rounded-lg hover:bg-green-800 hover:opacity-75 transition-colors flex-shrink-0"
    >
      <span>Create Task</span>
    </button>
  </div>
</template>

<script>
export default {
  name: 'AddTaskForm',
  props: {
    activeListId: {
      type: [Number, String],
      required: true
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
      newItemTitle: ''
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
    
    async addItem() {
      if (!this.newItemTitle.trim() || !this.activeListId) return;
      
      try {
        const newItem = await this.apiCall(`${this.baseUrl}/${this.activeListId}/items`, {
          method: 'POST',
          body: { title: this.newItemTitle, color: 'emerald' } // Changed from 'green' to 'emerald' for normal priority
        });
        
        this.newItemTitle = '';
        this.$emit('add-item', newItem);
      } catch (error) {
        console.error('Failed to add item:', error);
      }
    }
  }
}
</script>
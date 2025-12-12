<template>
  <div class="flex flex-col sm:flex-row gap-2 mb-6">
    <input 
      type="text" 
      v-model="newItemTitle" 
      id="new-item-title-input" 
      placeholder="Add new task" 
      class="flex-grow px-1 py-2 bg-transparent border-0 border-b border-sky-300 focus:outline-none focus:ring-0 focus:border-sky-50 placeholder-gray-400 placeholder:text-sm"
      @keyup.enter="addItem"
      ref="newItemInput"
    >
    <button 
      @click="addItem" 
      id="add-item-button" 
      class="border border-sky-300 text-sky-50 font-bold w-full sm:w-10 h-10 flex items-center justify-center rounded-lg hover:bg-sky-50 transition-colors flex-shrink-0"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
      </svg>
    </button>
  </div>
</template>

<script>
export default {
  name: 'NewTaskInput',
  data() {
    return {
      newItemTitle: ''
    }
  },
  methods: {
    addItem() {
      if (this.newItemTitle.trim()) {
        this.$emit('add-item', this.newItemTitle.trim());
        this.newItemTitle = '';
      }
    },
    focus() {
      this.$nextTick(() => {
        this.$refs.newItemInput?.focus();
      });
    }
  }
}
</script>
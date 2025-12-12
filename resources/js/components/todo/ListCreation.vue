<template>
  <div id="new-list-container">
    <button 
      v-if="!showCreateListInput" 
      @click="showCreateListInput = true" 
      class="text-sky-500 px-4 py-2 rounded-lg hover:bg-sky-50 transition-colors text-sm font-medium"
    >
      &oplus; New List
    </button>
    <div v-else class="flex items-center gap-2">
      <input 
        type="text" 
        v-model="newListName" 
        placeholder="New list name" 
        class="flex-grow px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-sky-500 text-sm"
        @keyup.enter="createNewList"
        @keyup.esc="cancelCreateList"
        ref="newListInput"
      >
      <button 
        @click="createNewList" 
        class="p-2 text-green-500 hover:bg-green-50 rounded-full"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
      </button>
      <button 
        @click="cancelCreateList" 
        class="p-2 text-red-500 hover:bg-red-50 rounded-full"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ListCreation',
  props: {
    showCreateListInput: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      newListName: ''
    }
  },
  methods: {
    createNewList() {
      if (this.newListName.trim()) {
        this.$emit('create-list', this.newListName.trim());
        this.newListName = '';
      }
    },
    cancelCreateList() {
      this.newListName = '';
      this.$emit('cancel-create');
    }
  },
  watch: {
    showCreateListInput(newValue) {
      if (newValue) {
        this.$nextTick(() => {
          this.$refs.newListInput?.focus();
        });
      }
    }
  }
}
</script>
<template>
  <h1 
    id="list-title-container" 
    class="text-2xl font-bold text-gray-800 mb-6" 
    :class="{ hidden: !activeList }"
 >
    <span 
      v-if="!editingListTitle" 
      @click="startEditingListTitle" 
      id="list-title" 
      class="cursor-pointer"
    >
      {{ activeList ? activeList.title : '' }}
    </span>
    <input 
      v-else 
      type="text" 
      v-model="editedListTitle" 
      id="list-title-input" 
      class="text-2xl font-bold text-gray-800 border-b border-gray-300 focus:outline-none w-full"
      @keyup.enter="saveListTitle"
      @keyup.esc="cancelEditingListTitle"
      @blur="saveListTitle"
      ref="listTitleInput"
    >
  </h1>
</template>

<script>
export default {
  name: 'ListTitle',
  props: {
    activeList: {
      type: Object,
      default: null
    },
    editingListTitle: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      editedListTitle: ''
    }
  },
  methods: {
    startEditingListTitle() {
      this.editedListTitle = this.activeList ? this.activeList.title : '';
      this.$emit('start-editing');
      this.$nextTick(() => {
        this.$refs.listTitleInput?.focus();
        this.$refs.listTitleInput?.select();
      });
    },
    saveListTitle() {
      if (this.editedListTitle.trim() && this.editedListTitle !== (this.activeList ? this.activeList.title : '')) {
        this.$emit('save-title', this.editedListTitle.trim());
      } else {
        this.$emit('cancel-editing');
      }
    },
    cancelEditingListTitle() {
      this.$emit('cancel-editing');
    }
  },
  watch: {
    editingListTitle(newValue) {
      if (newValue && this.activeList) {
        this.editedListTitle = this.activeList.title;
      }
    }
  }
}
</script>
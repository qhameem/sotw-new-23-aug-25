<template>
  <div class="p-3 border border-gray-200 rounded-lg" :class="`bg-${getPriorityColorClass(item.color || 'emerald')}`">
    <div class="flex items-center justify-between">
      <div class="flex items-center flex-grow">
        <input 
          type="checkbox" 
          v-model="item.completed" 
          class="h-5 w-5 rounded border-gray-300 text-gray-800 focus:ring-gray-700"
          @change="updateItem"
        >
        <div class="ml-3 flex-grow">
          <span
            v-if="!item.editingTitle"
            class="item-title text-base font-medium text-gray-800 cursor-pointer"
            :class="{ 'line-through text-gray-500': item.completed }"
            @click="startEditingItemTitle"
          >
            {{ item.title }}
          </span>
          <input
            v-else
            type="text"
            v-model="item.editedTitle"
            class="item-title-input text-base font-medium text-gray-800 border-b border-gray-300 focus:outline-none w-full"
            @keyup.enter="saveItemTitle"
            @keyup.esc="cancelEditingItemTitle"
            @blur="saveItemTitle"
            ref="itemTitleInput"
          >
        </div>
        <button 
          @click="deleteItem" 
          class="text-gray-400 hover:text-gray-600 ml-2 flex-shrink-0"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div> <!-- Closing tag for line 3 div -->
    <div class="flex items-center justify-between pl-8 mt-1">
      <div class="relative">
        <a 
          href="#" 
          @click.prevent="toggleColorPalette"
          class="text-xs text-gray-500 hover:underline"
        >
          Priority {{ priorityNumbers[item.color || 'emerald'] }}
        </a>
        <div 
          class="item-color-palette absolute z-10 mt-2 p-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg" 
          :class="{ hidden: !item.showColorPalette }"
        >
          <div 
            v-for="[color, priority] in Object.entries(priorityColors)" 
            :key="color"
            class="color-option p-1 cursor-pointer flex items-center gap-2" 
            @click="changeItemColor(color)"
          >
            <span class="block w-5 h-5 rounded-full" :class="`bg-${getPriorityColorClass(color)} hover:ring-2 hover:ring-offset-1 hover:ring-${color}-700`"></span>
            <span class="text-xs text-gray-600">{{ priority }}</span>
          </div>
        </div>
      </div> <!-- Closing tag for line 41 div -->
      <div class="relative">
        <a
          href="#"
          @click.prevent="toggleDeadlinePicker"
          :class="getDeadlineClass"
        >
          {{ formatDeadline(item.deadline) }}
        </a>
        <input
          :ref="el => setDeadlineInputRef(el, item.id)"
          v-model="item.editedDeadline"
          type="text"
          class="item-deadline-input absolute right-0 top-full mt-2 z-10 bg-white border border-gray-300 rounded-md text-sm focus:outline-none p-2 cursor-pointer"
          v-show="item.showDeadlinePicker"
          readonly
        >
      </div>
    </div>
  </div>
</template>

<script>
import { PRIORITY_COLORS, PRIORITY_NAMES, PRIORITY_NUMBERS } from '../../constants/priorityColors';
import flatpickr from 'flatpickr';

export default {
  name: 'TaskItem',
  props: {
    item: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      deadlineInputRefs: {},
      flatpickrInstance: null
    }
  },
  computed: {
    priorityColors() {
      return PRIORITY_COLORS;
    },
    priorityNumbers() {
      return PRIORITY_NUMBERS;
    },
    getDeadlineClass() {
      if (!this.item.deadline) return 'text-gray-500 hover:underline';
      
      const deadlineDate = new Date(this.item.deadline);
      const now = new Date();
      
      if (deadlineDate < now) {
        return 'text-red-500 hover:underline';
      }
      
      return 'text-gray-500 hover:underline';
    }
 },
  methods: {
    updateItem() {
      this.$emit('update-item', this.item);
    },
    deleteItem() {
      this.$emit('delete-item', this.item.id);
    },
    startEditingItemTitle() {
      this.item.editedTitle = this.item.title;
      this.item.editingTitle = true;
      this.$nextTick(() => {
        if (this.$refs.itemTitleInput) {
          this.$refs.itemTitleInput.focus();
          this.$refs.itemTitleInput.select();
        }
      });
    },
    saveItemTitle() {
      if (!this.item.editedTitle.trim() || this.item.editedTitle === this.item.title) {
        this.item.editingTitle = false;
        return;
      }
      
      this.$emit('save-item-title', this.item);
      this.item.editingTitle = false;
    },
    cancelEditingItemTitle() {
      this.item.editingTitle = false;
    },
    toggleColorPalette() {
      this.$emit('toggle-color-palette', this.item);
    },
    changeItemColor(color) {
      this.$emit('change-item-color', { item: this.item, color });
      this.item.showColorPalette = false;
    },
    toggleDeadlinePicker() {
      console.log('TaskItem toggleDeadlinePicker called, showDeadlinePicker was:', this.item.showDeadlinePicker);
      if (!this.item.showDeadlinePicker) {
        // Opening the picker
        this.$emit('toggle-deadline-picker', this.item);
        this.item.showDeadlinePicker = true;
        this.item.editedDeadline = this.item.deadline ? new Date(this.item.deadline).toISOString().slice(0, 16) : '';
        console.log('TaskItem deadline picker opened, editedDeadline set to:', this.item.editedDeadline);
        
        // Initialize flatpickr after the element is rendered
        this.$nextTick(() => {
          this.openFlatpickr();
        });
      } else {
        // Closing the picker - save the deadline
        this.updateItemDeadlineAndClose();
      }
    },
    updateItemDeadline() {
      this.$emit('update-item-deadline', this.item);
      // Don't close the picker here since it's called from flatpickr onChange
    },
    updateItemDeadlineAndClose() {
      this.$emit('update-item-deadline', this.item);
      this.item.showDeadlinePicker = false;
    },
    hideDeadlinePicker() {
      this.item.showDeadlinePicker = false;
    },
    setDeadlineInputRef(el, id) {
      if (el) {
        this.deadlineInputRefs[id] = el;
      }
    },
    openFlatpickr() {
      console.log('TaskItem openFlatpickr called');
      console.log('deadlineInputRefs for item id', this.item.id + ':', this.deadlineInputRefs[this.item.id]);
      console.log('showDeadlinePicker:', this.item.showDeadlinePicker);
      console.log('item.editedDeadline:', this.item.editedDeadline);
      
      // Don't use $nextTick here since we're already called from $nextTick in toggleDeadlinePicker
      if (this.flatpickrInstance) {
        console.log('Destroying existing flatpickr instance in TaskItem');
        this.flatpickrInstance.destroy();
      }
      
      // Format the current deadline for flatpickr
      let defaultDate = null;
      if (this.item.editedDeadline) {
        defaultDate = new Date(this.item.editedDeadline);
      }
      
      if (this.deadlineInputRefs[this.item.id]) {
        console.log('Creating new flatpickr instance for TaskItem');
        this.flatpickrInstance = flatpickr(this.deadlineInputRefs[this.item.id], {
          enableTime: true,
          dateFormat: "Y-m-d H:i",
          time_24hr: false,
          defaultDate: defaultDate,
          onChange: (selectedDates, dateStr, instance) => {
            console.log('TaskItem flatpickr onChange called, dateStr:', dateStr);
            this.item.editedDeadline = dateStr;
            // Update the item deadline when date is selected
            this.updateItemDeadline();
          }
        });
        
        // Open the flatpickr
        console.log('Opening flatpickr instance in TaskItem');
        this.flatpickrInstance.open();
      } else {
        console.error('Could not find deadlineInputRef for item id:', this.item.id);
        // Try again after a small delay if the ref isn't available yet
        setTimeout(() => {
          if (this.deadlineInputRefs[this.item.id]) {
            this.openFlatpickr();
          } else {
            console.error('Still could not find deadlineInputRef after delay for item id:', this.item.id);
          }
        }, 100);
      }
    },
    formatDeadline(deadline) {
      if (!deadline) return 'Set Deadline';
      
      const deadlineDate = new Date(deadline);
      return deadlineDate.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });
    },
    
    getPriorityColorClass(color) {
      // Return the appropriate color class from PRIORITY_COLORS
      return PRIORITY_COLORS[color] || 'emerald-50';
    }
  },
  
  beforeUnmount() {
    // Clean up flatpickr instance when component is destroyed
    if (this.flatpickrInstance) {
      this.flatpickrInstance.destroy();
    }
  }
}
</script>
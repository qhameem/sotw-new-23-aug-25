<template>
  <div class="p-3 border list-item rounded-lg">
    <div class="flex items-center justify-between">
      <div class="flex items-center flex-grow">
        <input 
          type="checkbox" 
          v-model="localItem.completed"
          @change="updateItem"
          class="h-5 w-5 rounded border-gray-300 text-black focus:ring-gray-700"
        >
        <div class="ml-3 flex-grow">
          <span
            class="item-title text-base font-medium text-black cursor-pointer"
            :class="{ 'line-through text-gray-500': localItem.completed }"
            @dblclick="startEditing"
            v-if="!localItem.editing"
          >
            {{ localItem.title }}
          </span>
          <input
            v-if="localItem.editing"
            v-model="localItem.editingTitle"
            @blur="finishEditing"
            @keyup.enter="finishEditing"
            @keyup.esc="cancelEditing"
            class="item-title-input text-base font-medium text-black border-b border-gray-300 focus:outline-none w-full"
            :ref="el => setEditInputRef(el, localItem.id)"
            v-focus="localItem.editing"
          >
        </div>
        <button 
          @click="deleteItem(localItem.id)"
          class="text-gray-400 hover:text-gray-600 ml-2 flex-shrink-0"
        >
          <svg 
            xmlns="http://www.w3.org/2000/svg" 
            class="h-5 w-5" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M6 18L18 6M6 6l12 12" 
            />
          </svg>
        </button>
      </div>
    </div>

    <div class="flex items-center justify-between pl-8 mt-1">
      <div class="relative">
        <a
          href="#"
          @click.prevent="toggleColorPicker"
          class="text-xs priority-tag rounded px-1 text-white hover:underline"
          :class="`bg-${resolvedColorClass}`"
        >
          {{ priorityNames[resolvedColor] || 'Priority' }}
        </a>
        <div 
          v-if="showColorPicker"
          class="item-color-palette absolute z-10 mt-2 p-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg flex flex-col gap-1"
        >
          <div
            v-for="(color, name) in priorityColors"
            :key="name"
            class="color-option p-1 cursor-pointer flex items-center gap-2 hover:bg-gray-50"
            @click="updateItemColor(name)"
          >
            <span
              class="block w-5 h-5 rounded-full"
              :class="`bg-${color} hover:ring-2 hover:ring-offset-1 hover:ring-${name}-600`"
            ></span>
            <span class="text-xs text-gray-600">{{ priorityNames[name] || name }}</span>
          </div>
        </div>
      </div>

      <div class="relative">
        <a
          href="#"
          @click.prevent="toggleDeadlinePicker"
          class="text-xs"
          :class="getDeadlineClass"
        >
          {{ formatDeadline(localItem.deadline) }}
        </a>
        <input
          ref="deadlineInput"
          v-show="showDeadlinePicker"
          type="text"
          v-model="localItem.editingDeadline"
          class="item-deadline-input absolute right-0 top-full mt-2 z-10 bg-white border border-gray-300 rounded-md text-sm focus:outline-none p-2 cursor-pointer"
          readonly
        >
      </div>
    </div>
  </div>
</template>

<script>
import { PRIORITY_COLORS, PRIORITY_NAMES, PRIORITY_NUMBERS } from '../constants/priorityColors';
import flatpickr from 'flatpickr';

export default {
  name: 'TodoItem',
  props: {
    item: {
      type: Object,
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
      localItem: { ...this.item },
      showColorPicker: false,
      showDeadlinePicker: false,
      priorityNames: PRIORITY_NAMES,
      priorityColors: PRIORITY_COLORS, // Map of color name to Tailwind color class
      editInputRefs: {},
      flatpickrInstance: null
    }
  },
  computed: {
     resolvedColor() {
       // If localItem.color is already a valid color name, use it
       if (this.localItem.color && this.priorityNames[this.localItem.color]) {
         return this.localItem.color;
       }
       
       // If it's a number, map it to the corresponding color name
       const colorNum = Number(this.localItem.color);
       if (!isNaN(colorNum)) {
         const colorName = Object.keys(this.priorityColors).find(key => this.priorityColors[key] === colorNum);
         return colorName || 'emerald'; // Default to emerald for normal priority
       }
       
       // If it's a string that's not in our priority names, try to match partial strings
       if (typeof this.localItem.color === 'string') {
         const lowerColor = this.localItem.color.toLowerCase();
         if (lowerColor.includes('rose') || lowerColor.includes('urgent')) return 'rose';
         if (lowerColor.includes('amber') || lowerColor.includes('high')) return 'amber';
         if (lowerColor.includes('emerald') || lowerColor.includes('normal') || lowerColor.includes('low')) return 'emerald';
       }
       
       // Default to emerald if no match found
       return 'emerald';
     },
     
     resolvedColorClass() {
       // Get the actual Tailwind class from PRIORITY_COLORS based on resolvedColor
       return this.priorityColors[this.resolvedColor] || 'emerald-500';
     }
  },
  watch: {
     item: {
       handler(newItem) {
         this.localItem = { ...newItem };
       },
       deep: true
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
    
    async updateItem() {
      try {
        await this.apiCall(`${this.baseUrl}/items/${this.localItem.id}`, {
          method: 'PUT',
          body: { completed: this.localItem.completed }
        });
        
        this.$emit('update:item', { ...this.localItem });
      } catch (error) {
        console.error('Failed to update item:', error);
        this.localItem.completed = !this.localItem.completed; // Revert on failure
      }
    },
    
    startEditing() {
      this.localItem.editing = true;
      this.localItem.editingTitle = this.localItem.title;
      this.$nextTick(() => {
        const inputRef = this.editInputRefs[this.localItem.id];
        if (inputRef) {
          inputRef.focus();
          inputRef.select();
        }
      });
    },
    
    async finishEditing() {
      if (!this.localItem.editingTitle.trim() || this.localItem.editingTitle === this.localItem.title) {
        this.localItem.editing = false;
        return;
      }
      
      try {
        await this.apiCall(`${this.baseUrl}/items/${this.localItem.id}`, {
          method: 'PUT',
          body: { title: this.localItem.editingTitle }
        });
        
        this.localItem.title = this.localItem.editingTitle;
        this.localItem.editing = false;
        this.$emit('update:item', { ...this.localItem });
      } catch (error) {
        console.error('Failed to update item title:', error);
        this.localItem.editing = false;
      }
    },
    
    cancelEditing() {
      this.localItem.editing = false;
    },
    
    async updateItemColor(color) {
      try {
        await this.apiCall(`${this.baseUrl}/items/${this.localItem.id}`, {
          method: 'PUT',
          body: { color }
        });
        
        this.localItem.color = color;
        this.showColorPicker = false;
        this.$emit('update:item', { ...this.localItem });
      } catch (error) {
        console.error('Failed to update item color:', error);
      }
    },
    
    async updateItemDeadline() {
      try {
        await this.apiCall(`${this.baseUrl}/items/${this.localItem.id}`, {
          method: 'PUT',
          body: { deadline: this.localItem.editingDeadline }
        });
        
        this.localItem.deadline = this.localItem.editingDeadline;
        // Don't close the picker here since it's called from flatpickr onChange
        this.$emit('update:item', { ...this.localItem });
      } catch (error) {
        console.error('Failed to update item deadline:', error);
      }
    },
    
    async updateItemDeadlineAndClose() {
      try {
        await this.apiCall(`${this.baseUrl}/items/${this.localItem.id}`, {
          method: 'PUT',
          body: { deadline: this.localItem.editingDeadline }
        });
        
        this.localItem.deadline = this.localItem.editingDeadline;
        this.showDeadlinePicker = false;
        this.$emit('update:item', { ...this.localItem });
      } catch (error) {
        console.error('Failed to update item deadline:', error);
      }
    },
    
    toggleColorPicker() {
      this.showColorPicker = !this.showColorPicker;
      if (this.showColorPicker) {
        this.showDeadlinePicker = false;
      }
      this.$emit('color-picker-toggle', this.localItem.id, this.showColorPicker);
    },
    
    toggleDeadlinePicker() {
      console.log('toggleDeadlinePicker called, showDeadlinePicker was:', this.showDeadlinePicker);
      if (!this.showDeadlinePicker) {
        // Opening the picker
        this.showDeadlinePicker = true;
        this.showColorPicker = false;
        // Set editing deadline to current deadline if it exists
        this.localItem.editingDeadline = this.localItem.deadline || '';
        console.log('Deadline picker opened, editingDeadline set to:', this.localItem.editingDeadline);
        
        // Initialize flatpickr after the element is rendered
        this.$nextTick(() => {
          this.openFlatpickr();
        });
      } else {
        // Closing the picker - save the deadline
        this.updateItemDeadlineAndClose();
      }
      this.$emit('deadline-picker-toggle', this.localItem.id, this.showDeadlinePicker);
    },
    
    async deleteItem(itemId) {
      this.$emit('delete:item', itemId);
    },
    
    setEditInputRef(el, id) {
      if (el) {
        this.editInputRefs[id] = el;
      }
    },
    
    openFlatpickr() {
      console.log('openFlatpickr called, $refs.deadlineInput:', this.$refs.deadlineInput);
      console.log('showDeadlinePicker:', this.showDeadlinePicker);
      console.log('localItem.editingDeadline:', this.localItem.editingDeadline);
      
      // Don't use $nextTick here since we're already called from $nextTick in toggleDeadlinePicker
      if (this.flatpickrInstance) {
        console.log('Destroying existing flatpickr instance');
        this.flatpickrInstance.destroy();
      }
      
      // Format the current deadline for flatpickr
      let defaultDate = null;
      if (this.localItem.editingDeadline) {
        defaultDate = new Date(this.localItem.editingDeadline);
      }
      
      if (this.$refs.deadlineInput) {
        console.log('Creating new flatpickr instance');
        this.flatpickrInstance = flatpickr(this.$refs.deadlineInput, {
          enableTime: true,
          dateFormat: "Y-m-d H:i",
          time_24hr: false,
          defaultDate: defaultDate,
          onChange: (selectedDates, dateStr, instance) => {
            console.log('Flatpickr onChange called, dateStr:', dateStr);
            this.localItem.editingDeadline = dateStr;
            // Update the item deadline when date is selected
            this.updateItemDeadline();
          }
        });
        
        // Open the flatpickr
        console.log('Opening flatpickr instance');
        this.flatpickrInstance.open();
      } else {
        console.error('Could not find deadlineInput ref to initialize flatpickr');
        // Try again after a small delay if the ref isn't available yet
        setTimeout(() => {
          if (this.$refs.deadlineInput) {
            this.openFlatpickr();
          } else {
            console.error('Still could not find deadlineInput ref after delay');
          }
        }, 100);
      }
    },
    
    formatDeadline(deadline) {
      if (!deadline) return 'Set Deadline';
      
      const date = new Date(deadline);
      return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });
    },
    
    isDeadlinePassed(deadline) {
      if (!deadline) return false;
      
      const deadlineDate = new Date(deadline);
      return deadlineDate < new Date();
    },
    
    getDeadlineClass() {
      if (!this.localItem.deadline) return 'text-gray-500 hover:underline';
      
      const deadlineDate = new Date(this.localItem.deadline);
      const now = new Date();
      
      if (deadlineDate < now) {
        return 'text-red-500 hover:underline';
      }
      
      return 'text-gray-500 hover:underline';
    }
  },
  
  mounted() {
    // Close pickers when clicking outside
    document.addEventListener('click', (event) => {
      const element = this.$el;
      if (!element.contains(event.target)) {
        if (this.showColorPicker) {
          this.showColorPicker = false;
          this.$emit('color-picker-toggle', this.localItem.id, false);
        }
        if (this.showDeadlinePicker) {
          this.showDeadlinePicker = false;
          this.$emit('deadline-picker-toggle', this.localItem.id, false);
        }
      }
    });
  },
  
  beforeUnmount() {
    // Clean up flatpickr instance when component is destroyed
    if (this.flatpickrInstance) {
      this.flatpickrInstance.destroy();
    }
  },
  
  directives: {
    focus: {
      updated(el, { value }) {
        if (value) {
          el.focus();
        }
      }
    }
  }
}
</script>

<style scoped>
.priority-tag, .list-item {
  --border-width: 1.5px;
  --border-color: #374151; /* gray-700 */
  --border-radius: 0.25rem; /* equivalent to Tailwind's rounded */
 transition: all 0.2s ease-in-out;
}

.priority-tag {
 --border-width: 1.5px;
  --border-color: #374151; /* gray-700 */
  border-radius: var(--border-radius, 0.25rem);
  position: relative;
 z-index: 1;
  color: white !important;
  border: var(--border-width, 1.5px) solid var(--border-color, #9ca3af);
 list-style-type: none;
}

.priority-tag.bg-rose-500 {
   background-color: #f43f5e !important;
   color: white !important;
}

.priority-tag.bg-amber-600 {
   background-color: #d97706 !important;
   color: white !important;
}

.priority-tag.bg-emerald-500 {
   background-color: #10b981 !important;
   color: white !important;
}

.list-item {
   border: var(--border-width, 1.5px) solid var(--border-color, #9ca3af);
   border-radius: var(--border-radius, 0.25rem);
   list-style-type: none;
}

.priority-tag.active {
  font-weight: 500;
}
</style>
<template>
  <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
    <div class="flex justify-between items-center mb-6">
      <div class="relative">
        <button 
          id="list-switcher-button" 
          class="text-2xl font-bold text-gray-800 focus:outline-none flex items-center"
          @click="toggleListDropdown"
        >
          <span id="active-list-title">{{ activeList ? activeList.title : '' }}</span>
          <svg class="w-6 h-6 ml-2 text-gray-50" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div 
          id="list-dropdown" 
          class="absolute left-0 mt-2 w-56 bg-white rounded-md z-10" 
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
      <div class="flex items-center gap-4">
        <a 
          :href="activeList ? `${baseUrl}/${activeList.id}/export` : '#'" 
          id="export-list-button" 
          class="text-gray-400 hover:text-sky-500" 
          title="Export to Excel"
          :class="{ hidden: !activeList }"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
        </a>
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
      </div>
    </div> <!-- Closing the flex justify-between items-center mb-6 div -->
    
     <h1
      id="list-title-container" 
      class="text-2xl font-bold text-gray-80 mb-6" 
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
        class="text-2xl font-bold text-gray-80 border-b border-gray-300 focus:outline-none w-full"
        @keyup.enter="saveListTitle"
        @keyup.esc="cancelEditingListTitle"
        @blur="saveListTitle"
        ref="listTitleInput"
      >
    </h1>

    <!-- Priority Filter Tags -->
    <div id="priority-filter-container" class="flex items-center gap-2 mb-4">
      <div 
        class="priority-tag" 
        :class="{ 
          'active': activePriorityFilter === null,
          'priority-tag-all': true 
        }" 
        data-priority="all"
        @click="setPriorityFilter(null)"
      >
        <span>All</span>
        <span class="priority-count">{{ totalItemsCount }}</span>
      </div>
      
      <div 
        v-for="(priority, key) in priorityNames" 
        :key="key"
        v-if="priorityCounts[key]"
        class="priority-tag" 
        :class="{ 
          'active': activePriorityFilter === key,
          [`priority-tag-${key}`]: true 
        }" 
        :data-priority="key"
        @click="setPriorityFilter(key)"
      >
        <span>{{ priority }}</span>
        <span class="priority-count">{{ priorityCounts[key] }}</span>
      </div>
    </div>

    <!-- Add New Task -->
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
        class="border border-sky-300 text-sky-500 font-bold w-full sm:w-10 h-10 flex items-center justify-center rounded-lg hover:bg-sky-50 transition-colors flex-shrink-0"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
      </button>
    </div>

    <!-- To-Do Lists Container -->
    <div id="todo-lists-container" class="space-y-2">
      <div 
        v-for="item in filteredItems" 
        :key="item.id"
        class="p-3 border border-gray-200 rounded-lg"
        :class="`bg-${getPriorityColorClass(item.color || 'emerald')}`"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center flex-grow">
            <input 
              type="checkbox" 
              v-model="item.completed" 
              class="h-5 w-5 rounded border-gray-30 text-gray-800 focus:ring-gray-700"
              @change="updateItem(item)"
            >
            <div class="ml-3 flex-grow">
              <span
                v-if="!item.editingTitle"
                class="item-title text-base font-medium text-gray-800 cursor-pointer"
                :class="{ 'line-through text-gray-500': item.completed }"
                @click="startEditingItemTitle(item)"
              >
                {{ item.title }}
              </span>
              <input
                v-else
                type="text"
                v-model="item.editedTitle"
                class="item-title-input text-base font-medium text-gray-800 border-b border-gray-300 focus:outline-none w-full"
                @keyup.enter="saveItemTitle(item)"
                @keyup.esc="cancelEditingItemTitle(item)"
                @blur="saveItemTitle(item)"
                ref="itemTitleInput"
              >
            </div>
          </div>
          <button 
            @click="deleteItem(item.id)" 
            class="text-gray-400 hover:text-gray-600 ml-2 flex-shrink-0"
          >
            <svg xmlns="http://www.w3.org/200/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="flex items-center justify-between pl-8 mt-1">
          <div class="relative">
            <a 
              href="#" 
              @click.prevent="toggleColorPalette(item)"
              class="text-xs text-gray-500 hover:underline"
            >
              Priority {{ priorityNumbers[item.color || 'emerald'] }}
            </a>
            <div 
              class="item-color-palette absolute z-10 mt-2 p-2 w-40 bg-white border border-gray-200 rounded-md" 
              :class="{ hidden: !item.showColorPalette }"
            >
              <div 
                v-for="[color, priority] in Object.entries(priorityColors)" 
                :key="color"
                class="color-option p-1 cursor-pointer flex items-center gap-2" 
                @click="changeItemColor(item, color)"
              >
                <span class="block w-5 h-5 rounded-full" :class="`bg-${getPriorityColorClass(color)} hover:ring-2 hover:ring-offset-1 hover:ring-${color.includes('amber') ? 'amber-700' : color.includes('rose') ? 'rose-700' : 'emerald-700'}`"></span>
                <span class="text-xs text-gray-600">{{ priority }}</span>
              </div>
            </div>
          </div>
          <div class="relative">
            <a
              href="#"
              @click.prevent="toggleDeadlinePicker(item)"
              :class="getDeadlineClass(item)"
            >
              {{ formatDeadline(item.deadline) }}
            </a>
            <input
              :ref="el => setDeadlineInputRef(el, item.id)"
              v-model="item.editedDeadline"
              type="text"
              class="item-deadline-input absolute right-0 top-full mt-2 z-10 bg-white border border-gray-300 rounded-md text-sm focus:outline-none p-2 cursor-pointer"
              :class="{ hidden: !item.showDeadlinePicker }"
              readonly
            >
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="mt-6 text-center"></div>
  </div>
</template>

<script>
import { PRIORITY_COLORS, PRIORITY_NAMES, PRIORITY_NUMBERS, PRIORITY_ORDER } from '../constants/priorityColors';
import flatpickr from 'flatpickr';

export default {
  name: 'TodoListApp',
  props: {
    initialLists: {
      type: Array,
      default: () => []
    },
    storeUrl: {
      type: String,
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
      lists: this.initialLists,
      activeListId: this.initialLists.length > 0 ? this.initialLists[0].id : null,
      activePriorityFilter: null,
      showListDropdown: false,
      showCreateListInput: false,
      newListName: '',
      newItemTitle: '',
      editingListTitle: false,
      editedListTitle: '',
      priorityNames: PRIORITY_NAMES,
      priorityColors: PRIORITY_COLORS, // Map of color name to Tailwind color class
      priorityNumbers: PRIORITY_NUMBERS,
      quotes: [
        "\"Doing what you love is the cornerstone of having abundance in your life.\" - Wayne Dyer",
        "\"The secret of getting ahead is getting started.\" - Mark Twain",
        "\"It's not the load that breaks you down, it's the way you carry it.\" - Lou Holtz",
        "\"The future depends on what you do today.\" - Mahatma Gandhi"
      ],
      deadlineInputRefs: {},
      flatpickrInstances: {}
    }
 },
  computed: {
    activeList() {
      return this.lists.find(list => list.id == this.activeListId) || null;
    },
    filteredItems() {
      if (!this.activeList || !this.activeList.items) return [];
      
      let sortedItems = [...this.activeList.items].sort((a, b) => {
        const priorityA = PRIORITY_ORDER[a.color || 'emerald'] || 9;
        const priorityB = PRIORITY_ORDER[b.color || 'emerald'] || 9;
        return priorityA - priorityB;
      });
      
      if (this.activePriorityFilter) {
        sortedItems = sortedItems.filter(item => (item.color || 'emerald') === this.activePriorityFilter);
      }
      
      return sortedItems;
    },
    priorityCounts() {
      if (!this.activeList || !this.activeList.items) return {};
      
      return this.activeList.items.reduce((acc, item) => {
        const priority = item.color || 'emerald';
        acc[priority] = (acc[priority] || 0) + 1;
        return acc;
      }, {});
    },
    totalItemsCount() {
      return this.activeList ? this.activeList.items.length : 0;
    }
  },
  methods: {
    async apiCall(url, options) {
      const response = await fetch(url, {
        ...options,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'Accept': 'application/json',
          ...options.headers
        }
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
          body: JSON.stringify({ 
            title: this.newItemTitle.trim(), 
            color: 'emerald' // Changed to emerald for normal priority
          })
        });
        
        const list = this.lists.find(l => l.id == this.activeListId);
        if (list) {
          newItem.editingTitle = false;
          newItem.editedTitle = newItem.title;
          newItem.showColorPalette = false;
          newItem.showDeadlinePicker = false;
          newItem.editedDeadline = newItem.deadline ? new Date(newItem.deadline).toISOString().slice(0, 16) : '';
          list.items.push(newItem);
        }
        
        this.newItemTitle = '';
        this.$nextTick(() => {
          this.$refs.newItemInput?.focus();
        });
      } catch (error) {
        console.error('Failed to add item:', error);
      }
    },
    
    async updateItem(item) {
      try {
        await this.apiCall(`${this.baseUrl}/items/${item.id}`, {
          method: 'PUT',
          body: JSON.stringify({ 
            completed: item.completed 
          })
        });
      } catch (error) {
        console.error('Failed to update item:', error);
        // Revert the change if the API call fails
        item.completed = !item.completed;
      }
    },
    
    async updateItemDeadline(item) {
      try {
        await this.apiCall(`${this.baseUrl}/items/${item.id}`, {
          method: 'PUT',
          body: JSON.stringify({
            deadline: item.editedDeadline
          })
        });
        
        item.deadline = item.editedDeadline;
        // Don't close the picker here since it's called from flatpickr onChange
      } catch (error) {
        console.error('Failed to update item deadline:', error);
      }
    },
    
    async updateItemDeadlineAndClose(item) {
      try {
        await this.apiCall(`${this.baseUrl}/items/${item.id}`, {
          method: 'PUT',
          body: JSON.stringify({
            deadline: item.editedDeadline
          })
        });
        
        item.deadline = item.editedDeadline;
        item.showDeadlinePicker = false;
      } catch (error) {
        console.error('Failed to update item deadline:', error);
      }
    },
    
    async deleteItem(itemId) {
      if (!confirm('Are you sure you want to delete this task?')) return;
      
      try {
        await this.apiCall(`${this.baseUrl}/items/${itemId}`, {
          method: 'DELETE'
        });
        
        const list = this.lists.find(l => l.id == this.activeListId);
        if (list) {
          list.items = list.items.filter(item => item.id != itemId);
        }
      } catch (error) {
        console.error('Failed to delete item:', error);
      }
    },
    
    async changeItemColor(item, color) {
      try {
        await this.apiCall(`${this.baseUrl}/items/${item.id}`, {
          method: 'PUT',
          body: JSON.stringify({ 
            color: color 
          })
        });
        
        item.color = color;
        item.showColorPalette = false;
      } catch (error) {
        console.error('Failed to update item color:', error);
      }
    },
    
    async saveItemTitle(item) {
      if (!item.editedTitle.trim() || item.editedTitle === item.title) {
        item.editingTitle = false;
        return;
      }
      
      try {
        await this.apiCall(`${this.baseUrl}/items/${item.id}`, {
          method: 'PUT',
          body: JSON.stringify({ 
            title: item.editedTitle.trim() 
          })
        });
        
        item.title = item.editedTitle.trim();
        item.editingTitle = false;
      } catch (error) {
        console.error('Failed to update item title:', error);
        item.editingTitle = false;
      }
    },
    
    startEditingItemTitle(item) {
      item.editedTitle = item.title;
      item.editingTitle = true;
      this.$nextTick(() => {
        if (this.$refs.itemTitleInput) {
          const input = this.$refs.itemTitleInput.find(ref => ref.closest('.p-3') === event.target.closest('.p-3'));
          if (input) {
            input.focus();
            input.select();
          }
        }
      });
    },
    
    cancelEditingItemTitle(item) {
      item.editingTitle = false;
    },
    
    async saveListTitle() {
      if (!this.editedListTitle.trim() || this.editedListTitle === this.activeList.title) {
        this.editingListTitle = false;
        return;
      }
      
      try {
        await this.apiCall(`${this.baseUrl}/${this.activeListId}`, {
          method: 'PATCH',
          body: JSON.stringify({ 
            title: this.editedListTitle.trim() 
          })
        });
        
        this.activeList.title = this.editedListTitle.trim();
        this.editingListTitle = false;
      } catch (error) {
        console.error('Failed to update list title:', error);
        this.editingListTitle = false;
      }
    },
    
    startEditingListTitle() {
      this.editedListTitle = this.activeList.title;
      this.editingListTitle = true;
      this.$nextTick(() => {
        this.$refs.listTitleInput?.focus();
        this.$refs.listTitleInput?.select();
      });
    },
    
    cancelEditingListTitle() {
      this.editingListTitle = false;
    },
    
    async createNewList() {
      if (!this.newListName.trim()) return;
      
      try {
        const newList = await this.apiCall(this.storeUrl, {
          method: 'POST',
          body: JSON.stringify({ 
            title: this.newListName.trim() 
          })
        });
        
        newList.items = newList.items || [];
        this.lists.push(newList);
        this.activeListId = newList.id;
        this.showCreateListInput = false;
        this.newListName = '';
      } catch (error) {
        console.error('Failed to create new list:', error);
      }
    },
    
    cancelCreateList() {
      this.showCreateListInput = false;
      this.newListName = '';
    },
    
    async deleteList(listId) {
      if (!confirm('Are you sure you want to delete this list? This action cannot be undone.')) return;
      
      try {
        await this.apiCall(`${this.baseUrl}/${listId}`, {
          method: 'DELETE'
        });
        
        this.lists = this.lists.filter(list => list.id != listId);
        
        if (this.activeListId == listId) {
          this.activeListId = this.lists.length > 0 ? this.lists[0].id : null;
        }
        
        if (this.lists.length === 0) {
          // Create a default list if all lists are deleted
          await this.createNewListDefault();
        }
      } catch (error) {
        console.error('Failed to delete list:', error);
      }
    },
    
    async createNewListDefault() {
      try {
        const newList = await this.apiCall(this.storeUrl, {
          method: 'POST',
          body: JSON.stringify({ 
            title: 'My To Do List' 
          })
        });
        
        newList.items = newList.items || [];
        this.lists.push(newList);
        this.activeListId = newList.id;
      } catch (error) {
        console.error('Failed to create initial list:', error);
      }
    },
    
    switchList(listId) {
      this.activeListId = listId;
    },
    
    toggleListDropdown() {
      this.showListDropdown = !this.showListDropdown;
    },
    
    setPriorityFilter(priority) {
      if (this.activePriorityFilter === priority) {
        this.activePriorityFilter = null; // Deselect if clicking the active filter
      } else {
        this.activePriorityFilter = priority;
      }
    },
    
    toggleColorPalette(item) {
      // Close all other color palettes first
      this.lists.forEach(list => {
        if (list.items) {
          list.items.forEach(i => {
            if (i !== item) i.showColorPalette = false;
          });
        }
      });
      
      // Toggle the current item's color palette
      item.showColorPalette = !item.showColorPalette;
    },
    
    toggleDeadlinePicker(item) {
      console.log('TodoListApp toggleDeadlinePicker called for item id:', item.id);
      // Close all other deadline pickers first
      this.lists.forEach(list => {
        if (list.items) {
          list.items.forEach(i => {
            if (i !== item) {
              i.showDeadlinePicker = false;
              // Close the flatpickr instance if it exists
              if (this.flatpickrInstances[i.id]) {
                this.flatpickrInstances[i.id].destroy();
                delete this.flatpickrInstances[i.id];
              }
            }
          });
        }
      });
      
      // Toggle the current item's deadline picker
      if (!item.showDeadlinePicker) {
        item.showDeadlinePicker = true;
        item.editedDeadline = item.deadline ? new Date(item.deadline).toISOString().slice(0, 16) : '';
        console.log('TodoListApp deadline picker opened for item id:', item.id, 'editedDeadline:', item.editedDeadline);
        
        // Initialize flatpickr after the element is rendered
        this.$nextTick(() => {
          this.openFlatpickr(item);
        });
      } else {
        // Close the picker and save the deadline
        this.updateItemDeadline(item);
        item.showDeadlinePicker = false;
      }
    },
    
    setDeadlineInputRef(el, id) {
      if (el) {
        this.deadlineInputRefs[id] = el;
      }
    },
    
    openFlatpickr(item) {
      console.log('TodoListApp openFlatpickr called for item id:', item.id);
      console.log('deadlineInputRefs for item id', item.id + ':', this.deadlineInputRefs[item.id]);
      console.log('showDeadlinePicker for item:', item.showDeadlinePicker);
      console.log('item.editedDeadline:', item.editedDeadline);
      
      // Don't use $nextTick here since we're already called from $nextTick in toggleDeadlinePicker
      // Destroy any existing instance for this item
      if (this.flatpickrInstances[item.id]) {
        console.log('Destroying existing flatpickr instance in TodoListApp for item id:', item.id);
        this.flatpickrInstances[item.id].destroy();
      }
      
      // Format the current deadline for flatpickr
      let defaultDate = null;
      if (item.editedDeadline) {
        defaultDate = new Date(item.editedDeadline);
      }
      
      if (this.deadlineInputRefs[item.id]) {
        console.log('Creating new flatpickr instance in TodoListApp for item id:', item.id);
        this.flatpickrInstances[item.id] = flatpickr(this.deadlineInputRefs[item.id], {
          enableTime: true,
          dateFormat: "Y-m-d H:i",
          time_24hr: false,
          defaultDate: defaultDate,
          onChange: (selectedDates, dateStr, instance) => {
            console.log('TodoListApp flatpickr onChange called for item id:', item.id, 'dateStr:', dateStr);
            item.editedDeadline = dateStr;
            // Update the item deadline when date is selected
            this.updateItemDeadline(item);
          }
        });
        
        // Open the flatpickr
        console.log('Opening flatpickr instance in TodoListApp for item id:', item.id);
        this.flatpickrInstances[item.id].open();
      } else {
        console.error('Could not find deadlineInputRef for item id:', item.id, 'in TodoListApp');
        // Try again after a small delay if the ref isn't available yet
        setTimeout(() => {
          if (this.deadlineInputRefs[item.id]) {
            this.openFlatpickr(item);
          } else {
            console.error('Still could not find deadlineInputRef after delay for item id:', item.id);
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
      // Return the appropriate background color class from PRIORITY_COLORS
      return this.priorityColors[color] || 'emerald-50';
    },
    
    getDeadlineClass(item) {
      if (!item.deadline) return 'text-gray-500 hover:underline';
      const deadlineDate = new Date(item.deadline);
      const now = new Date();
      
      if (deadlineDate < now) {
        return 'text-red-500 hover:underline';
      }
      
      return 'text-gray-500 hover:underline';
    }
 },
  
  mounted() {
    // Initialize default list if none exist
    if (this.lists.length === 0) {
      this.createNewListDefault();
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', (event) => {
      if (!event.target.closest('#list-switcher-button') &&
          !event.target.closest('#list-dropdown')) {
        this.showListDropdown = false;
      }
    });
    
    // Initialize item properties that don't exist in the original data
    this.lists.forEach(list => {
      if (list.items) {
        list.items.forEach(item => {
          this.$set(item, 'editingTitle', false);
          this.$set(item, 'editedTitle', item.title);
          this.$set(item, 'showColorPalette', false);
          this.$set(item, 'showDeadlinePicker', false);
          this.$set(item, 'editedDeadline', item.deadline ? new Date(item.deadline).toISOString().slice(0, 16) : '');
        });
      }
    });
  },
  
  beforeUnmount() {
    // Clean up all flatpickr instances when component is destroyed
    Object.values(this.flatpickrInstances).forEach(instance => {
      if (instance) {
        instance.destroy();
      }
    });
  }
}
</script>
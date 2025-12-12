<template>
  <div class="bg-white rounded-lg shadow-md p-6 w-full max-w-2xl">
    <!-- Header with list switcher and actions -->
    <div class="flex justify-between items-center mb-6">
      <ListDropdown 
        :lists="lists"
        :active-list="activeList"
        :base-url="baseUrl"
        :csrf-token="csrfToken"
        @toggle-dropdown="handleDropdownToggle"
        @select-list="selectList"
        @add-list="addList"
        @delete-list="deleteListFromStore"
      />
      
      <div class="flex items-center gap-4">
        <a 
          :href="exportUrl" 
          class="text-gray-400 hover:text-sky-500" 
          title="Export to Excel"
          :class="{ 'hidden': !activeListId }"
        >
          &#x2913;
        </a>
      </div>
    </div> <!-- End of header container div -->
    
    <!-- Priority Filter Tags -->
    <PriorityFilter
      :active-list="activeList"
      :active-priority-filter="activePriorityFilter"
      :priority-counts="priorityCounts"
      @filter-change="setPriorityFilter"
    />
  
    <!-- Add New Task -->
    <AddTaskForm
      :active-list-id="activeListId"
      :base-url="baseUrl"
      :csrf-token="csrfToken"
      @add-item="addItemToStore"
    />
    
    <!-- To-Do Lists Container -->
    <div class="space-y-2">
      <TodoItem
        v-for="item in filteredItems"
        :key="item.id"
        :item="item"
        :base-url="baseUrl"
        :csrf-token="csrfToken"
        @update:item="updateItemInStore"
        @delete:item="deleteItem"
        @color-picker-toggle="handleColorPickerToggle"
        @deadline-picker-toggle="handleDeadlinePickerToggle"
      />
    </div> <!-- End of To-Do Lists Container div -->
    
    <!-- Footer -->
    <div class="mt-6 text-center text-xs text-gray-400">
      A free Todo list tool by 
      <a href="/" class="underline hover:text-gray-600">Software on the Web</a>
    </div>
  </div> <!-- End of main container div -->
</template>

<script>
import ListDropdown from './ListDropdown.vue';
import PriorityFilter from './PriorityFilter.vue';
import AddTaskForm from './AddTaskForm.vue';
import TodoItem from './TodoItem.vue';
import { PRIORITY_ORDER } from '../constants/priorityColors';

export default {
  name: 'TodoList',
  components: {
    ListDropdown,
    PriorityFilter,
    AddTaskForm,
    TodoItem
 },
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
      priorityCounts: {}
    }
  },
 computed: {
    activeList() {
      return this.lists.find(list => list.id == this.activeListId) || null;
    },
    filteredItems() {
      if (!this.activeList || !this.activeList.items) {
        return [];
      }
      
      let sortedItems = [...this.activeList.items].sort((a, b) => {
        const priorityA = PRIORITY_ORDER[a.color || 'emerald'] || 99;
        const priorityB = PRIORITY_ORDER[b.color || 'emerald'] || 99;
        return priorityA - priorityB;
      });
      
      if (this.activePriorityFilter) {
        sortedItems = sortedItems.filter(item => (item.color || 'emerald') === this.activePriorityFilter);
      }
      
      return sortedItems;
    },
    exportUrl() {
      return this.activeListId ? `${this.baseUrl}/${this.activeListId}/export` : '#';
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
    
    handleDropdownToggle(isOpen) {
      // Handle dropdown toggle if needed
    },
    
    selectList(listId) {
      this.activeListId = listId;
      this.calculatePriorityCounts();
    },
    
    addList(newList) {
      this.lists.push(newList);
      this.activeListId = newList.id;
      this.calculatePriorityCounts();
    },
    
    deleteListFromStore(listId) {
      this.lists = this.lists.filter(list => list.id !== listId);
      
      if (this.activeListId == listId) {
        this.activeListId = this.lists.length > 0 ? this.lists[0].id : null;
      }
      
      this.calculatePriorityCounts();
    },
    
    addItemToStore(newItem) {
      const list = this.lists.find(l => l.id == this.activeListId);
      if (list) {
        list.items.push(newItem);
      }
      this.calculatePriorityCounts();
    },
    
    updateItemInStore(updatedItem) {
      const list = this.lists.find(l => l.id == this.activeListId);
      if (list) {
        const index = list.items.findIndex(item => item.id === updatedItem.id);
        if (index !== -1) {
          list.items.splice(index, 1, updatedItem);
        }
      }
      this.calculatePriorityCounts();
    },
    
    async deleteItem(itemId) {
      try {
        await this.apiCall(`${this.baseUrl}/items/${itemId}`, {
          method: 'DELETE'
        });
        
        const list = this.lists.find(l => l.id == this.activeListId);
        if (list) {
          list.items = list.items.filter(item => item.id !== itemId);
        }
        
        this.calculatePriorityCounts();
      } catch (error) {
        console.error('Failed to delete item:', error);
      }
    },
    
    setPriorityFilter(priority) {
      this.activePriorityFilter = priority;
    },
    
    calculatePriorityCounts() {
      if (!this.activeList || !this.activeList.items) {
        this.priorityCounts = {};
        return;
      }
      
      const counts = {};
      this.activeList.items.forEach(item => {
        const priority = item.color || 'emerald';
        counts[priority] = (counts[priority] || 0) + 1;
      });
      
      this.priorityCounts = counts;
    },
    
    handleColorPickerToggle(itemId, isOpen) {
      // Handle color picker toggle if needed
    },
    
    handleDeadlinePickerToggle(itemId, isOpen) {
      // Handle deadline picker toggle if needed
    }
  },
  
  mounted() {
    this.calculatePriorityCounts();
  }
}
</script>

<style scoped>
.priority-tag, .list-item {
  --border-width: 1.5px;
  --border-color: #9ca3af; /* gray-70 */
  --border-radius: 0.25rem; /* equivalent to Tailwind's rounded */
  transition: all 0.2s ease-in-out;
}

.priority-tag {
  border: var(--border-width, 1.5px) solid var(--border-color, #9ca3af);
  border-radius: var(--border-radius, 0.25rem);
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
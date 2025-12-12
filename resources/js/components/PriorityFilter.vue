<template>
  <div class="flex items-center gap-2 mb-4">
    <div 
      class="inline-flex align-items-center px-3 py-1 rounded text-xs cursor-pointer transition-all priority-tag"
      :class="{
        'text-gray-400': activePriorityFilter !== null,
        'text-gray-800 font-medium': activePriorityFilter === null
      }"
      @click="setPriorityFilter(null)"
    >
      <span>All</span>
      <span class="ml-2 min-w-[20px] h-5 rounded-full bg-gray-400 text-white flex items-center justify-center text-xs font-medium border-gray-700">
        {{ activeList ? (activeList.items ? activeList.items.length : 0) : 0 }}
      </span>
    </div>
    
    <div 
      v-for="(priority, key) in priorityNames" 
      :key="key"
      class="inline-flex align-items-center px-3 py-1 rounded text-xs cursor-pointer transition-all priority-tag"
      :class="{
        [`text-${getPriorityColorClass(key)} font-medium`]: activePriorityFilter === key,
        [`text-gray-400`]: activePriorityFilter !== key
      }"
      @click="setPriorityFilter(key)"
    >
      <span>{{ priority }}</span>
      <span class="ml-2 min-w-[20px] h-5 rounded-full border border-gray-700" :class="`bg-${getPriorityColorClass(key)} text-white flex items-center justify-center text-xs font-medium`">
        {{ priorityCounts[key] || 0 }}
      </span>
    </div>
  </div>
</template>

<script>
import { PRIORITY_COLORS, PRIORITY_NAMES } from '../constants/priorityColors';

export default {
  name: 'PriorityFilter',
  props: {
    activeList: {
      type: Object,
      default: null
    },
    activePriorityFilter: {
      type: [String, null],
      default: null
    },
    priorityCounts: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      priorityNames: PRIORITY_NAMES,
      priorityColors: PRIORITY_COLORS
    }
  },
  methods: {
    setPriorityFilter(priority) {
      this.$emit('filter-change', priority);
    },
    
    getPriorityColorClass(color) {
      // Return the appropriate color class from PRIORITY_COLORS
      return this.priorityColors[color] || 'emerald-500';
    }
  }
}
</script>

<style scoped>
.priority-tag {
  transition: all 0.2s ease-in-out;
  position: relative;
 background: none;
  border: none;
  color: inherit;
}

.priority-tag::after {
  content: '';
  position: absolute;
 bottom: -2px;
  left: 0;
  width: 100%;
  height: 2px;
  background-color: transparent;
  transition: all 0.2s ease-in-out;
}

.priority-tag.font-medium::after {
  background-color: #7EBB94;
}

.priority-tag::after {
  content: '';
  position: absolute;
 bottom: -2px;
  left: 0;
  width: 100%;
  height: 2px;
  background-color: transparent;
  transition: all 0.2s ease-in-out;
}

.priority-tag.font-medium::after {
  background-color: #7EBB94;
}
</style>
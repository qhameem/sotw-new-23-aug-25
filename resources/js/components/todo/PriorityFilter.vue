<template>
  <div class="flex items-center gap-2 mb-4">
    <div 
      class="priority-tag priority-tag-all" 
      :class="{ 'active': activePriorityFilter === null }" 
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
</template>

<script>
import { PRIORITY_COLORS, PRIORITY_NAMES } from '../../constants/priorityColors';

export default {
  name: 'PriorityFilter',
  props: {
    activePriorityFilter: {
      type: [String, null],
      required: true
    },
    priorityCounts: {
      type: Object,
      required: true
    },
    totalItemsCount: {
      type: Number,
      required: true
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
      this.$emit('update:activePriorityFilter', priority);
    }
  }
}
</script>

<style scoped>
.priority-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
 padding: 0.5rem 1rem;
 border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s ease;
  background-color: #e5e7eb;
  color: #4b5563;
}

.priority-tag:hover {
  background-color: #d1d5db;
}

.priority-tag.active {
  background-color: #3b82f6;
  color: white;
}

.priority-tag-all {
  background-color: #3b82f6;
 color: white;
}

.priority-tag-all.active {
  background-color: #2563eb;
}

.priority-count {
 background-color: rgba(255, 255, 255, 0.3);
  border-radius: 9999px;
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
}

.priority-tag:hover {
  background-color: #d1d5db;
}

.priority-tag.active {
  background-color: #3b82f6;
  color: white;
}

.priority-tag-all {
  background-color: #3b82f6;
 color: white;
}

.priority-tag-all.active {
  background-color: #2563eb;
}

.priority-count {
 background-color: rgba(255, 255, 255, 0.3);
  border-radius: 9999px;
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
}
</style>
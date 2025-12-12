<template>
  <div class="w-full p-8 hidden md:block">
    <div class="flex mb-4 items-center">
      <img
        v-if="favicon"
        :src="favicon"
        alt="Favicon"
        class="h-12 w-12 mr-2"
      >
      <div>
        <h2 class="text-lg font-semibold text-gray-700">{{ productName || 'Product Details' }}</h2>
        <p class="text-sm text-gray-500">In progress</p>
      </div>
    </div>
    <ul class="space-y-2">
      <li v-for="(step, index) in sidebarSteps" :key="index"
          @click="$emit('navigate', step.id)"
          :class="['p-2 rounded-lg cursor-pointer', { 'bg-yellow-50': currentTab === step.id }]">
        <a href="#" class="flex items-center space-x-3">
          <span v-html="step.icon"></span>
          <span class="font-normal text-gray-600 text-sm">{{ step.name }}</span>
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  favicon: String,
  productName: String,
  sidebarSteps: Array,
  currentTab: String,
});

defineEmits(['navigate']);
</script>
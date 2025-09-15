<div class="flex justify-end mb-2">
    <div class="flex space-x-1 border border-dashed p-1 rounded-lg">
        <button @click="view = 'weekly'; $dispatch('view-changed', 'weekly')" :class="{ 'bg-white border text-gray-600': view === 'weekly', 'text-gray-400': view !== 'weekly' }" class="px-3 py-1 text-xs font-semibold rounded-md">Weekly</button>
        <button @click="view = 'daily'; $dispatch('view-changed', 'daily')" :class="{ 'bg-white border text-gray-600': view === 'daily', 'text-gray-400': view !== 'daily' }" class="px-3 py-1 text-xs font-semibold rounded-md">Daily</button>
    </div>
</div>
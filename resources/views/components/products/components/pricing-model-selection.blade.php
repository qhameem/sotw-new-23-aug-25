@props(['categories', 'selected' => [], 'type'])

<div x-data='pricingModelSelect(@json($categories), @json($selected), @json($type))'>
    <div class="flex justify-between items-center">
        <label class="block text-xs font-semibold md:text-left md:pr-4 mb-1">Pricing Model<span class="text-red-500 ml-1">*</span></label>
        <span class="text-xs text-gray-600">Select at least 1</span>
    </div>
    <div class="grid grid-cols-2 gap-y-1 border rounded-md p-3">
        <template x-for="category in allCategories" :key="category.id">
            <label class="flex items-center py-1 text-xs cursor-pointer hover:bg-gray-50 px-1 rounded">
                <input type="checkbox" name="categories[]" :value="category.id" x-model="selectedCategories" class="mr-2 rounded border-gray-300 text-primary-600 shadow-sm">
                <span x-text="category.name" class="text-gray-700"></span>
            </label>
        </template>
        <template x-if="allCategories.length === 0">
            <p class="col-span-full text-center text-xs text-gray-500 py-2">No pricing categories available.</p>
        </template>
    </div>
    <template x-for="selectedCatId in selectedCategories">
        <input type="hidden" name="categories[]" :value="selectedCatId">
    </template>
</div>

<script>
    function pricingModelSelect(categories, selected, type) {
        return {
            type: type,
            allCategories: categories.map(c => ({...c, id: c.id.toString()})),
            selectedCategories: selected.map(s => s.toString()),
            init() {
                this.$watch('selectedCategories', (newValue) => {
                    this.$dispatch('category-change', { type: this.type, selected: newValue });
                });
            }
        }
    }
</script>
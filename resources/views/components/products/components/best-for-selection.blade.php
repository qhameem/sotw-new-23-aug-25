@props(['categories', 'selected' => [], 'type'])

<div x-data='bestForSelect(@json($categories), @json($selected), @json($type))' @click.away="isBestForDropdownOpen = false">
    <div class="flex justify-between items-center">
        <label class="block text-xs font-semibold md:text-left md:pr-4 mb-1">Best for<span class="text-red-500 ml-1">*</span></label>
        <span class="text-xs text-gray-600">Select at least 1</span>
    </div>
    <div class="mb-4">
        <div class="mb-2 relative">
            <div class="w-full text-sm text-gray-700 border-gray-300 rounded-md p-2 placeholder-gray-400 pr-8 flex flex-wrap gap-2 items-center border" @click="isBestForDropdownOpen = true; $refs.bestForSearchInput.focus()">
                <template x-for="category in selectedCategories.map(id => allCategories.find(c => c.id == id))" :key="category.id">
                    <span class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full group hover:bg-gray-200">
                        <span x-text="category.name" class="truncate max-w-[180px]" :title="category.name"></span>
                        <button @click.prevent.stop="deselectCategory(category.id)" type="button" class="ml-1.5 -mr-1 flex-shrink-0 inline-flex items-center justify-center h-4 w-4 rounded-full text-gray-400 hover:text-gray-600 focus:outline-none focus:bg-gray-300" :aria-label="'Remove ' + category.name">
                            <svg class="h-2.5 w-2.5" stroke="currentColor" fill="none" viewBox="0 0 8 8"><path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" /></svg>
                        </button>
                    </span>
                </template>
                <input type="text" x-model="bestForSearchTerm" x-ref="bestForSearchInput" placeholder="Search 'best for' categories..." class="flex-grow border-none focus:ring-0 p-0 text-sm placeholder-gray-400 placeholder:text-xs focus:outline-none" @keydown.arrow-down.prevent="highlightedBestForIndex = Math.min(highlightedBestForIndex + 1, filteredCategories.length - 1)" @keydown.arrow-up.prevent="highlightedBestForIndex = Math.max(highlightedBestForIndex - 1, -1)" @keydown.enter.prevent="if (highlightedBestForIndex > -1) { toggleCategory(filteredCategories[highlightedBestForIndex].id); highlightedBestForIndex = -1; }" @focus="isBestForDropdownOpen = true">
                <div class="absolute inset-y-0 right-0 flex items-center pr-2" @click.stop="isBestForDropdownOpen = !isBestForDropdownOpen">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </div>
            </div>
            <div x-show="isBestForDropdownOpen" x-transition class="absolute z-20 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm" x-ref="bestForDropdown">
                <template x-for="(category, index) in filteredCategories" :key="category.id">
                    <div @click="toggleCategory(category.id)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-gray-100" :class="{ 'bg-gray-200': highlightedBestForIndex === index }">
                        <span x-text="category.name" class="font-normal block truncate"></span>
                    </div>
                </template>
                <template x-if="filteredCategories.length === 0">
                    <p class="text-center text-xs text-gray-500 py-2">No matching categories found.</p>
                </template>
            </div>
        </div>
    </div>
    <template x-for="selectedCatId in selectedCategories">
        <input type="hidden" name="categories[]" :value="selectedCatId">
    </template>
</div>

<script>
    function bestForSelect(categories, selected, type) {
        return {
            type: type,
            allCategories: categories.map(c => ({...c, id: c.id.toString()})),
            selectedCategories: selected.map(s => s.toString()),
            bestForSearchTerm: '',
            isBestForDropdownOpen: false,
            highlightedBestForIndex: -1,
            init() {
                this.$watch('bestForSearchTerm', () => {
                    this.highlightedBestForIndex = -1;
                });
            },
            get filteredCategories() {
                if (this.bestForSearchTerm.trim() === '') {
                    return this.allCategories;
                }
                return this.allCategories.filter(category =>
                    category.name.toLowerCase().includes(this.bestForSearchTerm.toLowerCase())
                );
            },
            toggleCategory(categoryId) {
                const id = categoryId.toString();
                const index = this.selectedCategories.indexOf(id);
                if (index === -1) {
                    this.selectedCategories.push(id);
                } else {
                    this.selectedCategories.splice(index, 1);
                }
                this.$dispatch('category-change', { type: this.type, selected: this.selectedCategories });
            },
            deselectCategory(categoryId) {
                const id = categoryId.toString();
                const index = this.selectedCategories.indexOf(id);
                if (index > -1) {
                    this.selectedCategories.splice(index, 1);
                }
                this.$dispatch('category-change', { type: this.type, selected: this.selectedCategories });
            }
        }
    }
</script>
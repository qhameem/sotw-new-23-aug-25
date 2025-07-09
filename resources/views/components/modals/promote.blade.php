@auth
<!-- Include Flatpickr CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div x-show="promoteModalOpen" @click.away="promoteModalOpen = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
     style="display: none;"
     x-data="{
        search: '',
        products: {{ auth()->user()->products()->where('approved', false)->latest()->get()->toJson() }},
        cart: [],
        publish_dates: {},
        get filteredProducts() {
            if (this.search === '') return this.products;
            return this.products.filter(product => product.name.toLowerCase().includes(this.search.toLowerCase()));
        },
        get cartTotal() {
            return this.cart.length * 49;
        },
        isDateSelected(id) {
            return this.publish_dates[id] && this.publish_dates[id] !== '';
        },
        getDefaultDateUTC() {
            const tomorrow = new Date();
            tomorrow.setUTCDate(tomorrow.getUTCDate() + 1);
            return tomorrow.toISOString().split('T')[0]; // Format: YYYY-MM-DD
        },
        initDatepicker(id) {
            this.$nextTick(() => {
                const el = document.getElementById(`date-${id}`);
                if (el && !el._flatpickr) {
                    const defaultDate = this.getDefaultDateUTC();
                    if (!this.publish_dates[id]) {
                        this.publish_dates[id] = defaultDate;
                    }
                    flatpickr(el, {
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'j F Y', // 3 July 2025
                        minDate: 'today',
                        defaultDate: this.publish_dates[id],
                        onChange: (selectedDates, dateStr, instance) => {
                            // Store value in YYYY-MM-DD format for backend
                            this.publish_dates[id] = instance.input._flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                        }
                    });
                }
            });
        }

     }"
     x-init="$nextTick(() => {
         products.forEach(p => initDatepicker(p.id));
     })"
>

    <div @click.away="promoteModalOpen = false" class="bg-white rounded-lg p-8 max-w-2xl w-full">
        <h2 class="text-xl font-semibold mb-4">Select Products to Fast-Track or Schedule Submission</h2>

        <template x-if="products.length > 0">
            <form action="{{ route('stripe.checkout') }}" method="POST">
                @csrf

                <!-- Product Grid -->
                <div class="grid grid-cols-1 gap-4 max-h-96 overflow-y-auto">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div class="border rounded-lg p-4" :class="{'border-primary-500': cart.includes(product.id)}">
                            <div class="flex items-center">
                                <input type="checkbox"
                                       :value="product.id"
                                       x-model="cart"
                                       class="form-checkbox h-5 w-5 text-primary-600">
                                <div class="h-10 w-10 mx-4 flex-shrink-0">
                                    <template x-if="product.logo">
                                        <img :src="product.logo.startsWith('http') ? product.logo : '/storage/' + product.logo"
                                             :alt="product.name + ' logo'" class="h-10 w-10 rounded-full object-cover">
                                    </template>
                                    <template x-if="!product.logo && product.link">
                                        <img :src="'https://www.google.com/s2/favicons?sz=64&domain_url=' + encodeURIComponent(product.link)"
                                             :alt="product.name + ' favicon'" class="h-10 w-10 rounded-full object-cover">
                                    </template>
                                    <template x-if="!product.logo && !product.link">
                                        <div class="h-10 w-10 rounded-full bg-gray-200"></div>
                                    </template>
                                </div>
                                <div class="flex-grow">
                                    <p class="font-semibold" x-text="product.name"></p>
                                    <a :href="product.link" target="_blank"
                                       class="text-sm text-gray-500 hover:underline"
                                       x-text="product.link"></a>
                                </div>
                            </div>

                            <!-- Date Picker (Always Visible with Default Date) -->
                            <div class="mt-2">
                                <label :for="'date-' + product.id" class="block text-sm font-medium text-gray-700">Publish Date</label>
                                <input type="text"
                                       :id="'date-' + product.id"
                                       x-model="publish_dates[product.id]"
                                       class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
                                       placeholder="Select date" required>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-between items-center mt-4">
                    <div>
                        <span class="text-xl font-semibold">Total:</span>
                        <span class="text-xl" x-text="`$${cartTotal}`"></span>
                    </div>
                    <div class="flex justify-end">
                        <input type="hidden" name="products" :value="cart">
                        <input type="hidden" name="publish_dates" :value="JSON.stringify(publish_dates)">
                        <button type="button" @click="promoteModalOpen = false"
                                class="mr-2 bg-gray-200 text-gray-800 hover:bg-gray-300 px-4 py-2 rounded-md font-semibold">Cancel</button>
                        <button type="submit"
                                class="bg-primary-500 text-white hover:opacity-90 px-4 py-2 rounded-md font-semibold"
                                :disabled="cart.length === 0 || !cart.every(id => isDateSelected(id))">
                            Proceed to Payment
                        </button>
                    </div>
                </div>
            </form>
        </template>

        <template x-if="products.length === 0">
            <div>
                <p class="text-center text-gray-500">You have no products to promote.</p>
                <div class="flex justify-end mt-4">
                    <button type="button" @click="promoteModalOpen = false"
                            class="bg-gray-200 text-gray-800 hover:bg-gray-300 px-4 py-2 rounded-md font-semibold">Close</button>
                </div>
            </div>
        </template>
    </div>
</div>
@endauth

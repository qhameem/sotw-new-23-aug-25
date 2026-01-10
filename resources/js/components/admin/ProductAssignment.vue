<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-4xl mx-auto">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Assign Product Ownership</h2>
            <p class="text-gray-500 mt-1">Search for a product and a user to reassign its ownership. The new owner will be able to edit the product details.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Product Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">1. Select Product</label>
                <div class="relative">
                    <input 
                        type="text" 
                        v-model="productSearch" 
                        @input="debounceProductSearch"
                        placeholder="Search product by name..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all outline-none"
                        :class="{ 'border-primary-500': selectedProduct }"
                    >
                    <div v-if="isProductSearching" class="absolute right-3 top-3.5">
                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Product Results -->
                    <div v-if="productResults.length > 0 && productSearch.length > 0" class="absolute z-10 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-xl max-h-64 overflow-y-auto">
                        <div 
                            v-for="product in productResults" 
                            :key="product.id"
                            @click="selectProduct(product)"
                            class="flex items-center p-3 hover:bg-gray-50 cursor-pointer transition-colors border-b last:border-0"
                        >
                            <img :src="product.logo_url" class="w-10 h-10 rounded-lg object-cover mr-3 bg-gray-100">
                            <div>
                                <div class="font-bold text-gray-900">{{ product.name }}</div>
                                <div class="text-xs text-gray-500 line-clamp-1">{{ product.tagline }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Product Card -->
                <div v-if="selectedProduct" class="mt-4 p-4 bg-primary-50 border border-primary-100 rounded-lg flex items-center">
                    <img :src="selectedProduct.logo_url" class="w-12 h-12 rounded-xl object-cover mr-4 shadow-sm">
                    <div class="flex-1">
                        <span class="text-xs font-bold text-primary-600 uppercase tracking-wider">Target Product</span>
                        <h4 class="font-bold text-gray-900 leading-tight">{{ selectedProduct.name }}</h4>
                    </div>
                    <button @click="selectedProduct = null" class="text-gray-400 hover:text-rose-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- User Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">2. Select New Owner</label>
                <div class="relative">
                    <input 
                        type="text" 
                        v-model="userSearch" 
                        @input="debounceUserSearch"
                        placeholder="Search by name, username or email..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all outline-none"
                        :class="{ 'border-primary-500': selectedUser }"
                    >
                    <div v-if="isUserSearching" class="absolute right-3 top-3.5">
                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- User Results -->
                    <div v-if="userResults.length > 0 && userSearch.length > 0" class="absolute z-10 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-xl max-h-64 overflow-y-auto">
                        <div 
                            v-for="user in userResults" 
                            :key="user.id"
                            @click="selectUser(user)"
                            class="flex items-center p-3 hover:bg-gray-50 cursor-pointer transition-colors border-b last:border-0"
                        >
                            <img :src="user.avatar" class="w-10 h-10 rounded-full object-cover mr-3 bg-gray-100">
                            <div>
                                <div class="font-bold text-gray-900">{{ user.name }} <span class="text-xs font-normal text-gray-400">@{{ user.username }}</span></div>
                                <div class="text-xs text-gray-500">{{ user.email }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected User Card -->
                <div v-if="selectedUser" class="mt-4 p-4 bg-primary-50 border border-primary-100 rounded-lg flex items-center">
                    <img :src="selectedUser.avatar" class="w-12 h-12 rounded-full object-cover mr-4 shadow-sm border-2 border-white">
                    <div class="flex-1">
                        <span class="text-xs font-bold text-primary-600 uppercase tracking-wider">New Owner</span>
                        <h4 class="font-bold text-gray-900 leading-tight">{{ selectedUser.name }}</h4>
                    </div>
                    <button @click="selectedUser = null" class="text-gray-400 hover:text-rose-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center">
            <button 
                @click="performAssignment"
                :disabled="!isValid || isSubmitting"
                class="px-12 py-4 bg-gray-900 text-white font-bold rounded-xl shadow-lg hover:bg-black transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
            >
                <span v-if="!isSubmitting">Complete Assignment</span>
                <span v-else class="flex items-center">
                    <svg class="animate-spin h-5 w-5 text-white mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                </span>
            </button>
            <p v-if="!isValid && selectedProduct && selectedUser" class="text-xs text-rose-500 mt-3 font-semibold">Please ensure both product and user are correctly selected.</p>
        </div>

        <!-- Notifications -->
        <transition name="fade">
            <div v-if="notification" :class="notification.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'" class="fixed bottom-8 right-8 px-6 py-4 rounded-xl border shadow-xl flex items-center z-[100]">
                <div class="mr-3">
                    <svg v-if="notification.type === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                </div>
                <div class="font-semibold">{{ notification.message }}</div>
                <button @click="notification = null" class="ml-4 opacity-50 hover:opacity-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </transition>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'ProductAssignment',
    data() {
        return {
            productSearch: '',
            userSearch: '',
            productResults: [],
            userResults: [],
            selectedProduct: null,
            selectedUser: null,
            isProductSearching: false,
            isUserSearching: false,
            isSubmitting: false,
            productTimeout: null,
            userTimeout: null,
            notification: null
        };
    },
    computed: {
        isValid() {
            return this.selectedProduct && this.selectedUser;
        }
    },
    methods: {
        debounceProductSearch() {
            clearTimeout(this.productTimeout);
            this.selectedProduct = null;
            if (this.productSearch.length < 2) {
                this.productResults = [];
                return;
            }
            this.isProductSearching = true;
            this.productTimeout = setTimeout(this.fetchProducts, 400);
        },
        async fetchProducts() {
            try {
                const response = await axios.get('/admin/products-search-ajax', {
                    params: { q: this.productSearch }
                });
                this.productResults = response.data;
            } catch (error) {
                console.error('Error searching products:', error);
            } finally {
                this.isProductSearching = false;
            }
        },
        selectProduct(product) {
            this.selectedProduct = product;
            this.productSearch = product.name;
            this.productResults = [];
        },
        debounceUserSearch() {
            clearTimeout(this.userTimeout);
            this.selectedUser = null;
            if (this.userSearch.length < 2) {
                this.userResults = [];
                return;
            }
            this.isUserSearching = true;
            this.userTimeout = setTimeout(this.fetchUsers, 400);
        },
        async fetchUsers() {
            try {
                const response = await axios.get('/admin/users-search-ajax', {
                    params: { q: this.userSearch }
                });
                this.userResults = response.data;
            } catch (error) {
                console.error('Error searching users:', error);
            } finally {
                this.isUserSearching = false;
            }
        },
        selectUser(user) {
            this.selectedUser = user;
            this.userSearch = user.name;
            this.userResults = [];
        },
        async performAssignment() {
            if (!this.isValid || this.isSubmitting) return;

            this.isSubmitting = true;
            try {
                const response = await axios.post('/admin/products/assign', {
                    product_id: this.selectedProduct.id,
                    user_id: this.selectedUser.id
                });

                if (response.data.success) {
                    this.showNotification('success', response.data.message);
                    this.resetForm();
                }
            } catch (error) {
                const message = error.response?.data?.message || 'Failed to assign product. Please try again.';
                this.showNotification('error', message);
            } finally {
                this.isSubmitting = false;
            }
        },
        showNotification(type, message) {
            this.notification = { type, message };
            setTimeout(() => {
                if (this.notification?.message === message) {
                    this.notification = null;
                }
            }, 5000);
        },
        resetForm() {
            this.selectedProduct = null;
            this.selectedUser = null;
            this.productSearch = '';
            this.userSearch = '';
        }
    }
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>

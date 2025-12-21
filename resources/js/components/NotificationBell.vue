<template>
    <div class="relative ml-3">
        <button @click="toggleDropdown"
            class="relative p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none">
            <span class="sr-only">View notifications</span>
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <span v-if="unreadCount > 0"
                class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div v-show="isOpen" @click.away="isOpen = false"
            class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            style="max-height: 400px; overflow-y: auto;" @scroll="handleScroll">
            <div class="px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-sm font-medium text-gray-700">Notifications</h3>
                <button @click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-500"
                    :disabled="unreadCount === 0">
                    Mark all as read
                </button>
            </div>

            <div v-if="loading && notifications.length === 0" class="p-4 text-center text-sm text-gray-500">
                Loading...</div>
            <div v-if="!loading && notifications.length === 0" class="p-4 text-center text-sm text-gray-500">
                You have no new notifications.</div>

            <a v-for="notification in notifications" :key="notification.id" :href="notification.data.link"
                @click.prevent="markAsRead(notification)"
                class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100"
                :class="{ 'bg-gray-50 font-semibold': !notification.read_at }">
                <p class="truncate">{{ notification.data.message }}</p>
                <p class="text-xs text-gray-500">{{ new Date(notification.created_at).toLocaleString() }}</p>
            </a>

            <div v-if="loadingMore" class="p-4 text-center text-sm text-gray-500">Loading more...</div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

export default {
    props: {
        userId: {
            type: Number,
            required: true,
        },
    },
    setup(props) {
        const isOpen = ref(false);
        const notifications = ref([]);
        const unreadCount = ref(0);
        const loading = ref(true);
        const loadingMore = ref(false);
        const page = ref(1);
        const lastPage = ref(1);

        const fetchNotifications = async () => {
            if (page.value > lastPage.value && page.value > 1) return;
            if (page.value === 1) loading.value = true;
            else loadingMore.value = true;

            try {
                const response = await axios.get(`/api/notifications?page=${page.value}`, {
                    withCredentials: true
                });
                const data = response.data;
                notifications.value = page.value === 1 ? data.notifications.data : [...notifications.value, ...data.notifications.data];
                unreadCount.value = data.unread_count;
                lastPage.value = data.notifications.last_page;
            } catch (error) {
                console.error('Error fetching notifications:', error);
            } finally {
                loading.value = false;
                loadingMore.value = false;
            }
        };

        const handleScroll = (event) => {
            const { scrollTop, clientHeight, scrollHeight } = event.target;
            if (scrollTop + clientHeight >= scrollHeight - 50 && page.value < lastPage.value && !loadingMore.value) {
                page.value++;
                fetchNotifications();
            }
        };

        const toggleDropdown = () => {
            isOpen.value = !isOpen.value;
            if (isOpen.value && notifications.value.length === 0) {
                fetchNotifications();
            }
        };

        const markAsRead = async (notification) => {
            if (!notification.read_at) {
                try {
                    await axios.put(`/api/notifications/${notification.id}/read`, {}, {
                        withCredentials: true
                    });
                    notification.read_at = new Date().toISOString();
                    unreadCount.value = Math.max(0, unreadCount.value - 1);
                } catch (error) {
                    console.error('Error marking as read:', error);
                }
            }
            window.location.href = notification.data.link;
        };

        const markAllAsRead = async () => {
            try {
                await axios.put('/api/notifications/read-all', {}, {
                    withCredentials: true
                });
                notifications.value.forEach(n => n.read_at = new Date().toISOString());
                unreadCount.value = 0;
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        };

        onMounted(() => {
            fetchNotifications();

            if (window.Echo) {
                window.Echo.private(`App.Models.User.${props.userId}`)
                    .notification((notificationEvent) => {
                        notifications.value.unshift(notificationEvent);
                        unreadCount.value++;
                    });
            }
            
            // Listen for product submission event to refresh notifications
            document.addEventListener('product-submitted', handleProductSubmitted);
        });

        const handleProductSubmitted = () => {
            // Refresh notifications after a short delay to allow the server to process the notification
            setTimeout(() => {
                page.value = 1; // Reset to first page
                fetchNotifications(); // Fetch latest notifications
            }, 1000); // 1 second delay to ensure notification is processed
        };

        onUnmounted(() => {
            document.removeEventListener('product-submitted', handleProductSubmitted);
        });

        return {
            isOpen,
            notifications,
            unreadCount,
            loading,
            loadingMore,
            toggleDropdown,
            markAsRead,
            markAllAsRead,
            handleScroll,
        };
    },
};
</script>
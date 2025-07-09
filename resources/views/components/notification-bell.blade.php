@props(['unreadCount' => 0])

<div x-data="{ open: false, unread: {{ $unreadCount }}, notifications: [], loading: true, page: 1, lastPage: 1 }"
     x-init="
        fetchNotifications = async () => {
            loading = true;
            try {
                const response = await fetch(`/api/notifications?page=${page}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'include' // Important for Sanctum cookie-based auth
                });
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Failed to fetch notifications: ${response.status} ${errorText}`);
                }
                const data = await response.json();
                notifications = page === 1 ? data.notifications.data : [...notifications, ...data.notifications.data];
                unread = data.unread_count;
                lastPage = data.notifications.last_page;
            } catch (error) {
                console.error('Error fetching notifications:', error);
                notifications = []; // Clear notifications on error
            } finally {
                loading = false;
            }
        };
        fetchNotifications();

        @auth
        if (window.Echo) {
            window.Echo.private('App.Models.User.{{ auth()->id() }}')
                .notification((notificationEvent) => {
                    console.log('New notification received via Echo:', notificationEvent);
                    // Add to the start of the notifications list
                    // Ensure the structure matches what the template expects
                    // The broadcasted notificationEvent should already be in the correct format
                    notifications.unshift(notificationEvent);
                    unread++;
                    // If the dropdown is open, new notification will appear at the top.
                    // Optionally, you can add a limit to the number of notifications displayed
                    // if (notifications.length > 20) {
                    //    notifications.pop();
                    // }
                });
        } else {
            console.warn('Laravel Echo not found. Real-time notifications will not work.');
        }
        @endauth

        // Optional: Poll for unread count updates every 60 seconds
        // setInterval(async () => {
        //     try {
        //         const response = await fetch('/api/notifications?per_page=1'); // Fetch minimal data just for count
        //         if (!response.ok) return;
        //         const data = await response.json();
        //         unread = data.unread_count;
        //     } catch (error) { console.error('Error polling unread count:', error); }
        // }, 60000);
     "
     class="relative ml-3">
    <button @click="open = !open; if(open && notifications.length === 0 && page === 1) fetchNotifications();"
            class="relative p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        <span class="sr-only">View notifications</span>
        <!-- Heroicon name: outline/bell -->
        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <template x-if="unread > 0">
            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" x-text="unread > 9 ? '9+' : unread"
                  :class="{ 'px-1 text-xs': unread > 0, 'hidden': unread === 0 }"
                  style="font-size: 0.6rem; line-height: 0.5rem; min-width: 0.5rem; text-align: center; padding-top: 1px; padding-bottom: 1px;">
            </span>
        </template>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
         role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1"
         style="max-height: 400px; overflow-y: auto;"
         @scroll="if ($event.target.scrollTop + $event.target.clientHeight >= $event.target.scrollHeight - 50 && page < lastPage && !loading) { page++; fetchNotifications(); }">

        <div class="px-4 py-2 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-sm font-medium text-gray-700">Notifications</h3>
            <button @click="
                        async () => {
                            try {
                                const response = await fetch('/api/notifications/read-all', { method: 'PUT', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
                                if (!response.ok) throw new Error('Failed to mark all as read');
                                notifications.forEach(n => n.read_at = new Date().toISOString());
                                unread = 0;
                            } catch (error) { console.error('Error marking all as read:', error); }
                        }
                    "
                    class="text-xs text-indigo-600 hover:text-indigo-500"
                    :disabled="unread === 0">
                Mark all as read
            </button>
        </div>

        <template x-if="loading && notifications.length === 0">
            <div class="p-4 text-center text-sm text-gray-500">Loading...</div>
        </template>

        <template x-if="!loading && notifications.length === 0">
            <div class="p-4 text-center text-sm text-gray-500">You have no new notifications.</div>
        </template>

        <template x-for="notification in notifications" :key="notification.id">
            <a :href="notification.data.link"
               @click="
                    if (!notification.read_at) {
                        try {
                            fetch(`/api/notifications/${notification.id}/read`, { method: 'PUT', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
                            notification.read_at = new Date().toISOString(); // Optimistic update
                            unread = Math.max(0, unread - 1);
                        } catch (error) { console.error('Error marking as read:', error); }
                    }
               "
               class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100"
               :class="{ 'bg-gray-50 font-semibold': !notification.read_at }">
                <p class="truncate" x-text="notification.data.message"></p>
                <p class="text-xs text-gray-500" x-text="new Date(notification.created_at).toLocaleString()"></p>
            </a>
        </template>
         <template x-if="loading && notifications.length > 0">
            <div class="p-4 text-center text-sm text-gray-500">Loading more...</div>
        </template>
    </div>
</div>
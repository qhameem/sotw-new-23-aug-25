// Components will be dynamically imported
import axios from 'axios';

// Add this line to set the CSRF token for all Axios requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Check if Alpine is already loaded (e.g., by Livewire) to avoid multiple instances
if (!window.Alpine) {
    import('alpinejs').then(Alpine => {
        window.Alpine = Alpine.default;

        // Define the upvote component
        window.Alpine.data('upvote', (isUpvoted, initialVotesCount, productId, productSlug, isAuthenticated, csrfToken) => ({
            isUpvoted: isUpvoted,
            votesCount: initialVotesCount,
            errorMessage: '',

            async toggleUpvote() {
                if (!isAuthenticated) {
                    // Redirect to login if not authenticated
                    window.location.href = '/login';
                    return;
                }

                try {
                    const response = await fetch(`/products/${productId}/upvote`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                        }),
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.isUpvoted = data.is_upvoted;
                        this.votesCount = data.votes_count;
                        this.errorMessage = '';
                    } else {
                        const errorData = await response.json();
                        this.errorMessage = errorData.message || 'An error occurred while processing your request';
                    }
                } catch (error) {
                    console.error('Error toggling upvote:', error);
                    this.errorMessage = 'Network error occurred. Please try again.';
                }
            }
        }));

        window.Alpine.start();
    });
} else {
    // Alpine is already loaded, just define the upvote component
    window.Alpine.data('upvote', (isUpvoted, initialVotesCount, productId, productSlug, isAuthenticated, csrfToken) => ({
        isUpvoted: isUpvoted,
        votesCount: initialVotesCount,
        errorMessage: '',

        async toggleUpvote() {
            if (!isAuthenticated) {
                // Redirect to login if not authenticated
                window.location.href = '/login';
                return;
            }

            try {
                const response = await fetch(`/products/${productId}/upvote`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    this.isUpvoted = data.is_upvoted;
                    this.votesCount = data.votes_count;
                    this.errorMessage = '';
                } else {
                    const errorData = await response.json();
                    this.errorMessage = errorData.message || 'An error occurred while processing your request';
                }
            } catch (error) {
                console.error('Error toggling upvote:', error);
                this.errorMessage = 'Network error occurred. Please try again.';
            }
        }
    }));
}

if (document.getElementById('notification-bell-app')) {
    import('./components/NotificationBell.vue').then((NotificationBell) => {
        import('vue').then(({ createApp }) => {
            const notificationApp = createApp({});
            notificationApp.component('notification-bell', NotificationBell.default);
            notificationApp.mount('#notification-bell-app');
        });
    });
}

if (document.getElementById('product-submit-app')) {
    import('./components/ProductSubmit.vue').then((ProductSubmit) => {
        import('vue').then(({ createApp }) => {
            const app = createApp(ProductSubmit.default);
            app.mount('#product-submit-app');
        });
    });
}

if (document.getElementById('user-dropdown-app')) {
    const el = document.getElementById('user-dropdown-app');
    import('./components/UserDropdown.vue').then((UserDropdown) => {
        import('vue').then(({ createApp }) => {
            const userDropdownApp = createApp(UserDropdown.default, {
                user: JSON.parse(el.dataset.user),
                isAdmin: el.dataset.isAdmin === 'true',
            });
            userDropdownApp.mount('#user-dropdown-app');
        });
    });
}

// Register the TodoList component globally
if (document.getElementById('todo-app-container')) {
    import('./components/TodoList.vue').then((TodoList) => {
        import('vue').then(({ createApp }) => {
            const todoApp = createApp({
                components: {
                    TodoList: TodoList.default
                }
            });
            todoApp.mount('#todo-app-container');
        });
    });
}

// Mount the DynamicChecklist component to the checklist container
if (document.getElementById('checklist-container')) {
    import('./components/DynamicChecklist.vue').then((DynamicChecklist) => {
        import('vue').then(({ createApp }) => {
            const checklistApp = createApp(DynamicChecklist.default);
            checklistApp.mount('#checklist-container');
        });
    });
}

// Mount the ProductAssignment component for admin
if (document.getElementById('product-assignment-app')) {
    import('./components/admin/ProductAssignment.vue').then((ProductAssignment) => {
        import('vue').then(({ createApp }) => {
            const assignmentApp = createApp(ProductAssignment.default);
            assignmentApp.mount('#product-assignment-app');
        });
    });
}

// Make Datepicker available globally for inline scripts

document.addEventListener('DOMContentLoaded', () => {
    console.log('[app.js] DOMContentLoaded: Flowbite, Alpine initialized. Main script logic follows.');

    // Inline loader logic for "Add your product" buttons (only run if buttons exist)
    const addProductButtons = [
        document.getElementById('addProductBtnDesktop'),
        document.getElementById('addProductBtnMobile')
    ].filter(btn => btn !== null); // Filter out nulls if a button isn't found
    console.log('[app.js] addProductButtons found:', addProductButtons);

    if (addProductButtons.length > 0) {
        function showButtonLoader(buttonElement) {
            console.log('[app.js] showButtonLoader called for button:', buttonElement);
            const textElement = buttonElement.querySelector('.button-text');
            const loaderElement = buttonElement.querySelector('.button-loader');
            console.log('[app.js] textElement:', textElement);
            console.log('[app.js] loaderElement:', loaderElement);

            if (textElement && loaderElement) {
                // Get current dimensions before hiding text
                const currentWidth = buttonElement.offsetWidth;
                const currentHeight = buttonElement.offsetHeight;
                console.log(`[app.js] Button current dimensions: ${currentWidth}x${currentHeight}`);

                // Apply fixed dimensions
                buttonElement.style.width = `${currentWidth}px`;
                buttonElement.style.height = `${currentHeight}px`;

                textElement.classList.add('hidden');
                loaderElement.classList.remove('hidden');
                console.log('[app.js] Loader shown, text hidden for button:', buttonElement.id);
                // buttonElement.disabled = true; // Optionally disable button
            } else {
                console.warn('[app.js] Could not find text or loader element inside button:', buttonElement.id);
            }
        }

        function resetButtonState(buttonElement) {
            console.log('[app.js] resetButtonState called for button:', buttonElement);
            const textElement = buttonElement.querySelector('.button-text');
            const loaderElement = buttonElement.querySelector('.button-loader');

            if (textElement && loaderElement) {
                textElement.classList.remove('hidden');
                loaderElement.classList.add('hidden');

                // Clear fixed dimensions
                buttonElement.style.width = '';
                buttonElement.style.height = '';
                console.log('[app.js] Button state reset for:', buttonElement.id);
                // buttonElement.disabled = false; // Re-enable if disabled
            } else {
                console.warn('[app.js] Could not find text or loader element to reset state for button:', buttonElement.id);
            }
        }

        addProductButtons.forEach(button => {
            console.log('[app.js] Attaching click listener to button:', button.id);
            button.addEventListener('click', function (event) {
                console.log('[app.js] Clicked button:', this.id);
                // Don't prevent default if it's a link, let it navigate.
                // The loader will show during the brief period before navigation.
                showButtonLoader(this);
                // If this were an AJAX action, you'd call resetButtonState in the callback.
            });
        });

        // Reset button states on page show (e.g., after back navigation)
        window.addEventListener('pageshow', function (event) {
            console.log('[app.js] pageshow event triggered.');
            addProductButtons.forEach(button => {
                // Add a small delay to ensure the reset doesn't interfere with
                // the loader showing if the navigation was extremely fast.
                setTimeout(() => {
                    resetButtonState(button);
                }, 50);
            });
        });
    } else {
        console.log('[app.js] No "Add Product" buttons found to attach listeners. This is expected on some pages.');
    }
    console.log('[app.js] Inline button loader setup complete.');


});

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('sidebar-search-input');
    const searchResults = document.getElementById('sidebar-search-results');
    const searchClear = document.getElementById('sidebar-search-clear');
    let timeout = null;

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (searchInput.value.length > 0) {
                searchClear.style.display = 'flex';
            } else {
                searchClear.style.display = 'none';
            }

            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const query = searchInput.value;

                if (query.length > 2) {
                    fetch(`/api/sidebar-search?query=${query}`)
                        .then(response => response.json())
                        .then(data => {
                            let html = '';

                            if (data.products.length > 0) {
                                html += '<div class="px-4 py-2 text-xs text-gray-500">Products</div>';
                                data.products.forEach(product => {
                                    const logoHtml = product.logo_url
                                        ? `<img src="${product.logo_url}" alt="${product.name}" class="w-8 h-8 mr-3 rounded-md object-cover">`
                                        : `<div class="w-8 h-8 mr-3 rounded-md bg-gray-20 flex items-center justify-center"><span class="text-xs font-semibold text-gray-500">${product.name.substring(0, 1)}</span></div>`;

                                    html += `<a href="/product/${product.slug}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                ${logoHtml}
                                                <span>${product.name}</span>
                                             </a>`;
                                });
                            }

                            if (data.categories.length > 0) {
                                html += '<div class="px-4 py-2 text-xs text-gray-500">Categories</div>';
                                data.categories.forEach(category => {
                                    html += `<a href="/categories/${category.slug}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">${category.name}</a>`;
                                });
                            }

                            if (html === '') {
                                html = '<div class="px-4 py-2 text-sm text-gray-500">No results found.</div>';
                            }

                            searchResults.innerHTML = html;
                            searchResults.style.display = 'block';
                        });
                } else {
                    searchResults.style.display = 'none';
                }
            }, 300);
        });

        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            searchResults.style.display = 'none';
            searchClear.style.display = 'none';
        });

        document.addEventListener('click', (e) => {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });
    }
});
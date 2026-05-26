// Components will be dynamically imported
import axios from 'axios';

// Add this line to set the CSRF token for all Axios requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const authSyncTabId = (() => {
    const existing = sessionStorage.getItem('authSyncTabId');

    if (existing) {
        return existing;
    }

    const generated = window.crypto?.randomUUID?.() ?? `tab-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    sessionStorage.setItem('authSyncTabId', generated);

    return generated;
})();

const authSyncChannel = 'BroadcastChannel' in window ? new BroadcastChannel('auth-sync') : null;

function showAuthFeedbackToast(message) {
    const existing = document.getElementById('auth-feedback-toast');

    if (existing) {
        existing.remove();
    }

    const toast = document.createElement('div');
    toast.id = 'auth-feedback-toast';
    toast.className = 'fixed right-5 top-5 z-[200] rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-lg';
    toast.textContent = message;
    document.body.appendChild(toast);
}

function publishAuthSyncEvent(type) {
    const payload = {
        type,
        origin: authSyncTabId,
        timestamp: Date.now(),
    };

    localStorage.setItem('auth-sync-event', JSON.stringify(payload));
    authSyncChannel?.postMessage(payload);
}

function handleIncomingAuthSync(payload) {
    if (!payload || payload.origin === authSyncTabId) {
        return;
    }

    const currentState = document.body?.dataset?.authSessionState ?? 'guest';
    const shouldRefresh =
        (payload.type === 'signed-in' && currentState !== 'authenticated') ||
        (payload.type === 'signed-out' && currentState !== 'guest');

    if (!shouldRefresh) {
        return;
    }

    showAuthFeedbackToast(
        payload.type === 'signed-in'
            ? 'Signed in in another tab. Refreshing...'
            : 'Signed out in another tab. Refreshing...'
    );

    window.setTimeout(() => {
        window.location.reload();
    }, 500);
}

window.addEventListener('storage', (event) => {
    if (event.key !== 'auth-sync-event' || !event.newValue) {
        return;
    }

    try {
        handleIncomingAuthSync(JSON.parse(event.newValue));
    } catch (error) {
        console.error('Unable to parse auth sync event:', error);
    }
});

authSyncChannel?.addEventListener('message', (event) => {
    handleIncomingAuthSync(event.data);
});

function firstValidationError(payload) {
    if (!payload || typeof payload !== 'object') {
        return null;
    }

    if (typeof payload.message === 'string' && payload.message.length > 0) {
        return payload.message;
    }

    if (!payload.errors || typeof payload.errors !== 'object') {
        return null;
    }

    const firstKey = Object.keys(payload.errors)[0];

    if (!firstKey || !Array.isArray(payload.errors[firstKey]) || payload.errors[firstKey].length === 0) {
        return null;
    }

    return payload.errors[firstKey][0];
}

function normalizeCollectionOptions(collections) {
    if (!Array.isArray(collections)) {
        return [];
    }

    return collections.map((collection) => ({
        id: collection.id ?? null,
        name: collection.name ?? '',
        visibility: collection.visibility ?? 'public',
        selected: Boolean(collection.selected),
        comment: collection.comment ?? '',
        is_default: Boolean(collection.is_default),
        default_name: collection.default_name ?? null,
        url: collection.url ?? null,
    }));
}

function registerAlpineComponents(Alpine) {
    Alpine.data('upvote', (isUpvoted, initialVotesCount, productId, productSlug, isAuthenticated, csrfToken) => ({
        isUpvoted: isUpvoted,
        votesCount: initialVotesCount,
        errorMessage: '',

        async toggleUpvote() {
            if (!isAuthenticated) {
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

    Alpine.data('productCollectionSaver', (config) => ({
        collections: normalizeCollectionOptions(config.collections),
        syncUrl: config.syncUrl,
        csrfToken: config.csrfToken,
        submitting: false,
        message: '',
        errorMessage: '',
        newCollection: {
            enabled: false,
            name: '',
            visibility: 'public',
            comment: '',
        },

        selectedCollectionsPayload() {
            return this.collections
                .filter((collection) => collection.selected)
                .map((collection) => ({
                    id: collection.id,
                    default_name: collection.default_name,
                    comment: typeof collection.comment === 'string' ? collection.comment.trim() : '',
                }));
        },

        newCollectionPayload() {
            if (!this.newCollection.enabled) {
                return {};
            }

            const name = this.newCollection.name.trim();

            if (name === '') {
                return {};
            }

            return {
                name,
                visibility: this.newCollection.visibility,
                comment: this.newCollection.comment.trim(),
            };
        },

        resetNewCollection() {
            this.newCollection = {
                enabled: false,
                name: '',
                visibility: 'public',
                comment: '',
            };
        },

        async save() {
            this.submitting = true;
            this.errorMessage = '';
            this.message = '';

            try {
                const response = await fetch(this.syncUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        collections: this.selectedCollectionsPayload(),
                        new_collection: this.newCollectionPayload(),
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    this.errorMessage = firstValidationError(payload) || 'We could not save your collections.';
                    return;
                }

                this.collections = normalizeCollectionOptions(payload.collections);
                this.resetNewCollection();
                this.message = payload.message || 'Saved to your collections.';

                window.dispatchEvent(new CustomEvent('product-collections-synced', {
                    detail: {
                        isSaved: Boolean(payload.is_saved),
                        savedCollectionCount: Number(payload.saved_collection_count ?? 0),
                    },
                }));

                window.setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'product-save-modal' }));
                }, 250);
            } catch (error) {
                console.error('Error saving product collections:', error);
                this.errorMessage = 'Network error occurred. Please try again.';
            } finally {
                this.submitting = false;
            }
        },
    }));
}

// Check if Alpine is already loaded (e.g., by Livewire) to avoid multiple instances
if (!window.Alpine) {
    import('alpinejs').then((Alpine) => {
        window.Alpine = Alpine.default;
        registerAlpineComponents(window.Alpine);
        window.Alpine.start();
    });
} else {
    registerAlpineComponents(window.Alpine);
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

// Mount mobile notification bell
if (document.getElementById('mobile-notification-bell-app')) {
    import('./components/NotificationBell.vue').then((NotificationBell) => {
        import('vue').then(({ createApp }) => {
            const mobileNotificationApp = createApp({});
            mobileNotificationApp.component('notification-bell', NotificationBell.default);
            mobileNotificationApp.mount('#mobile-notification-bell-app');
        });
    });
}

const prefetchedProductUrls = new Set();

function prefetchProductPage(url) {
    if (!url || prefetchedProductUrls.has(url)) {
        return;
    }

    prefetchedProductUrls.add(url);

    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.as = 'document';
    link.href = url;
    document.head.appendChild(link);
}

function isPrefetchableProductLink(anchor) {
    if (!(anchor instanceof HTMLAnchorElement) || !anchor.href) {
        return false;
    }

    if (anchor.hasAttribute('wire:navigate') || anchor.hasAttribute('wire:navigate.hover')) {
        return false;
    }

    const url = new URL(anchor.href, window.location.origin);

    return url.origin === window.location.origin
        && url.pathname.startsWith('/product/')
        && !url.search
        && !url.hash;
}

function warmProductPageNavigation() {
    const handleIntent = (event) => {
        const anchor = event.target instanceof Element ? event.target.closest('a[href]') : null;

        if (anchor && isPrefetchableProductLink(anchor)) {
            prefetchProductPage(anchor.href);
        }
    };

    document.addEventListener('mouseover', handleIntent, { passive: true });
    document.addEventListener('focusin', handleIntent);
    document.addEventListener('touchstart', handleIntent, { passive: true });
}

warmProductPageNavigation();

const seenProductImpressions = new Set();
const pendingProductImpressions = new Map();
let productImpressionFlushTimer = null;

function scheduleProductImpressionFlush() {
    if (productImpressionFlushTimer) {
        return;
    }

    productImpressionFlushTimer = window.setTimeout(() => {
        flushProductImpressions();
    }, 500);
}

function queueProductImpression(productId, surface) {
    if (!productId || !surface) {
        return;
    }

    const key = `${surface}:${productId}`;

    if (seenProductImpressions.has(key)) {
        return;
    }

    seenProductImpressions.add(key);

    if (!pendingProductImpressions.has(surface)) {
        pendingProductImpressions.set(surface, new Set());
    }

    pendingProductImpressions.get(surface).add(Number(productId));
    scheduleProductImpressionFlush();
}

function flushProductImpressions() {
    if (productImpressionFlushTimer) {
        window.clearTimeout(productImpressionFlushTimer);
        productImpressionFlushTimer = null;
    }

    if (pendingProductImpressions.size === 0) {
        return;
    }

    const payloads = Array.from(pendingProductImpressions.entries()).map(([surface, productIds]) => ({
        surface,
        products: Array.from(productIds),
    }));

    pendingProductImpressions.clear();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    payloads.forEach(({ surface, products }) => {
        fetch('/api/impressions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                surface,
                products,
            }),
            credentials: 'same-origin',
            keepalive: true,
        }).catch((error) => {
            console.error('Failed to record product impressions:', error);
        });
    });
}

function trackVisibleProductCards() {
    const cards = document.querySelectorAll('[data-track-impression="true"][data-product-id][data-impression-surface]');

    if (!cards.length || !('IntersectionObserver' in window)) {
        return;
    }

    const visibilityTimers = new WeakMap();
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const element = entry.target;

            if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                if (visibilityTimers.has(element)) {
                    return;
                }

                const timer = window.setTimeout(() => {
                    queueProductImpression(
                        element.dataset.productId,
                        element.dataset.impressionSurface
                    );
                    observer.unobserve(element);
                    visibilityTimers.delete(element);
                }, 800);

                visibilityTimers.set(element, timer);
            } else if (visibilityTimers.has(element)) {
                window.clearTimeout(visibilityTimers.get(element));
                visibilityTimers.delete(element);
            }
        });
    }, {
        threshold: [0.6],
    });

    cards.forEach((card) => observer.observe(card));
}

function trackProductDetailView() {
    const detailMetrics = document.getElementById('product-detail-metrics');

    if (!detailMetrics?.dataset?.productId) {
        return;
    }

    const sendDetailView = () => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        window.setTimeout(() => {
            if (document.visibilityState === 'visible') {
                queueProductImpression(detailMetrics.dataset.productId, 'product_detail');
            }
        }, 1200);
    };

    if (document.visibilityState === 'visible') {
        sendDetailView();
        return;
    }

    const handleVisibility = () => {
        if (document.visibilityState === 'visible') {
            sendDetailView();
            document.removeEventListener('visibilitychange', handleVisibility);
        }
    };

    document.addEventListener('visibilitychange', handleVisibility);
}

function bootstrapNavigatedPage() {
    trackVisibleProductCards();
    trackProductDetailView();
}

// Mount mobile user dropdown
if (document.getElementById('mobile-user-dropdown-app')) {
    const el = document.getElementById('mobile-user-dropdown-app');
    import('./components/UserDropdown.vue').then((UserDropdown) => {
        import('vue').then(({ createApp }) => {
            const mobileUserDropdownApp = createApp(UserDropdown.default, {
                user: JSON.parse(el.dataset.user),
                isAdmin: el.dataset.isAdmin === 'true',
            });
            mobileUserDropdownApp.mount('#mobile-user-dropdown-app');
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

if (document.querySelector('[data-article-editor-root]')) {
    import('./article-editor').then(({ initArticleEditors }) => {
        initArticleEditors();
    });
}

// Make Datepicker available globally for inline scripts

document.addEventListener('DOMContentLoaded', () => {
    const authSyncEvent = document.body?.dataset?.authSyncEvent;

    if (authSyncEvent) {
        publishAuthSyncEvent(authSyncEvent);
    }

    console.log('[app.js] DOMContentLoaded: Flowbite, Alpine initialized. Main script logic follows.');
    bootstrapNavigatedPage();

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

document.addEventListener('livewire:navigated', () => {
    bootstrapNavigatedPage();
});

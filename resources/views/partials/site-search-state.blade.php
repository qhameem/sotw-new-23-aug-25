x-data="{
        searchModalOpen: false,
        searchModalLoaded: true,
        searchModalLoading: false,
        searchModalError: '',
        open: false, // For mobile navigation
        initialPath: window.location.pathname, // Store the initial path
        intendedUrl: '',
        popularSearchContent: { products: [], categories: [] },
        popularSearchLoaded: false,
        popularSearchLoading: false,
        searchResults: { products: [], categories: [] },
        searchTrackingKeys: new Set(),
        searchTerm: '',
        searchLoading: false,
        searchController: null,
        hasSearchResults() {
            return this.searchResults.products.length > 0 || this.searchResults.categories.length > 0;
        },
        showDefaultSearchContent() {
            return this.searchTerm.trim().length < 2;
        },
        isTypingTarget(target) {
            if (!target) {
                return false;
            }

            const tagName = target.tagName ? target.tagName.toLowerCase() : '';
            const canCheckClosest = typeof target.closest === 'function';

            return target.isContentEditable
                || ['input', 'textarea', 'select'].includes(tagName)
                || (canCheckClosest && target.closest('[contenteditable]'));
        },
        resetSearchState() {
            if (this.searchController) {
                this.searchController.abort();
            }

            this.searchController = null;
            this.searchTrackingKeys = new Set();
            this.searchTerm = '';
            this.searchResults = { products: [], categories: [] };
            this.searchLoading = false;
        },
        normalizeTrackedSearchTerm(term) {
            return (term || '').trim().replace(/\s+/g, ' ').slice(0, 255);
        },
        queueSearchTracking(term, source = 'global_search_modal') {
            const query = this.normalizeTrackedSearchTerm(term);

            if (query.length < 2) {
                return;
            }

            const trackingKey = `${source}:${query.toLowerCase()}`;

            if (this.searchTrackingKeys.has(trackingKey)) {
                return;
            }

            this.searchTrackingKeys.add(trackingKey);

            try {
                const csrfToken = document.querySelector(`meta[name='csrf-token']`)?.getAttribute('content') || '';

                if (navigator.sendBeacon) {
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('query', query);
                    formData.append('source', source);

                    if (navigator.sendBeacon('{{ route('api.search.log') }}', formData)) {
                        return;
                    }
                }

                fetch('{{ route('api.search.log') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ query, source }),
                    keepalive: true,
                    credentials: 'same-origin',
                }).catch(() => {});
            } catch (error) {
                console.error('Search tracking error:', error);
            }
        },
        loadSearchModalContent() {
            this.searchModalError = '';
            this.searchModalLoaded = true;

            this.$nextTick(() => {
                document.getElementById('globalSearchInput')?.focus();
            });
        },
        openSearchModal() {
            this.resetSearchState();
            this.searchModalOpen = true;
            this.loadSearchModalContent();
            this.loadPopularSearchContent();
        },
        closeSearchModal() {
            this.queueSearchTracking(this.searchTerm);
            this.searchModalOpen = false;
            this.resetSearchState();
            this.searchModalError = '';
        },
        loadPopularSearchContent: async function() {
            if (this.popularSearchLoaded || this.popularSearchLoading) {
                return;
            }

            this.popularSearchLoading = true;

            try {
                const response = await fetch('/api/search/defaults');
                if (!response.ok) {
                    throw new Error('Failed to load search defaults.');
                }

                const data = await response.json();
                this.popularSearchContent = {
                    products: Array.isArray(data.products) ? data.products : [],
                    categories: Array.isArray(data.categories) ? data.categories : [],
                };
                this.popularSearchLoaded = true;
            } catch (error) {
                console.error('Popular search defaults error:', error);
                this.popularSearchContent = { products: [], categories: [] };
            } finally {
                this.popularSearchLoading = false;
            }
        },
        handleSearchShortcut(event) {
            const key = event.key ? event.key.toLowerCase() : '';

            if (key !== 'k' || (!event.metaKey && !event.ctrlKey) || event.altKey || event.shiftKey) {
                return;
            }

            if (this.isTypingTarget(event.target) && (!event.target || event.target.id !== 'globalSearchInput')) {
                return;
            }

            event.preventDefault();

            if (this.searchModalOpen) {
                const searchInput = document.getElementById('globalSearchInput');

                if (searchInput) {
                    searchInput.focus();
                }
                return;
            }

            this.openSearchModal();
        },
        performSearch: async function(term) {
            const query = term.trim();

            if (query.length < 2) {
                this.searchResults = { products: [], categories: [] };
                this.searchLoading = false;
                this.searchController = null;
                return;
            }

            if (this.searchController) {
                this.searchController.abort();
            }

            this.searchController = new AbortController();
            this.searchLoading = true;

            try {
                const response = await fetch(`/api/search?query=${encodeURIComponent(query)}`, {
                    signal: this.searchController.signal
                });

                if (response.ok) {
                    this.searchResults = await response.json();
                } else {
                    this.searchResults = { products: [], categories: [] };
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                    this.searchResults = { products: [], categories: [] };
                }
            } finally {
                this.searchLoading = false;
            }
        }
    }"

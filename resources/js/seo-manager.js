document.addEventListener('DOMContentLoaded', () => {
    const GLOBAL_DEFAULTS_PAGE_ID = 'global_defaults';
    const PLACEHOLDER_PREVIEW_URL = 'https://placehold.co/1200x630/e2e8f0/94a3b8?text=No+OG+image';
    const appDiv = document.getElementById('seo-manager-app');

    if (!appDiv) {
        console.error('SEO Manager app mount point not found.');
        return;
    }

    appDiv.innerHTML = `
        <div class="max-w-4xl mx-auto">
            <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                <h2 class="text-base font-semibold text-indigo-900">Default site-wide social image</h2>
                <p class="mt-1 text-sm text-indigo-800">This screen starts on the global fallback SEO record automatically, so the OG image you upload here applies to all pages that do not have a page-specific override.</p>
            </div>

            <div class="mb-4">
                <label for="page-selector" class="block text-sm font-medium text-gray-700">Optional: switch to a specific page override</label>
                <p class="mt-1 text-sm text-gray-500">Leave this on the default global entry if you want one OG image to cover the whole site.</p>
                <div class="relative">
                    <input type="text" id="page-search" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Search for a page override...">
                    <select id="page-selector" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" size="10" style="display: none;"></select>
                </div>
            </div>

            <form id="seo-form" class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-sm font-medium text-gray-700">Currently editing</p>
                    <p id="current-page-label" class="mt-1 text-sm text-gray-900">Loading global defaults...</p>
                </div>

                <div class="p-6 border border-gray-200 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Core Meta Tags</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="meta-title" class="block text-sm font-medium text-gray-700">Meta Title:</label>
                            <input type="text" id="meta-title" name="meta_title" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="meta-description" class="block text-sm font-medium text-gray-700">Meta Description:</label>
                            <textarea id="meta-description" name="meta_description" rows="4" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <div class="p-6 border border-gray-200 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Social Media Image (OG Image)</h3>
                    <p class="text-sm text-gray-500 mb-4">Recommended dimensions: 1200x630px. Accepted formats: JPG, JPEG, PNG, WEBP. Maximum size: 2 MB. Uploaded images are normalized to WEBP for storage.</p>
                    <div class="flex items-center space-x-6">
                        <div class="shrink-0">
                            <img id="og-image-preview" class="h-24 w-48 object-cover rounded-md border border-gray-200" src="${PLACEHOLDER_PREVIEW_URL}" alt="OG Image Preview">
                        </div>
                        <label for="og-image" class="cursor-pointer flex flex-col items-center justify-center">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <span class="mt-2 text-sm text-gray-600">Click to upload</span>
                            <input type="file" id="og-image" name="og_image" accept="image/jpeg,image/png,image/webp" class="hidden"/>
                        </label>
                    </div>
                </div>

                <input type="hidden" id="page-id" name="page_id">
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Save SEO Data
                    </button>
                </div>
                <div id="response-message" class="mt-4 text-sm text-center"></div>
            </form>
    `;

    const pageSearchInput = document.getElementById('page-search');
    const pageSelector = document.getElementById('page-selector');
    const metaTitleInput = document.getElementById('meta-title');
    const metaDescriptionInput = document.getElementById('meta-description');
    const pageIdInput = document.getElementById('page-id');
    const currentPageLabel = document.getElementById('current-page-label');
    const seoForm = document.getElementById('seo-form');
    const responseMessage = document.getElementById('response-message');
    const ogImageInput = document.getElementById('og-image');
    const ogImagePreview = document.getElementById('og-image-preview');
    const setPreviewImage = (imageUrl) => {
        ogImagePreview.src = imageUrl || PLACEHOLDER_PREVIEW_URL;
    };

    ogImageInput.addEventListener('change', () => {
        const file = ogImageInput.files[0];
        if (file) {
            setPreviewImage(URL.createObjectURL(file));
        }
    });

    let allPages = [];

    const setCurrentPage = (page) => {
        if (!page) {
            currentPageLabel.textContent = 'Unknown page';
            return;
        }

        pageIdInput.value = page.id || page.name;
        pageSearchInput.value = page.name;
        currentPageLabel.textContent = page.name;
    };

    const selectedPageFromId = (pageId) => allPages.find(page => (page.id || page.name) === pageId);

    // Fetch CSRF token
    const getCsrfToken = () => {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    };

    // Fetch Pages
    const fetchPages = async () => {
        try {
            const response = await fetch('/api/seo/pages');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            allPages = await response.json();
            populatePageSelector(allPages);
            const defaultPage = selectedPageFromId(GLOBAL_DEFAULTS_PAGE_ID);

            if (defaultPage) {
                setCurrentPage(defaultPage);
                await loadMetaData(defaultPage.id || defaultPage.name);
            }
        } catch (error) {
            console.error('Error fetching pages:', error);
            responseMessage.textContent = 'Error loading pages.';
            responseMessage.className = 'mt-4 text-sm text-red-600';
        }
    };

    const populatePageSelector = (pages) => {
        pageSelector.innerHTML = '';
        pages.forEach(page => {
            const option = document.createElement('option');
            // The API now returns 'id' (route name or 'global_defaults') and 'name' (friendly label)
            option.value = page.id || page.name;
            option.textContent = page.name;
            pageSelector.appendChild(option);
        });
    };

    // Searchable Dropdown
    pageSearchInput.addEventListener('input', () => {
        const searchTerm = pageSearchInput.value.toLowerCase();
        const filteredPages = allPages.filter(page =>
            page.name.toLowerCase().includes(searchTerm)
        );
        populatePageSelector(filteredPages);
        pageSelector.style.display = filteredPages.length > 0 ? 'block' : 'none';
    });

    pageSearchInput.addEventListener('focus', () => {
        pageSelector.style.display = pageSelector.options.length > 0 ? 'block' : 'none';
    });

    pageSelector.addEventListener('change', () => {
        const selectedOption = pageSelector.options[pageSelector.selectedIndex];
        if (selectedOption) {
            const selectedPage = selectedPageFromId(selectedOption.value);
            setCurrentPage(selectedPage || { id: selectedOption.value, name: selectedOption.textContent });
            pageSelector.style.display = 'none';
            loadMetaData(selectedOption.value);
        }
    });

    // Load Meta Data
    const loadMetaData = async (pageId) => {
        try {
            const response = await fetch(`/api/seo/meta/${pageId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            metaTitleInput.value = data.meta_title || '';
            metaDescriptionInput.value = data.meta_description || '';
            setPreviewImage(data.og_image_path || null);
            responseMessage.textContent = '';
        } catch (error) {
            console.error('Error fetching meta data:', error);
            metaTitleInput.value = '';
            metaDescriptionInput.value = '';
            setPreviewImage(null);
            responseMessage.textContent = 'Error loading meta data.';
            responseMessage.className = 'mt-4 text-sm text-red-600';
        }
    };

    // Save Meta Data
    seoForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const pageId = pageIdInput.value;
        if (!pageId) {
            responseMessage.textContent = 'The default global SEO record is still loading. Please wait a moment and try again.';
            responseMessage.className = 'mt-4 text-sm text-red-600';
            return;
        }

        const formData = new FormData(seoForm);
        // Find the selected page by id, not by name text
        const selectedPage = selectedPageFromId(pageId);
        formData.append('path', selectedPage ? selectedPage.uri : '');
        formData.append('page_id', selectedPage ? (selectedPage.id || selectedPage.name) : pageId);


        try {
            const response = await fetch('/api/seo/meta', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                responseMessage.textContent = result.message || 'SEO data saved successfully!';
                responseMessage.className = 'mt-4 text-sm text-green-600';
                ogImageInput.value = '';
                await loadMetaData(pageId);
            } else {
                const firstError = Object.values(result)[0];
                responseMessage.textContent = Array.isArray(firstError)
                    ? firstError[0]
                    : (typeof firstError === 'string' ? firstError : (result.message || 'Error saving SEO data.'));
                responseMessage.className = 'mt-4 text-sm text-red-600';
            }
        } catch (error) {
            console.error('Error saving SEO data:', error);
            responseMessage.textContent = 'An unexpected error occurred.';
            responseMessage.className = 'mt-4 text-sm text-red-600';
        }
    });

    // Initial fetch
    fetchPages();
});

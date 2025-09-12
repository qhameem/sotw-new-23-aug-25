document.addEventListener('DOMContentLoaded', () => {
    const appDiv = document.getElementById('seo-manager-app');

    if (!appDiv) {
        console.error('SEO Manager app mount point not found.');
        return;
    }

    appDiv.innerHTML = `
        <div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">SEO Management</h2>

            <div class="mb-4">
                <label for="page-selector" class="block text-sm font-medium text-gray-700">Select Page:</label>
                <div class="relative">
                    <input type="text" id="page-search" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Search for a page...">
                    <select id="page-selector" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" size="10" style="display: none;"></select>
                </div>
            </div>

            <form id="seo-form" class="space-y-6">
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
                    <p class="text-sm text-gray-500 mb-4">Recommended dimensions: 1200x630px. Accepted formats: JPG, PNG, GIF, SVG. Image will be converted to WEBP format.</p>
                    <div class="flex items-center space-x-6">
                        <div class="shrink-0">
                            <img id="og-image-preview" class="h-24 w-48 object-cover rounded-md border border-gray-200" src="https://placehold.co/1200x630/e2e8f0/e2e8f0" alt="OG Image Preview">
                        </div>
                        <label for="og-image" class="cursor-pointer flex flex-col items-center justify-center">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <span class="mt-2 text-sm text-gray-600">Click to upload</span>
                            <input type="file" id="og-image" name="og_image" class="hidden"/>
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
        </div>
    `;

    const pageSearchInput = document.getElementById('page-search');
    const pageSelector = document.getElementById('page-selector');
    const metaTitleInput = document.getElementById('meta-title');
    const metaDescriptionInput = document.getElementById('meta-description');
    const pageIdInput = document.getElementById('page-id');
    const seoForm = document.getElementById('seo-form');
    const responseMessage = document.getElementById('response-message');
    const ogImageInput = document.getElementById('og-image');
    const ogImagePreview = document.getElementById('og-image-preview');

    ogImageInput.addEventListener('change', () => {
        const file = ogImageInput.files[0];
        if (file) {
            ogImagePreview.src = URL.createObjectURL(file);
        }
    });

    let allPages = [];

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
            option.value = page.name;
            option.textContent = page.name;
            pageSelector.appendChild(option);
        });
        pageSelector.style.display = pages.length > 0 ? 'block' : 'none';
    };

    // Searchable Dropdown
    pageSearchInput.addEventListener('input', () => {
        const searchTerm = pageSearchInput.value.toLowerCase();
        const filteredPages = allPages.filter(page =>
            page.name.toLowerCase().includes(searchTerm)
        );
        populatePageSelector(filteredPages);
    });

    pageSearchInput.addEventListener('focus', () => {
        pageSelector.style.display = 'block';
    });

    pageSelector.addEventListener('change', () => {
        const selectedOption = pageSelector.options[pageSelector.selectedIndex];
        if (selectedOption) {
            pageSearchInput.value = selectedOption.textContent;
            pageIdInput.value = selectedOption.value;
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
            const ogImagePreview = document.getElementById('og-image-preview');
            if (data.og_image_path) {
                ogImagePreview.src = data.og_image_path;
            } else {
                ogImagePreview.src = 'https://placehold.co/1200x630/e2e8f0/e2e8f0';
            }
            responseMessage.textContent = '';
        } catch (error) {
            console.error('Error fetching meta data:', error);
            metaTitleInput.value = '';
            metaDescriptionInput.value = '';
            responseMessage.textContent = 'Error loading meta data.';
            responseMessage.className = 'mt-4 text-sm text-red-600';
        }
    };

    // Save Meta Data
    seoForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const pageId = pageIdInput.value;
        if (!pageId) {
            responseMessage.textContent = 'Please select a page.';
            responseMessage.className = 'mt-4 text-sm text-red-600';
            return;
        }

        const formData = new FormData(seoForm);
        const selectedPage = allPages.find(p => p.name === pageId);
        formData.append('path', selectedPage ? selectedPage.uri : '');
        formData.append('page_id', pageId);


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
                if (result.data && result.data.og_image_path) {
                    document.getElementById('og-image-preview').src = result.data.og_image_path;
                }
            } else {
                responseMessage.textContent = result.message || 'Error saving SEO data.';
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
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

            <form id="seo-form" class="space-y-4">
                <div>
                    <label for="meta-title" class="block text-sm font-medium text-gray-700">Meta Title:</label>
                    <input type="text" id="meta-title" name="meta_title" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="meta-description" class="block text-sm font-medium text-gray-700">Meta Description:</label>
                    <textarea id="meta-description" name="meta_description" rows="4" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                </div>
                <input type="hidden" id="page-id" name="page_id">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Save SEO Data</button>
                <div id="response-message" class="mt-4 text-sm"></div>
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
        const metaTitle = metaTitleInput.value;
        const metaDescription = metaDescriptionInput.value;
        const csrfToken = getCsrfToken();

        if (!pageId) {
            responseMessage.textContent = 'Please select a page.';
            responseMessage.className = 'mt-4 text-sm text-red-600';
            return;
        }

        try {
            const response = await fetch('/api/seo/meta', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    page_id: pageId,
                    meta_title: metaTitle,
                    meta_description: metaDescription
                })
            });

            const result = await response.json();

            if (response.ok) {
                responseMessage.textContent = result.message || 'SEO data saved successfully!';
                responseMessage.className = 'mt-4 text-sm text-green-600';
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
import './bootstrap';
import 'flowbite'; // This initializes Flowbite components based on data attributes
import { Datepicker as FlowbiteDatepicker } from 'flowbite';
import Alpine from 'alpinejs';
import upvote from './components/upvote';

// Initialize Alpine before DOMContentLoaded for better reliability
window.Alpine = Alpine;
Alpine.data('upvote', upvote);
Alpine.start();

// Make Datepicker available globally for inline scripts
if (typeof window.Flowbite === 'undefined') {
    window.Flowbite = {};
}
window.Flowbite.Datepicker = FlowbiteDatepicker;

document.addEventListener('DOMContentLoaded', () => {
    console.log('[app.js] DOMContentLoaded: Flowbite, Alpine initialized. Main script logic follows.');

    // Inline loader logic for "Add your product" buttons
    const addProductButtons = [
        document.getElementById('addProductBtnDesktop'),
        document.getElementById('addProductBtnMobile')
    ].filter(btn => btn !== null); // Filter out nulls if a button isn't found
    console.log('[app.js] addProductButtons found:', addProductButtons);

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

    if (addProductButtons.length > 0) {
        addProductButtons.forEach(button => {
            console.log('[app.js] Attaching click listener to button:', button.id);
            button.addEventListener('click', function(event) {
                console.log('[app.js] Clicked button:', this.id);
                // Don't prevent default if it's a link, let it navigate.
                // The loader will show during the brief period before navigation.
                showButtonLoader(this);
                // If this were an AJAX action, you'd call resetButtonState in the callback.
            });
        });
    } else {
        console.warn('[app.js] No "Add Product" buttons found to attach listeners.');
    }

    // Reset button states on page show (e.g., after back navigation)
    window.addEventListener('pageshow', function(event) {
        console.log('[app.js] pageshow event triggered.');
        if (addProductButtons.length > 0) {
            addProductButtons.forEach(button => {
                // Add a small delay to ensure the reset doesn't interfere with
                // the loader showing if the navigation was extremely fast.
                setTimeout(() => {
                    resetButtonState(button);
                }, 50);
            });
        }
    });
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
                                        : `<div class="w-8 h-8 mr-3 rounded-md bg-gray-200 flex items-center justify-center"><span class="text-xs font-semibold text-gray-500">${product.name.substring(0, 1)}</span></div>`;

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
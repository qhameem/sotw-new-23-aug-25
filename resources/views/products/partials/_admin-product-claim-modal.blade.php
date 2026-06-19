@php
    $productPageUrl = route('products.show', ['product' => $product->slug]);
@endphp

<x-modal name="admin-product-claim-modal" :show="false" maxWidth="lg" :scrollable="false" :hideScrollbar="true" viewportPadding="compact" focusable>
    <div class="px-6 py-6 sm:px-8"
        x-data="{
            copied: false,
            productUrl: @js($productPageUrl),
            async copyLink() {
                try {
                    await navigator.clipboard.writeText(this.productUrl);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                } catch (error) {
                    this.copied = false;
                }
            }
        }">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-lg font-semibold text-gray-900">Claim this product</div>
                <p class="mt-2 text-sm leading-6 text-gray-600">
                    Email <a href="mailto:hello@softwareontheweb.com" class="font-medium text-primary-600 hover:underline">hello@softwareontheweb.com</a> to claim this product.
                </p>
                <p class="mt-2 text-sm leading-6 text-gray-600">
                    Please include this product page link in your email so we can identify the listing quickly.
                </p>
            </div>

            <button type="button" @click="$dispatch('close-modal', 'admin-product-claim-modal')"
                class="rounded-full border border-gray-200 p-2 text-gray-400 transition hover:border-gray-300 hover:text-gray-600"
                aria-label="Close claim modal">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
            <label for="admin-product-claim-link" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                Product page link
            </label>
            <div class="mt-2 flex items-center gap-2">
                <input id="admin-product-claim-link" type="text" readonly :value="productUrl"
                    class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                <button type="button" @click="copyLink()"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:bg-gray-50 hover:text-gray-900">
                    <span x-show="!copied">Copy link</span>
                    <span x-show="copied" x-cloak>Copied!</span>
                </button>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="mailto:hello@softwareontheweb.com?subject={{ rawurlencode('Claim request for ' . $product->name) }}"
                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-black">
                Email hello@softwareontheweb.com
            </a>
            <button type="button" @click="$dispatch('close-modal', 'admin-product-claim-modal')"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50 hover:text-gray-900">
                Close
            </button>
        </div>
    </div>
</x-modal>

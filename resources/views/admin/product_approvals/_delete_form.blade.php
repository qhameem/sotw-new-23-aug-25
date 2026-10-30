<form action="{{ route('admin.product-approvals.destroy', $product) }}" method="POST"
    onsubmit="return confirm('Permanently delete this product? This cannot be undone.');">
    @csrf
    @method('DELETE')
    <button type="submit" aria-label="Delete {{ $product->name }}" title="Delete {{ $product->name }}"
        class="{{ $class ?? 'block w-full rounded px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950' }}">
        @if(isset($withIcon) && $withIcon)
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Delete</span>
        @else
            Delete
        @endif
    </button>
</form>

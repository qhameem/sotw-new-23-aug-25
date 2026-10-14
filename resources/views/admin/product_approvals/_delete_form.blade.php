<form action="{{ route('admin.product-approvals.destroy', $product) }}" method="POST"
    onsubmit="return confirm('Permanently delete this product? This cannot be undone.');">
    @csrf
    @method('DELETE')
    <button type="submit" aria-label="Delete {{ $product->name }}"
        class="block w-full rounded px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950">
        Delete
    </button>
</form>

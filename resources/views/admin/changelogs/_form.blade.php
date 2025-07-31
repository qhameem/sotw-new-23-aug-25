@csrf
<div class="mb-4">
    <label for="released_at" class="block text-gray-700 text-sm font-bold mb-2">Release Date:</label>
    <input type="date" name="released_at" id="released_at" value="{{ old('released_at', $changelog->released_at ?? now()->format('Y-m-d')) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
</div>
<div class="mb-4">
    <label for="version" class="block text-gray-700 text-sm font-bold mb-2">Version (Optional):</label>
    <input type="text" name="version" id="version" value="{{ old('version', $changelog->version ?? '') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
</div>
<div class="mb-4">
    <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Type:</label>
    <select name="type" id="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        <option value="added" @selected(old('type', $changelog->type ?? '') == 'added')>Added</option>
        <option value="changed" @selected(old('type', $changelog->type ?? '') == 'changed')>Changed</option>
        <option value="fixed" @selected(old('type', $changelog->type ?? '') == 'fixed')>Fixed</option>
        <option value="removed" @selected(old('type', $changelog->type ?? '') == 'removed')>Removed</option>
    </select>
</div>
<div class="mb-4">
    <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Title:</label>
    <input type="text" name="title" id="title" value="{{ old('title', $changelog->title ?? '') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
</div>
<div class="mb-4">
    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description (Optional):</label>
    <textarea name="description" id="description" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">{{ old('description', $changelog->description ?? '') }}</textarea>
</div>
<div class="flex items-center justify-between">
    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
        {{ isset($changelog) ? 'Update Entry' : 'Add Entry' }}
    </button>
    <a href="{{ route('admin.changelogs.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
        Cancel
    </a>
</div>
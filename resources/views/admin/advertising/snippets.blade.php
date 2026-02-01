<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-semibold mb-4">Add New Code Snippet</h2>
    <form action="{{ route('admin.code-snippets.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="page" class="block text-sm font-medium text-gray-700">Page</label>
                <select name="page" id="page"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="all">All Pages</option>
                    <option value="home">Home</option>
                    <option value="products.*">Products</option>
                    <option value="articles.*">Articles</option>
                </select>
            </div>
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                <select name="location" id="location"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    <option value="head">Head</option>
                    <option value="body">Body</option>
                    <option value="sidebar">Sidebar</option>
                </select>
            </div>
        </div>
        <div class="mt-6">
            <label for="code" class="block text-sm font-medium text-gray-700">Code</label>
            <textarea name="code" id="code" rows="5"
                class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
        </div>
        <div class="mt-6">
            <button type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Add Snippet
            </button>
        </div>
    </form>
</div>

<div class="mt-8 bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-semibold mb-4">Existing Snippets</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($snippets as $snippet)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $snippet->page }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $snippet->location }}</td>
                        <td class="px-6 py-4">
                            <pre
                                class="bg-gray-100 p-2 rounded-md text-sm text-gray-800"><code>{{ e($snippet->code) }}</code></pre>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <form action="{{ route('admin.code-snippets.destroy', $snippet->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
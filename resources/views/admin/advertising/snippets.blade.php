@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-8">
        <div class="py-8">
            <div>
                <h2 class="text-2xl font-semibold leading-tight">Code Snippets</h2>
            </div>
            <div class="my-5">
                <form action="{{ route('admin.advertising.snippets.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="page" class="block text-gray-700 text-sm font-bold mb-2">Page:</label>
                        <select name="page" id="page" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="all">All Pages</option>
                            <option value="home">Home Page</option>
                            <option value="product">Product Pages</option>
                            <option value="category">Category Pages</option>
                            <option value="article">Article Pages</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="location" class="block text-gray-700 text-sm font-bold mb-2">Location:</label>
                        <select name="location" id="location" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="head">Head</option>
                            <option value="body">Body (end)</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="code" class="block text-gray-700 text-sm font-bold mb-2">Code:</label>
                        <textarea name="code" id="code" rows="10" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Save Snippet
                        </button>
                    </div>
                </form>
            </div>
            <div class="-mx-4 sm:-mx-8 px-4 sm:px-8 py-4 overflow-x-auto">
                <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Page
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Location
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Code
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($snippets as $snippet)
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <p class="text-gray-900 whitespace-no-wrap">{{ $snippet->page }}</p>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <p class="text-gray-900 whitespace-no-wrap">{{ $snippet->location }}</p>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <pre class="text-gray-900 whitespace-pre-wrap">{{ htmlspecialchars($snippet->code) }}</pre>
                                    </td>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-right">
                                        <form action="{{ route('admin.advertising.snippets.destroy', $snippet) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this snippet?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
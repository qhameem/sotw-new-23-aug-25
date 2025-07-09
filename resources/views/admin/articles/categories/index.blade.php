<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800  leading-tight">
            {{ __('Article Categories Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white  overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 ">
                    <div class="mb-6 border-b border-gray-200 ">
                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="blogAdminTabs" role="tablist">
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('admin.articles.posts.index') }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request()->routeIs('admin.articles.posts.*') ? 'text-blue-600 border-blue-600  ' : 'border-transparent hover:text-gray-600 hover:border-gray-300 ' }}" role="tab">
                                    {{ __('Posts') }}
                                </a>
                            </li>
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('admin.articles.categories.index') }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request()->routeIs('admin.articles.categories.*') ? 'text-blue-600 border-blue-600  ' : 'border-transparent hover:text-gray-600 hover:border-gray-300 ' }}" role="tab">
                                    {{ __('Categories') }}
                                </a>
                            </li>
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('admin.articles.tags.index') }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request()->routeIs('admin.articles.tags.*') ? 'text-blue-600 border-blue-600  ' : 'border-transparent hover:text-gray-600 hover:border-gray-300 ' }}" role="tab">
                                    {{ __('Tags') }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">{{ __('All Article Categories') }}</h3>
                        <a href="{{ route('admin.articles.categories.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Create New Category') }}
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100  text-green-700  rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100  text-red-700  rounded-md">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Search Form -->
                    <div class="mb-6">
                        <form method="GET" action="{{ route('admin.articles.categories.index') }}">
                            <div class="flex items-center">
                                <x-text-input type="text" name="search" placeholder="{{ __('Search categories...') }}" class="mr-2 flex-grow" :value="request('search')" />
                                <x-primary-button type="submit">{{ __('Search') }}</x-primary-button>
                                @if(request('search') || request('sort_by'))
                                    <a href="{{ route('admin.articles.categories.index') }}" class="ml-2 text-sm text-gray-600  hover:text-gray-900 ">{{ __('Clear') }}</a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 ">
                            <thead class="bg-gray-50 ">
                                <tr>
                                    @php
                                        $sortableLink = fn($field, $displayName) => '<a href="'.route('admin.articles.categories.index', array_merge(request()->query(), ['sort_by' => $field, 'sort_direction' => request('sort_by') === $field && request('sort_direction') === 'asc' ? 'desc' : 'asc'])).'">'.$displayName.(request('sort_by') === $field ? (request('sort_direction') === 'asc' ? ' &uarr;' : ' &darr;') : '').'</a>';
                                    @endphp
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500  uppercase tracking-wider">
                                        {!! $sortableLink('name', __('Name')) !!}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500  uppercase tracking-wider">
                                        {!! $sortableLink('slug', __('Slug')) !!}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500  uppercase tracking-wider">
                                        {{ __('Description') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500  uppercase tracking-wider">
                                        {!! $sortableLink('articles_count', __('Posts Count')) !!}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500  uppercase tracking-wider">
                                        {{ __('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white  divide-y divide-gray-200 ">
                                @forelse ($categories as $category)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 ">
                                            {{ $category->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 ">
                                            {{ $category->slug }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 ">
                                            {{ Str::limit($category->description, 70) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 ">
                                            {{ $category->articles_count }} {{-- Use the count loaded by withCount --}}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.articles.categories.edit', $category) }}" class="text-indigo-600  hover:text-indigo-900  mr-2">{{ __('Edit') }}</a>
                                            <form action="{{ route('admin.articles.categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this category? This might affect posts associated with it.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600  hover:text-red-900 ">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500  text-center">
                                            {{ __('No article categories found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $categories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
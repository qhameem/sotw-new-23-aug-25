@extends('layouts.app')

@section('title', 'Article Tags Management')

@section('header-title')
    Article Tags
@endsection

@section('actions')
    <a href="{{ route('admin.articles.tags.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
        {{ __('Create New Tag') }}
    </a>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="blogAdminTabs" role="tablist">
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('admin.articles.posts.index') }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request()->routeIs('admin.articles.posts.*') ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}" role="tab">
                                    {{ __('Posts') }}
                                </a>
                            </li>
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('admin.articles.categories.index') }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request()->routeIs('admin.articles.categories.*') ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}" role="tab">
                                    {{ __('Categories') }}
                                </a>
                            </li>
                            <li class="mr-2" role="presentation">
                                <a href="{{ route('admin.articles.tags.index') }}" class="inline-block p-4 border-b-2 rounded-t-lg {{ request()->routeIs('admin.articles.tags.*') ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}" role="tab">
                                    {{ __('Tags') }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 dark:bg-green-700 text-green-700 dark:text-green-200 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                     @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 dark:bg-red-700 text-red-700 dark:text-red-200 rounded-md">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Search Form -->
                    <div class="mb-6">
                        <form method="GET" action="{{ route('admin.articles.tags.index') }}">
                            <div class="flex items-center">
                                <x-text-input type="text" name="search" placeholder="{{ __('Search tags...') }}" class="mr-2 flex-grow" :value="request('search')" />
                                <x-primary-button type="submit">{{ __('Search') }}</x-primary-button>
                                @if(request('search') || request('sort_by'))
                                    <a href="{{ route('admin.articles.tags.index') }}" class="ml-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">{{ __('Clear') }}</a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    @php
                                        $sortableLink = fn($field, $displayName) => '<a href="'.route('admin.articles.tags.index', array_merge(request()->query(), ['sort_by' => $field, 'sort_direction' => request('sort_by') === $field && request('sort_direction') === 'asc' ? 'desc' : 'asc'])).'">'.$displayName.(request('sort_by') === $field ? (request('sort_direction') === 'asc' ? ' &uarr;' : ' &darr;') : '').'</a>';
                                    @endphp
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {!! $sortableLink('name', __('Name')) !!}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {!! $sortableLink('slug', __('Slug')) !!}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {!! $sortableLink('articles_count', __('Posts Count')) !!}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($tags as $tag)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $tag->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ $tag->slug }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ $tag->articles_count }} {{-- Use the count loaded by withCount --}}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.articles.tags.edit', $tag) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 mr-2">{{ __('Edit') }}</a>
                                            <form action="{{ route('admin.articles.tags.destroy', $tag) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this tag? This might affect posts associated with it.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-center">
                                            {{ __('No article tags found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $tags->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
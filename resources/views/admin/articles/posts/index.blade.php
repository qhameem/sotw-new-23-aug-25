@extends('layouts.app')

@section('title', 'Articles Management')

@section('header-title')
    Articles
@endsection

@section('actions')
    <x-primary-button onclick="window.location.href='{{ route('admin.articles.posts.create') }}';">
        {{ __('Create New Post') }}
    </x-primary-button>
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

                    <!-- TODO: Add search and filter controls -->

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Title') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Author') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Categories') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Tags') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Status') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Published At') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Staff Pick') }}
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($posts as $post)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{-- Link to edit by ID now --}}
                                            <a href="{{ route('admin.articles.posts.edit', $post->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200">{{ Str::limit($post->title, 50) }}</a>
                                            {{-- Public view link still uses slug, conditional on publish status --}}
                                            @if($post->slug && $post->status === 'published' && ($post->published_at && $post->published_at <= now()))
                                                <a href="{{ route('articles.show', $post->slug) }}" target="_blank" class="ml-2 text-xs text-green-500 hover:text-green-700" title="View live public post">(View Public)</a>
                                            @elseif($post->slug)
                                                <span class="ml-2 text-xs text-gray-400" title="Post is not live (draft, scheduled, or invalid slug)">(Preview N/A)</span>
                                            @else
                                                <span class="ml-2 text-xs text-gray-400">(No slug)</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ $post->author->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            @foreach($post->categories as $category)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200">
                                                    {{ $category->name }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            @foreach($post->tags as $tag)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-200">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($post->status == 'published') bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-200
                                                @elseif($post->status == 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-200
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200 @endif">
                                                {{ ucfirst($post->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            {{ $post->published_at ? $post->published_at->format('M d, Y H:i') : 'Not Published' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form action="{{ route('admin.articles.posts.toggleStaffPick', $post->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="text-white font-bold py-2 px-4 rounded {{ $post->staff_pick ? 'bg-green-500' : 'bg-gray-500' }}">
                                                    {{ $post->staff_pick ? 'Yes' : 'No' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            {{-- Edit link uses ID --}}
                                            <a href="{{ route('admin.articles.posts.edit', $post->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 mr-2">{{ __('Edit') }}</a>
                                            {{-- Delete form uses ID --}}
                                            <form action="{{ route('admin.articles.posts.destroy', $post->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-center">
                                            {{ __('No articles found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $posts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
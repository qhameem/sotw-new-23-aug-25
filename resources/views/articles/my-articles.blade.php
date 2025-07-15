<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('My Articles') }}
            </h2>
            <a href="{{ route('articles.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Write Article') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($posts->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($posts as $post)
                                <div class="p-4 border rounded-lg">
                                    <h3 class="text-lg font-semibold">{{ $post->title }}</h3>
                                    <p class="text-sm text-gray-600">Status: {{ ucfirst($post->status) }}</p>
                                    <p class="text-sm text-gray-500">Created: {{ $post->created_at->format('M d, Y') }}</p>
                                    <!-- Add edit/delete links here if needed in the future -->
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <p>You haven't written any articles yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
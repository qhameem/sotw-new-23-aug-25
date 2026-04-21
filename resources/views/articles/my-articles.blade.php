<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Articles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($articles->isEmpty())
                        <p>You have not written any articles yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($articles as $article)
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <a href="{{ route('articles.edit', ['article' => $article->id]) }}" class="text-lg font-semibold text-gray-900 hover:text-primary-600">
                                                {{ $article->title }}
                                            </a>
                                            <p class="mt-1 text-sm text-gray-600">
                                                Status: <span class="font-medium">{{ ucfirst($article->status) }}</span>
                                                • Updated {{ $article->updated_at->format('M d, Y') }}
                                            </p>
                                        </div>

                                        <div class="flex gap-3 text-sm">
                                            <a href="{{ route('articles.edit', ['article' => $article->id]) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                                Edit
                                            </a>
                                            <a href="{{ route('articles.preview', ['article' => $article->id]) }}" class="font-medium text-gray-600 hover:text-gray-800">
                                                Preview
                                            </a>
                                            @if($article->status === 'published' && $article->published_at && $article->published_at <= now())
                                                <a href="{{ route('articles.show', ['article' => $article->slug]) }}" class="font-medium text-gray-600 hover:text-gray-800" target="_blank" rel="noopener">
                                                    View Live
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $articles->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

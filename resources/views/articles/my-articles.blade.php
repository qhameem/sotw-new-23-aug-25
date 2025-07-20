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
                        <ul>
                            @foreach($articles as $article)
                                <li>
                                    <a href="{{ route('articles.show', $article) }}" class="text-lg font-semibold">{{ $article->title }}</a>
                                    <p class="text-sm text-gray-600">{{ $article->created_at->format('M d, Y') }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
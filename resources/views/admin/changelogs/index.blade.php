@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Changelog</h1>
        <a href="{{ route('admin.changelogs.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add New Entry
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg">
        <div class="p-6">
            @forelse($changelogs as $date => $logs)
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">{{ $date }}</h2>
                    @foreach($logs as $log)
                        <div class="mb-4 p-4 border rounded-lg">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full
                                        @switch($log->type)
                                            @case('added') bg-green-100 text-green-800 @break
                                            @case('changed') bg-yellow-100 text-yellow-800 @break
                                            @case('fixed') bg-blue-100 text-blue-800 @break
                                            @case('removed') bg-red-100 text-red-800 @break
                                        @endswitch
                                    ">{{ ucfirst($log->type) }}</span>
                                    <h3 class="text-lg font-bold mt-2">{{ $log->title }}</h3>
                                    @if($log->version)
                                        <span class="text-sm text-gray-500">{{ $log->version }}</span>
                                    @endif
                                    @if($log->description)
                                        <div class="prose prose-sm max-w-none mt-2 text-gray-700">
                                            {!! $log->description !!}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2 flex-shrink-0 ml-4">
                                    <a href="{{ route('admin.changelogs.edit', $log) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    <form action="{{ route('admin.changelogs.destroy', $log) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="text-gray-500">No changelog entries yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
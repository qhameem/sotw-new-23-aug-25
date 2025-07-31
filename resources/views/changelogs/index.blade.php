@extends('layouts.app')

@section('title', 'Changelog')

@section('actions')
    <div class="md:flex items-center space-x-2">
        <a href="{{ route('topics.index') }}" class="bg-white hover:bg-gray-100 text-gray-800 border border-gray-300 text-sm font-semibold py-1 px-3 rounded-md transition duration-300 shadow-sm">
            Categories
        </a>
        <x-add-product-button />
    </div>
@endsection

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        @forelse($changelogs as $date => $logs)
            <div class="mb-12">
                <h2 class="text-sm font-bold text-gray-700 border-b pb-3 mb-4">{{ $date }}</h2>
                <div class="space-y-7">
                    @foreach($logs as $log)
                        <div>
                            <div class="flex items-center space-x-2">
                                
                                <h3 class="text-sm font-medium">{{ $log->title }}</h3>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-lg
                                    @switch($log->type)
                                        @case('added') bg-green-100 text-green-800 @break
                                        @case('changed') bg-yellow-100 text-yellow-800 @break
                                        @case('fixed') bg-blue-100 text-blue-800 @break
                                        @case('removed') bg-red-100 text-red-800 @break
                                    @endswitch
                                ">{{ ucfirst($log->type) }}</span>
                                @if($log->version)
                                    <span class="text-sm text-gray-500">{{ $log->version }}</span>
                                @endif
                            </div>
                            @if($log->description)
                                <div class="text-sm prose prose-sm max-w-none mt-2 text-gray-700">
                                    {!! $log->description !!}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center">No changelog entries yet.</p>
        @endforelse
    </div>
</div>
@endsection
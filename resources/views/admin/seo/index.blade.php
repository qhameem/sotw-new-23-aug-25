@extends('layouts.app')

@section('title')
    <h1 class="text-xl font-semibold text-gray-800">
        {{ __('SEO Management') }}
    </h1>
@endsection

@section('content')
    <div class="p-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div id="seo-manager-app"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/seo-manager.js')
@endpush
@extends('layouts.app', ['title' => __('SEO Management')])

@section('content')
    <div class="p-4">
        <x-breadcrumbs>
            <x-breadcrumbs.item :url="route('admin.settings.index')">{{ __('Admin') }}</x-breadcrumbs.item>
            <x-breadcrumbs.item :last="true">{{ __('SEO Management') }}</x-breadcrumbs.item>
        </x-breadcrumbs>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-4">
            <div class="p-6 text-gray-900">
                <div id="seo-manager-app"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/seo-manager.js')
@endpush
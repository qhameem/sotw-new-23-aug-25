@extends('layouts.app', ['title' => __('SEO Management')])

@section('header-title')
    SEO Management
@endsection

@section('below_header')
    <div class="p-4">
        <x-breadcrumbs>
            <x-breadcrumbs.item :url="route('admin.settings.index')">{{ __('Admin') }}</x-breadcrumbs.item>
            <x-breadcrumbs.item :last="true">{{ __('SEO Management') }}</x-breadcrumbs.item>
        </x-breadcrumbs>
    </div>
@endsection

@section('content')
    <div class="p-4">
        <div id="seo-manager-app"></div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/seo-manager.js')
@endpush
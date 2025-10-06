@extends('layouts.app')

@section('title', 'Advertising Management')

@section('header-title')
    Advertising
@endsection

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeTab: 'ads' }">
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'ads'"
                   class="inline-block p-4 border-b-2 rounded-t-lg"
                   :class="{ 'text-blue-600 border-blue-600': activeTab === 'ads', 'border-transparent hover:text-gray-600 hover:border-gray-300': activeTab !== 'ads' }">
                    Ads
                </a>
            </li>
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'ad_zones'"
                   class="inline-block p-4 border-b-2 rounded-t-lg"
                   :class="{ 'text-blue-600 border-blue-600': activeTab === 'ad_zones', 'border-transparent hover:text-gray-600 hover:border-gray-300': activeTab !== 'ad_zones' }">
                    Ad Zones
                </a>
            </li>
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'code_snippets'"
                   class="inline-block p-4 border-b-2 rounded-t-lg"
                   :class="{ 'text-blue-600 border-blue-600': activeTab === 'code_snippets', 'border-transparent hover:text-gray-600 hover:border-gray-300': activeTab !== 'code_snippets' }">
                    Code Snippets
                </a>
            </li>
        </ul>
    </div>

    <div x-show="activeTab === 'ads'">
        @include('admin.ads.index', ['ads' => $ads])
    </div>

    <div x-show="activeTab === 'ad_zones'">
        @include('admin.ad_zones.index', ['adZones' => $adZones])
    </div>

    <div x-show="activeTab === 'code_snippets'">
        @include('admin.advertising.snippets', ['snippets' => $snippets])
    </div>
</div>
@endsection
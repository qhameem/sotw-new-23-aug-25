@extends('layouts.app')

@section('title', 'Manage Ads')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 ">Manage Ads</h1>
        <a href="{{ route('admin.ads.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Create New Ad
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- TODO: Add Filtering and Sorting options here --}}

    <div class="bg-white  shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200  bg-gray-100  text-left text-xs font-semibold text-gray-600  uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200  bg-gray-100  text-left text-xs font-semibold text-gray-600  uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200  bg-gray-100  text-left text-xs font-semibold text-gray-600  uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200  bg-gray-100  text-left text-xs font-semibold text-gray-600  uppercase tracking-wider">
                        Zones
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200  bg-gray-100  text-left text-xs font-semibold text-gray-600  uppercase tracking-wider">
                        Schedule
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200  bg-gray-100  text-left text-xs font-semibold text-gray-600  uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ads as $ad)
                    <tr class="hover:bg-gray-50 ">
                        <td class="px-5 py-4 border-b border-gray-200  text-sm text-gray-900 ">
                            {{ $ad->internal_name }}
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200  text-sm text-gray-900 ">
                            {{ ucwords(str_replace('_', ' ', $ad->type)) }}
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200  text-sm">
                            @if($ad->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                            @endif
                            {{-- TODO: Add status toggle button --}}
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200  text-sm text-gray-900 ">
                            @foreach($ad->adZones as $zone)
                                <span class="bg-gray-200  text-gray-700  px-2 py-1 text-xs rounded-full mr-1 mb-1 inline-block">{{ $zone->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200  text-sm text-gray-900 ">
                            @if($ad->start_date) Start: {{ $ad->start_date->format('Y-m-d H:i') }}<br> @endif
                            @if($ad->end_date) End: {{ $ad->end_date->format('Y-m-d H:i') }} @endif
                            @if(!$ad->start_date && !$ad->end_date) Always Active @endif
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200  text-sm">
                            <a href="{{ route('admin.ads.edit', $ad) }}" class="text-indigo-600 hover:text-indigo-900   mr-3">Edit</a>
                            <form action="{{ route('admin.ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ad?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900  ">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500 ">
                            No ads found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $ads->links() }}
    </div>
</div>
@endsection
<div class="container mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Ads</h2>
            <p class="text-sm text-gray-500 mt-1">Unified inventory for sponsor ads, banners, inline placements, and snippets.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.ads.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create Ad
            </a>
            <a href="{{ route('admin.ads.create', ['template' => 'sponsor']) }}" class="bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 font-bold py-2 px-4 rounded">
                Sponsor Preset
            </a>
        </div>
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

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Preview</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ad</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Target</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Zones</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Schedule</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ads as $ad)
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            @if($ad->type === 'image_banner' && $ad->image_url)
                                <img src="{{ $ad->image_url }}" alt="{{ $ad->internal_name }}" class="h-16 w-24 rounded object-cover border border-gray-200">
                            @elseif($ad->type === 'product_listing_card' && $ad->image_url)
                                <div class="flex items-center gap-3 max-w-[14rem] rounded-lg border border-gray-200 bg-white p-2">
                                    <img src="{{ $ad->image_url }}" alt="{{ $ad->internal_name }}" class="h-12 w-12 rounded-xl object-cover border border-gray-100">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold truncate">{{ $ad->internal_name }}</div>
                                        @if($ad->tagline)
                                            <div class="text-xs text-gray-500 line-clamp-2">{{ $ad->tagline }}</div>
                                        @endif
                                    </div>
                                </div>
                            @elseif($ad->type === 'text_link')
                                <div class="max-w-[10rem] text-blue-600 line-clamp-2">{{ $ad->content }}</div>
                            @else
                                <div class="max-w-[12rem] text-xs text-gray-500 line-clamp-3">{{ strip_tags($ad->content) }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            <div class="font-semibold">{{ $ad->internal_name }}</div>
                            <div class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $ad->type)) }}</div>
                            @if($ad->tagline)
                                <div class="text-xs text-gray-500 mt-1">{{ $ad->tagline }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            @if($ad->target_url)
                                <a href="{{ $ad->target_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 break-all">{{ $ad->target_url }}</a>
                            @else
                                <span class="text-gray-400">No outbound URL</span>
                            @endif
                            <div class="text-xs text-gray-500 mt-2">
                                Impressions {{ number_format($ad->impressions_count) }} · Clicks {{ number_format($ad->clicks_count) }}
                            </div>
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            @forelse($ad->adZones as $zone)
                                <div class="mb-2 rounded-lg bg-gray-100 px-3 py-2">
                                    <div class="font-medium text-gray-800">{{ $zone->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $zone->slug }} · {{ $zone->render_location ?: $zone->description }}</div>
                                </div>
                            @empty
                                <span class="text-gray-400">Unassigned</span>
                            @endforelse
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm">
                            @php
                                $statusStyles = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'scheduled' => 'bg-yellow-100 text-yellow-800',
                                    'expired' => 'bg-gray-200 text-gray-700',
                                    'inactive' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusStyles[$ad->effective_status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($ad->effective_status) }}
                            </span>
                            @if($ad->is_house_ad)
                                <div class="text-xs text-gray-500 mt-2">House ad fallback enabled</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            @if($ad->start_date)
                                <div>Starts {{ $ad->start_date->format('Y-m-d H:i') }}</div>
                            @endif
                            @if($ad->end_date)
                                <div>Ends {{ $ad->end_date->format('Y-m-d H:i') }}</div>
                            @endif
                            @if(!$ad->start_date && !$ad->end_date)
                                <div>Always on</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm">
                            <div class="flex flex-col gap-2">
                                <form action="{{ route('admin.ads.toggle-active', $ad) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-left text-indigo-600 hover:text-indigo-900">
                                        {{ $ad->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.ads.duplicate', $ad) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-left text-slate-700 hover:text-slate-900">Duplicate</button>
                                </form>
                                <a href="{{ route('admin.ads.edit', $ad) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                <form action="{{ route('admin.ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ad?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-left text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-500">
                            No ads found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $ads->appends(['tab' => 'ads'])->links() }}
    </div>
</div>

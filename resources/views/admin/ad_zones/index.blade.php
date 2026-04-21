<div class="container mx-auto">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Ad Zones</h2>
            <p class="text-sm text-gray-500 mt-1">Operational view of every placement, including capacity, supported types, and active inventory.</p>
        </div>
        <a href="{{ route('admin.ad-zones.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Create Ad Zone
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Zone</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Renders</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rules</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Inventory</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($adZones as $adZone)
                    @php
                        $activeAds = $adZone->ads->filter(fn ($ad) => $ad->isEligibleAt());
                    @endphp
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            <div class="font-semibold">{{ $adZone->name }}</div>
                            <div class="text-xs text-gray-500">{{ $adZone->slug }}</div>
                            @if($adZone->description)
                                <div class="text-xs text-gray-500 mt-1">{{ $adZone->description }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            <div>{{ $adZone->render_location ?: 'Not documented yet' }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ ucfirst(str_replace('_', ' ', $adZone->placement_type)) }} placement</div>
                            @if($adZone->display_after_nth_product)
                                <div class="text-xs text-gray-500 mt-1">After product {{ $adZone->display_after_nth_product }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            <div>Supports: {{ implode(', ', $adZone->supported_ad_types ?: \App\Models\AdZone::SUPPORTED_AD_TYPES) }}</div>
                            <div class="text-xs text-gray-500 mt-1">Max ads {{ $adZone->max_ads }} · {{ ucfirst($adZone->rotation_mode) }}</div>
                            <div class="text-xs text-gray-500 mt-1">Device {{ ucfirst($adZone->device_scope) }} · Fallback {{ ucfirst(str_replace('_', ' ', $adZone->fallback_mode)) }}</div>
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900">
                            <div>{{ $activeAds->count() }} active / {{ $adZone->ads->count() }} assigned</div>
                            <div class="text-xs mt-1 {{ $activeAds->isEmpty() ? 'text-red-600' : 'text-gray-500' }}">
                                {{ $activeAds->isEmpty() ? 'Zone is currently empty' : 'Zone has eligible ads' }}
                            </div>
                        </td>
                        <td class="px-5 py-4 border-b border-gray-200 text-sm">
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('admin.ad-zones.edit', $adZone) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                <form action="{{ route('admin.ad-zones.destroy', $adZone) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this ad zone?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-left text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-gray-500">
                            No ad zones found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $adZones->appends(['tab' => 'ad_zones'])->links() }}
    </div>
</div>

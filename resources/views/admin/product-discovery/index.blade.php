@extends('layouts.app')

@section('title', 'Product Discovery')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Product discovery</h1>
        <p class="mt-1 text-sm text-gray-600">Sources are scanned daily. RSS/Atom feeds work automatically. HTML sources can use simple CSS selectors or XPath.</p>
    </div>

    @if(session('success'))
        <div class="rounded bg-green-100 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded bg-red-100 px-4 py-3 text-red-800">{{ $errors->first() }}</div>
    @endif

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editSource ? 'Edit source' : 'Add source' }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $editSource ? 'Update the source configuration, then save your changes.' : 'Add a page or feed. Extraction settings are optional for RSS and Atom feeds.' }}</p>
        </div>
        <form method="POST" action="{{ $editSource ? route('admin.product-discovery.sources.update', $editSource) : route('admin.product-discovery.sources.store') }}" class="space-y-6 p-6">
            @csrf
            @if($editSource) @method('PUT') @endif
            <div>
                <label for="source-url" class="block text-sm font-medium text-gray-700">Source URL</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                    <input id="source-url" name="url" type="url" value="{{ old('url', $editSource?->url) }}" required placeholder="https://example.com/launches" class="block min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button id="fetch-source-details" type="button" class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 shadow-sm hover:bg-blue-50">Fetch details</button>
                </div>
                <p id="fetch-source-status" class="mt-2 hidden text-sm"></p>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                <div class="lg:col-span-9">
                    <label for="source-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input id="source-name" name="name" value="{{ old('name', $editSource?->name) }}" required placeholder="Fetched automatically" class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="lg:col-span-3">
                    <label for="source-type" class="block text-sm font-medium text-gray-700">Source type</label>
                    <select id="source-type" name="type" class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="auto" @selected(old('type', $editSource?->type) === 'auto')>Auto detect</option>
                        <option value="feed" @selected(old('type', $editSource?->type) === 'feed')>RSS/Atom</option>
                        <option value="html" @selected(old('type', $editSource?->type) === 'html')>HTML</option>
                    </select>
                </div>
            </div>

            <fieldset class="rounded-lg border border-gray-200 bg-gray-50 p-5">
                <legend class="px-2 text-sm font-semibold text-gray-700">HTML extraction settings</legend>
                <p class="mb-4 text-xs text-gray-500">Leave blank for automatic extraction. Supports tag, .class, #id, or XPath.</p>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="item-selector" class="block text-sm font-medium text-gray-700">Item selector</label>
                        <input id="item-selector" name="item_selector" value="{{ old('item_selector', $editSource?->item_selector) }}" placeholder="article or .item" class="mt-2 block w-full rounded-lg border-gray-300 bg-white shadow-sm">
                    </div>
                    <div>
                        <label for="link-selector" class="block text-sm font-medium text-gray-700">Link selector</label>
                        <input id="link-selector" name="link_selector" value="{{ old('link_selector', $editSource?->link_selector) }}" placeholder="a or .title" class="mt-2 block w-full rounded-lg border-gray-300 bg-white shadow-sm">
                    </div>
                    <div>
                        <label for="title-selector" class="block text-sm font-medium text-gray-700">Title selector</label>
                        <input id="title-selector" name="title_selector" value="{{ old('title_selector', $editSource?->title_selector) }}" placeholder="h2" class="mt-2 block w-full rounded-lg border-gray-300 bg-white shadow-sm">
                    </div>
                    <div>
                        <label for="description-selector" class="block text-sm font-medium text-gray-700">Description selector</label>
                        <input id="description-selector" name="description_selector" value="{{ old('description_selector', $editSource?->description_selector) }}" placeholder=".tagline" class="mt-2 block w-full rounded-lg border-gray-300 bg-white shadow-sm">
                    </div>
                </div>
            </fieldset>

            <div class="flex flex-col gap-5 border-t border-gray-200 pt-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-end gap-6">
                    <div class="w-32">
                        <label for="daily-limit" class="block text-sm font-medium text-gray-700">Daily limit</label>
                        <input id="daily-limit" name="max_items" type="number" min="1" max="100" value="{{ old('max_items', $editSource?->max_items ?? 30) }}" required class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm">
                    </div>
                    <label class="flex h-10 items-center gap-2 text-sm font-medium text-gray-700">
                        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $editSource?->is_active ?? true)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Scan daily
                    </label>
                </div>
                <div class="flex items-center gap-4">
                    @if($editSource)
                        <a href="{{ route('admin.product-discovery.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    @endif
                    <x-primary-button type="submit" class="min-w-36 justify-center px-6 py-3 text-sm">
                        {{ $editSource ? 'Save changes' : 'Add source' }}
                    </x-primary-button>
                </div>
            </div>
        </form>
    </section>

    <section class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold">Sources</h2></div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Source</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Last scan</th><th class="px-4 py-3 text-left">Items</th><th class="px-4 py-3"></th></tr></thead>
            <tbody class="divide-y divide-gray-200">
            @forelse($sources as $source)
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            <div class="relative mt-0.5 h-9 w-9 shrink-0">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-500">{{ mb_strtoupper(mb_substr($source->name, 0, 1)) }}</div>
                                @if($source->faviconUrl())
                                    <img src="{{ $source->faviconUrl() }}" alt="" loading="lazy" class="absolute inset-0 h-9 w-9 rounded-lg border border-gray-200 bg-white object-contain p-1" onerror="this.remove()">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="font-medium text-gray-900">{{ $source->name }}</div>
                                <a href="{{ $source->url }}" target="_blank" rel="noopener" class="block break-all text-blue-600">{{ $source->url }}</a>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $sourceStatus = $source->last_error ? 'Scan error' : ($source->is_active ? 'Active' : 'Paused');
                            $sourceStatusColor = $source->last_error ? '#ef4444' : ($source->is_active ? '#22c55e' : '#f59e0b');
                        @endphp
                        <span class="inline-flex h-8 w-8 items-center justify-center" title="{{ $sourceStatus }}" aria-label="{{ $sourceStatus }}">
                            <span class="block h-3 w-3 rounded-full" style="background-color: {{ $sourceStatusColor }}; box-shadow: 0 0 0 4px {{ $sourceStatusColor }}26;"></span>
                            <span class="sr-only">{{ $sourceStatus }}</span>
                        </span>
                        @if($source->last_error)<div class="mt-1 max-w-xs text-xs text-red-600">{{ $source->last_error }}</div>@endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap" title="{{ $source->last_scanned_at?->toDayDateTimeString() ?? 'Never scanned' }}">
                        {{ $source->last_scanned_at?->shortAbsoluteDiffForHumans() ?? 'Never' }}
                    </td>
                    <td class="px-4 py-3">{{ $source->recommendations_count }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <div class="inline-flex items-center gap-1">
                            <form method="POST" action="{{ route('admin.product-discovery.sources.scan', $source) }}">
                                @csrf
                                <button title="Scan now" aria-label="Scan {{ $source->name }} now" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <x-phosphor-arrow-clockwise class="h-5 w-5" />
                                </button>
                            </form>
                            <a href="{{ route('admin.product-discovery.index', ['edit_source' => $source->id]) }}" title="Edit" aria-label="Edit {{ $source->name }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-indigo-600 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <x-phosphor-pencil-simple class="h-5 w-5" />
                            </a>
                            <form method="POST" action="{{ route('admin.product-discovery.sources.toggle', $source) }}">
                                @csrf @method('PATCH')
                                <button title="{{ $source->is_active ? 'Pause' : 'Activate' }}" aria-label="{{ $source->is_active ? 'Pause' : 'Activate' }} {{ $source->name }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    @if($source->is_active)<x-phosphor-pause class="h-5 w-5" />@else<x-phosphor-play class="h-5 w-5" />@endif
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.product-discovery.sources.destroy', $source) }}" onsubmit="return confirm('Delete this source and all its recommendations?')">
                                @csrf @method('DELETE')
                                <button title="Delete" aria-label="Delete {{ $source->name }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <x-phosphor-trash class="h-5 w-5" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No sources configured.</td></tr> @endforelse
            </tbody>
        </table></div>
    </section>

    <section>
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach(['new' => 'New', 'shortlisted' => 'Shortlisted', 'dismissed' => 'Dismissed'] as $key => $label)
                <a href="{{ route('admin.product-discovery.index', ['status' => $key]) }}" class="rounded px-4 py-2 text-sm {{ $status === $key ? 'bg-gray-900 text-white' : 'bg-white text-gray-700 shadow' }}">{{ $label }} ({{ $statusCounts[$key] ?? 0 }})</a>
            @endforeach
        </div>
        <div class="space-y-3">
        @forelse($recommendations as $recommendation)
            <article class="bg-white shadow rounded-lg p-5 flex flex-col md:flex-row md:items-start gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex gap-2 items-center"><span class="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">Score {{ $recommendation->score }}</span><span class="text-xs text-gray-500">{{ $recommendation->source->name }} · {{ $recommendation->discovered_at->diffForHumans() }}</span></div>
                    <h3 class="mt-2 font-semibold text-gray-900">{{ $recommendation->title }}</h3>
                    @if($recommendation->description)<p class="mt-1 text-sm text-gray-600">{{ $recommendation->description }}</p>@endif
                    <a href="{{ $recommendation->url }}" target="_blank" rel="noopener" class="mt-2 block text-sm text-blue-600 break-all">{{ $recommendation->url }}</a>
                </div>
                <div class="flex gap-3 whitespace-nowrap">
                    <a href="{{ route('products.create', ['name' => $recommendation->title, 'link' => $recommendation->url]) }}" class="text-indigo-700 hover:underline">Add product</a>
                    @if($recommendation->status !== 'shortlisted')
                    <form method="POST" action="{{ route('admin.product-discovery.recommendations.update', $recommendation) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="shortlisted"><button class="text-green-700 hover:underline">Shortlist</button></form>
                    @endif
                    @if($recommendation->status !== 'new')
                    <form method="POST" action="{{ route('admin.product-discovery.recommendations.update', $recommendation) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="new"><button class="text-blue-600 hover:underline">Restore</button></form>
                    @endif
                    @if($recommendation->status !== 'dismissed')
                    <form method="POST" action="{{ route('admin.product-discovery.recommendations.update', $recommendation) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="dismissed"><button class="text-red-600 hover:underline">Dismiss</button></form>
                    @endif
                </div>
            </article>
        @empty <div class="bg-white rounded-lg p-8 text-center text-gray-500">No {{ $status }} recommendations.</div> @endforelse
        </div>
        <div class="mt-6">{{ $recommendations->links() }}</div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('fetch-source-details');
    const urlInput = document.getElementById('source-url');
    const status = document.getElementById('fetch-source-status');
    const fields = {
        name: document.getElementById('source-name'),
        url: urlInput,
        type: document.getElementById('source-type'),
        item_selector: document.getElementById('item-selector'),
        link_selector: document.getElementById('link-selector'),
        title_selector: document.getElementById('title-selector'),
        description_selector: document.getElementById('description-selector'),
    };

    button.addEventListener('click', async () => {
        if (!urlInput.reportValidity()) return;

        button.disabled = true;
        button.textContent = 'Fetching…';
        status.className = 'mt-2 text-sm text-gray-600';
        status.textContent = 'Inspecting the source…';

        try {
            const response = await fetch(@json(route('admin.product-discovery.inspect')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify({url: urlInput.value}),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not fetch source details.');

            Object.entries(fields).forEach(([key, field]) => {
                field.value = data[key] ?? '';
            });
            status.className = 'mt-2 text-sm text-green-700';
            status.textContent = data.type === 'feed'
                ? 'Feed detected. Details filled automatically.'
                : 'Page inspected. Review the suggested extraction settings.';
        } catch (error) {
            status.className = 'mt-2 text-sm text-red-700';
            status.textContent = error.message;
        } finally {
            button.disabled = false;
            button.textContent = 'Fetch details';
        }
    });
});
</script>
@endpush

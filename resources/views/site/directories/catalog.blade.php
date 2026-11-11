@php
    $categories = \App\Models\StartupDirectory::CATEGORIES;
    $pricing = \App\Models\StartupDirectory::PRICING;
    $linkTypes = \App\Models\StartupDirectory::LINK_TYPES;
    $sorts = \App\Services\StartupDirectoryCatalog::SORTS;
    $directoryHeading = $page->directory_heading ?? 'Browse startup directories';
    $headingTag = ($useDirectoryBreadcrumb ?? false) ? 'h1' : 'h2';
    $directoryIntro = $page->directory_intro ?? 'Compare submission options. Pricing and link policies can change, so check the directory before submitting.';
    $catalogUrl = route('custom-pages.show', ['product_name' => $page->slug]);
    $activeSort = $directoryFilters['sort'] ?? 'recommended';
    $catalogLink = fn (array $filters) => $filters ? $catalogUrl.'?'.http_build_query($filters) : $catalogUrl;
    $sortUrl = fn ($sort) => $catalogLink(array_replace($directoryFilters, ['sort' => $sort]));
    $freeDofollow = ['pricing' => 'free', 'link_type' => 'dofollow'];
    $freeDofollowActive = array_intersect_assoc($freeDofollow, $directoryFilters) === $freeDofollow;
    $freeDofollowUrl = $catalogLink($freeDofollowActive
        ? \Illuminate\Support\Arr::except($directoryFilters, array_keys($freeDofollow))
        : array_replace($directoryFilters, $freeDofollow));
@endphp

@include('site.directories.styles')

<section @class(['space-y-4', 'mt-3' => $useDirectoryBreadcrumb ?? false, 'mt-8' => !($useDirectoryBreadcrumb ?? false)]) aria-labelledby="directory-catalog-title" id="directory-catalog">
    <div>
        <{{ $headingTag }} id="directory-catalog-title" class="text-2xl font-semibold">{{ \App\Services\CustomPageContent::expandShortcodes($directoryHeading) }}</{{ $headingTag }}>
        @if($editable)
            @include('site.directories.content-editor', ['field' => 'directory_heading', 'label' => 'table heading', 'currentValue' => $directoryHeading])
        @endif
        <p class="mt-1 text-sm text-gray-600">{{ \App\Services\CustomPageContent::expandShortcodes($directoryIntro) }}</p>
        @if($editable)
            @include('site.directories.content-editor', ['field' => 'directory_intro', 'label' => 'table introduction', 'currentValue' => $directoryIntro])
        @endif
    </div>

    @if($editable)
        <details class="directory-add">
            <summary class="directory-control directory-primary">+ Add directory</summary>
            <form method="POST" action="{{ route('admin.startup-directories.store') }}" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                <label class="text-sm">Name <input name="name" class="block rounded border" maxlength="255" required></label>
                <label class="text-sm">Website URL <input type="url" name="website_url" class="block rounded border" maxlength="2048" required></label>
                <button type="submit" class="rounded border px-3 py-2 text-sm">Add directory</button>
            </form>
        </details>
    @endif

    <form method="GET" action="{{ $catalogUrl }}#directory-catalog" class="directory-toolbar" id="directory-filters">
        <label class="directory-search">
            Search
            <input type="search" name="q" value="{{ $directoryFilters['q'] ?? '' }}" maxlength="100"
                placeholder="Name, domain, or description" class="directory-control">
        </label>
        @foreach([
            'category' => ['Category', $categories],
            'pricing' => ['Pricing', $pricing + ['unknown' => 'Unknown']],
            'link_type' => ['Link type', $linkTypes + ['unknown' => 'Unknown']],
            'sort' => ['Sort by', $sorts],
        ] as $name => [$label, $options])
            <label class="directory-filter">
                {{ $label }}
                <select name="{{ $name }}" class="directory-control">
                    @if($name !== 'sort') <option value="">All</option> @endif
                    @foreach($options as $value => $text)
                        <option value="{{ $value }}" @selected(($directoryFilters[$name] ?? '') === $value)>{{ $text }}</option>
                    @endforeach
                </select>
            </label>
        @endforeach
        <noscript>
            <button type="submit" class="directory-control directory-primary">Filter results</button>
        </noscript>
    </form>

    <div class="directory-result-bar">
        <span class="text-gray-600">
            @if($directories->isNotEmpty())
                {{ number_format($directories->firstItem()) }}–{{ number_format($directories->lastItem()) }} of
            @endif
            {{ number_format($directories->total()) }} {{ $directories->total() === 1 ? 'directory' : 'directories' }}
        </span>
        <div class="directory-filter-chips">
            <span>Filters:</span>
            <a href="{{ $freeDofollowUrl }}" @class(['directory-control', 'directory-chip', 'directory-chip-active' => $freeDofollowActive])
                aria-label="{{ $freeDofollowActive ? 'Remove Free + Dofollow filter' : 'Apply Free + Dofollow filter' }}">
                Free + Dofollow
                @if($freeDofollowActive)
                    <span class="directory-chip-remove" aria-hidden="true">×</span>
                @endif
            </a>
        </div>
        @if($directoryFilters)
            <a href="{{ $catalogUrl }}" class="underline">Clear filters</a>
        @endif
        @if($editable)
            <span id="directory-save-status" role="status"></span>
            <span class="text-gray-500">Click a value to edit it. Open Row details for more fields.</span>
        @endif
    </div>

    @if($directories->isEmpty())
        <p class="rounded-lg border p-4 text-sm">No directories match these filters.</p>
    @else
        <div class="directory-grid">
            <table class="directory-table">
                <colgroup>
                    <col class="directory-col-name">
                    <col class="directory-col-dr">
                    <col class="directory-col-category">
                    <col class="directory-col-pricing">
                    <col class="directory-col-link">
                    <col class="directory-col-action">
                </colgroup>
                <thead><tr>
                    <th scope="col" @if($activeSort === 'name') aria-sort="ascending" @endif>
                        <a href="{{ $sortUrl('name') }}">Directory <span aria-hidden="true">{{ $activeSort === 'name' ? '↑' : '↕' }}</span></a>
                    </th>
                    <th scope="col" class="directory-dr" @if($activeSort === 'dr') aria-sort="descending" @endif>
                        <a href="{{ $sortUrl('dr') }}"><abbr title="Ahrefs Domain Rating">DR</abbr> <span aria-hidden="true">{{ $activeSort === 'dr' ? '↓' : '↕' }}</span></a>
                    </th>
                    <th scope="col">Category</th>
                    <th scope="col">Pricing</th>
                    <th scope="col">Link type</th>
                    <th scope="col">Visit</th>
                </tr></thead>
                <tbody>
                    @foreach($directories as $directory)
                        @include('site.directories.row', ['directory' => $directory, 'editable' => $editable])
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $directories->onEachSide(1)->links('site.directories.pagination') }}
    @endif

    @if($editable)
        @include('site.directories.inline-editor')
    @endif
</section>

@push('scripts')
<script>
    document.getElementById('directory-filters')?.addEventListener('change', event => {
        event.currentTarget.requestSubmit();
    });
</script>
@endpush

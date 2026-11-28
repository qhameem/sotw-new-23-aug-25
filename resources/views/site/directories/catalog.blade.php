@php
    $categories = \App\Models\StartupDirectory::CATEGORIES;
    $pricing = \App\Models\StartupDirectory::PRICING;
    $linkTypes = \App\Models\StartupDirectory::LINK_TYPES;
    $sorts = \App\Services\StartupDirectoryCatalog::SORTS;
    $pageSizes = \App\Services\StartupDirectoryCatalog::PAGE_SIZES;
    $directoryHeading = $page->directory_heading ?? 'Browse startup directories';
    $headingTag = ($useDirectoryBreadcrumb ?? false) ? 'h1' : 'h2';
    $directoryIntro = $page->directory_intro ?? 'Compare submission options. Pricing and link policies can change, so check the directory before submitting.';
    $catalogUrl = route('custom-pages.show', ['product_name' => $page->slug]);
    $activeSort = $directoryFilters['sort'] ?? 'dr';
    $catalogLink = fn (array $filters) => $filters ? $catalogUrl.'?'.http_build_query($filters) : $catalogUrl;
    $sortUrl = fn ($sort) => $catalogLink(array_replace($directoryFilters, ['sort' => $sort]));
    $pageSize = $directoryFilters['per_page'] ?? '50';
    $sizeOnly = \Illuminate\Support\Arr::only($directoryFilters, ['per_page']);
    $pageSizeUrl = fn (string $size) => $catalogLink($size === '50'
        ? \Illuminate\Support\Arr::except($directoryFilters, ['per_page'])
        : array_replace($directoryFilters, ['per_page' => $size]));
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

    <span class="sr-only" data-directory-announcement role="status"></span>
    <div class="directory-filter-panel" data-directory-fragment>
        <form method="GET" action="{{ $catalogUrl }}#directory-catalog" class="directory-toolbar" id="directory-filters">
            <input type="hidden" name="per_page" value="{{ $sizeOnly['per_page'] ?? '' }}">
            <div class="directory-toolbar-row">
                <div class="directory-search">
                    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="7" cy="7" r="4.5"/><path d="m10.5 10.5 3 3"/>
                    </svg>
                    <input type="search" name="q" aria-label="Search directories by name or domain"
                        value="{{ $directoryFilters['q'] ?? '' }}" maxlength="100"
                        placeholder="Search {{ number_format($directoryCount) }} directories by name or domain." class="directory-control">
                    <kbd aria-hidden="true">/</kbd>
                    <button type="button" data-directory-clear-search aria-label="Clear search">×</button>
                </div>
            </div>
            <div class="directory-toolbar-row">
                @foreach(['pricing' => ['Pricing', ['' => 'All'] + $pricing + ['unknown' => 'Unknown']], 'link_type' => ['Link type', ['' => 'All'] + $linkTypes + ['unknown' => 'Unknown']]] as $name => [$label, $options])
                    <fieldset class="directory-filter">
                        <legend>{{ $label }}</legend>
                        <div class="directory-segments">
                            @foreach($options as $value => $text)
                                <label>
                                    <input class="sr-only" type="radio" name="{{ $name }}" value="{{ $value }}" @checked(($directoryFilters[$name] ?? '') === $value)>
                                    <span>{{ $text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
                <div class="directory-pickers">
                    <div class="directory-picker">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                            <path d="M2 2h4v4H2zM10 2h4v4h-4zM2 10h4v4H2zM10 10h4v4h-4z"/>
                        </svg>
                        <select name="category" aria-label="Category">
                            <option value="">All categories</option>
                            @foreach($categories as $value => $text)
                                <option value="{{ $value }}" @selected(($directoryFilters['category'] ?? '') === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                        <svg aria-hidden="true" width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <path d="m2 5 4-4 4 4zm0 2 4 4 4-4z"/>
                        </svg>
                    </div>
                    <div class="directory-picker">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                            <path d="M2 4h12M4 8h8M6 12h4"/>
                        </svg>
                        <select name="sort" aria-label="Sort by">
                            @foreach($sorts as $value => $text)
                                <option value="{{ $value }}" @selected($activeSort === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                        <svg aria-hidden="true" width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <path d="m2 5 4-4 4 4zm0 2 4 4 4-4z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <noscript>
                <button type="submit" class="directory-control directory-primary">Filter results</button>
            </noscript>
        </form>

        <div class="directory-result-bar">
            <details class="directory-page-size">
                <summary class="directory-control">
                    <span data-directory-count>
                        @if($directories->isNotEmpty())
                            {{ number_format($directories->firstItem()) }}–{{ number_format($directories->lastItem()) }} of
                        @endif
                        {{ number_format($directories->total()) }} {{ isset($directoryFilters['q']) ? ($directories->total() === 1 ? 'match' : 'matches') : ($directories->total() === 1 ? 'directory' : 'directories') }}
                    </span>
                    <span aria-hidden="true">▾</span>
                </summary>
                <nav class="directory-page-size-options" aria-label="Directories per page">
                    @foreach($pageSizes as $value => $label)
                        @php($size = (string) $value)
                        <a href="{{ $pageSizeUrl($size) }}#directory-catalog"
                            data-directory-navigation data-directory-page-size="{{ $size }}" class="directory-control"
                            @if($size === $pageSize) aria-current="true" @endif>{{ $label }}{{ $size === 'all' ? ' directories' : ' per page' }}</a>
                    @endforeach
                </nav>
            </details>
            <div class="directory-filter-chips">
                @foreach(['category' => $categories, 'pricing' => $pricing, 'link_type' => $linkTypes] as $name => $options)
                    @if(isset($directoryFilters[$name]))
                        @php($filterLabel = $options[$directoryFilters[$name]] ?? 'Unknown')
                        <a href="{{ $catalogLink(\Illuminate\Support\Arr::except($directoryFilters, [$name])) }}#directory-catalog"
                            data-directory-navigation class="directory-control directory-chip directory-chip-active" aria-label="Remove {{ str_replace('_', ' ', $name) }} filter: {{ $filterLabel }}">
                            {{ $filterLabel }} <span class="directory-chip-remove" aria-hidden="true">×</span>
                        </a>
                    @endif
                @endforeach
            </div>
            <a href="{{ $catalogLink($sizeOnly) }}#directory-catalog" data-directory-navigation data-directory-clear-all class="underline" @if($directoryFilters === $sizeOnly) hidden @endif>Clear all</a>
            @if($editable)
                <span id="directory-save-status" role="status"></span>
                <span class="text-gray-500">Click a value to edit it. Open Row details for more fields.</span>
            @endif
        </div>

    </div>

    <div data-directory-fragment class="space-y-4">
        <p class="rounded-lg border p-4 text-sm" data-directory-empty @if($directories->isNotEmpty()) hidden @endif>No directories match these filters.</p>
        <div data-directory-table @if($directories->isEmpty()) hidden @endif>
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
                        <th scope="col" @if(in_array($activeSort, ['name', 'name_desc'], true)) aria-sort="{{ $activeSort === 'name' ? 'ascending' : 'descending' }}" @endif>
                            <a href="{{ $sortUrl($activeSort === 'name' ? 'name_desc' : 'name') }}">Directory <span aria-hidden="true">{{ $activeSort === 'name' ? '↑' : ($activeSort === 'name_desc' ? '↓' : '↕') }}</span></a>
                        </th>
                        <th scope="col" class="directory-dr" @if(in_array($activeSort, ['dr', 'dr_asc'], true)) aria-sort="{{ $activeSort === 'dr' ? 'descending' : 'ascending' }}" @endif>
                            <a href="{{ $sortUrl($activeSort === 'dr' ? 'dr_asc' : 'dr') }}"><abbr title="Ahrefs Domain Rating">DR</abbr> <span aria-hidden="true">{{ $activeSort === 'dr' ? '↓' : ($activeSort === 'dr_asc' ? '↑' : '↕') }}</span></a>
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
            <p class="mt-2 flex flex-wrap gap-x-3 text-xs">
                <a class="underline" href="https://ahrefs.com/" target="_blank" rel="nofollow noopener noreferrer">Domain Rating by Ahrefs</a>
                @if($directoryDrUpdatedAt)
                    <time datetime="{{ $directoryDrUpdatedAt->toIso8601String() }}">Updated {{ $directoryDrUpdatedAt->diffForHumans() }}</time>
                @endif
            </p>
        </div>

            <div data-directory-pagination>
                {{ $directories->onEachSide(1)->links('site.directories.pagination') }}
            </div>
    </div>

    @if($editable)
        @include('site.directories.inline-editor')
    @endif
</section>

@push('scripts')
<script>
(() => {
    const catalog = document.getElementById('directory-catalog');
    if (!catalog) return;

    let snapshot;
    @unless($editable)
        const snapshotUrl = new URL(catalog.querySelector('#directory-filters').action);
        snapshotUrl.searchParams.set('_directory_snapshot', '1');
        fetch(snapshotUrl)
            .then(response => response.ok ? response.json() : null)
            .then(rows => { snapshot = rows; })
            .catch(() => {});
    @endunless

    @include('site.directories.local-results')

    let pending;
    let searchTimer;
    const searchField = () => catalog.querySelector('#directory-filters [name="q"]');
    async function navigate(url, push = true, badge = null) {
        clearTimeout(searchTimer);
        pending?.abort();
        if (snapshot && renderLocalResults(url)) {
            catalog.removeAttribute('aria-busy');
            if (push && url.href !== location.href) history.pushState(null, '', url);
            return;
        }
        const request = new AbortController();
        pending = request;
        catalog.setAttribute('aria-busy', 'true');

        const showSpinner = badge && setTimeout(() => badge.classList.add('directory-chip-loading'), 150);
        const clearSpinner = () => {
            clearTimeout(showSpinner);
            badge?.classList.remove('directory-chip-loading');
        };
        request.signal.addEventListener('abort', clearSpinner, { once: true });

        try {
            const response = await fetch(url, { signal: request.signal });
            if (!response.ok) throw new Error('Could not load directories');

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (request.signal.aborted) return;
            const fragments = catalog.querySelectorAll('[data-directory-fragment]');
            const replacements = page.querySelectorAll('#directory-catalog [data-directory-fragment]');
            if (fragments.length !== replacements.length) throw new Error('Directory results missing');

            const focused = document.activeElement;
            const sizeFocused = focused?.closest('.directory-page-size');
            const field = focused?.form?.id === 'directory-filters' ? focused.name : null;
            const search = searchField();
            const nextSearch = replacements[0].querySelector('.directory-search');
            if (search.value.trim() !== (url.searchParams.get('q') ?? '')) {
                search.value = nextSearch.querySelector('input').value;
            }
            const selection = [search.selectionStart, search.selectionEnd];
            const searchWrapper = search.closest('.directory-search');
            fragments.forEach((fragment, index) => fragment.replaceWith(replacements[index]));
            nextSearch.replaceWith(searchWrapper);
            if (sizeFocused) catalog.querySelector('.directory-page-size summary').focus({ preventScroll: true });
            if (focused === search) {
                search.focus({ preventScroll: true });
                search.setSelectionRange(...selection);
            }
            if (field && field !== 'q') {
                const controls = catalog.querySelectorAll('#directory-filters [name]');
                Array.from(controls).find(control =>
                    control.name === field && (control.type !== 'radio' || control.checked)
                )?.focus({ preventScroll: true });
            }
            catalog.removeAttribute('aria-busy');
            catalog.querySelector('[data-directory-announcement]').textContent =
                catalog.querySelector('[data-directory-count]').textContent.trim().replace(/\s+/g, ' ');
            if (push && url.href !== location.href) history.pushState(null, '', url);
        } catch (error) {
            if (!request.signal.aborted) location.assign(url);
        } finally {
            clearSpinner();
            if (pending === request) catalog.removeAttribute('aria-busy');
        }
    }

    catalog.addEventListener('change', event => {
        if (event.target !== searchField() && event.target.form?.id === 'directory-filters') event.target.form.requestSubmit();
    });

    function queueSearch(search) {
        clearTimeout(searchTimer);
        pending?.abort();
        searchTimer = setTimeout(() => search.form.requestSubmit(), 250);
    }

    catalog.addEventListener('input', event => {
        if (event.target === searchField() && !event.isComposing) queueSearch(event.target);
    });
    catalog.addEventListener('compositionend', event => {
        if (event.target === searchField()) queueSearch(event.target);
    });

    function clearSearch(search) {
        search.value = '';
        search.focus({ preventScroll: true });
        search.form.requestSubmit();
    }

    document.addEventListener('keydown', event => {
        if (event.defaultPrevented || event.isComposing || event.ctrlKey || event.metaKey || event.altKey) return;
        const search = searchField();
        if (event.key === 'Escape' && event.target === search && search.value) {
            event.preventDefault();
            clearSearch(search);
        } else if (event.key === '/' && !event.target.closest?.('input, textarea, select, [contenteditable]')) {
            event.preventDefault();
            search.focus();
        }
    });

    catalog.addEventListener('submit', event => {
        const form = event.target;
        if (form.id !== 'directory-filters') return;
        event.preventDefault();
        const url = new URL(form.action);
        for (const [name, value] of new FormData(form)) {
            if (value !== '' && !(name === 'sort' && value === 'dr')) url.searchParams.set(name, value);
        }
        navigate(url);
    });

    catalog.addEventListener('click', event => {
        if (event.target.closest('[data-directory-clear-search]')) {
            clearSearch(searchField());
            return;
        }
        const link = event.target.closest('a[data-directory-navigation], .directory-table th a, [data-directory-pagination] a');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey ||
            event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        navigate(new URL(link.href), true, link.matches('.directory-chip-active') ? link : null);
    });

    window.addEventListener('popstate', () => navigate(new URL(location.href), false));
})();
</script>
@endpush

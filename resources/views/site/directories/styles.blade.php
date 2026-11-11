@push('styles')
<style>
    #directory-catalog {
        --grid-border: color-mix(in srgb, currentColor 17%, transparent);
        --grid-header: color-mix(in srgb, currentColor 5%, var(--color-body-bg, white));
        --grid-hover: color-mix(in srgb, currentColor 6%, transparent);
        scroll-margin-top: 4.25rem;
        --grid-accent: var(--color-primary-700, #215a42);
    }
    .directory-breadcrumb { display: flex; align-items: center; gap: .4rem; font-size: .8125rem; }
    .directory-breadcrumb a { text-decoration: underline; text-underline-offset: .15em; }
    .directory-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: .5rem;
        font-size: .8125rem;
    }
    .directory-search { flex: 1 1 14rem; min-width: 0; }
    .directory-filter { flex: 0 1 auto; min-width: 0; }
    .directory-toolbar label { font-weight: 600; }
    .directory-toolbar label > :is(input, select) { display: block; margin-top: .25rem; }
    .directory-search input { width: 100%; }
    .directory-toolbar select { padding-right: 2rem; max-width: 100%; }
    .directory-control {
        display: inline-flex;
        align-items: center;
        min-height: 2rem;
        padding: .3rem .65rem;
        border: 1px solid var(--grid-border);
        border-radius: .3rem;
        background-color: var(--color-body-bg, white);
        color: inherit;
        font-size: .8125rem;
        line-height: 1.25rem;
        text-decoration: none;
    }
    .directory-control:hover { border-color: currentColor; }
    .directory-primary { background-color: #216449; color: white; font-weight: 600; }
    .directory-primary:hover { background-color: #194e39; }
    .directory-add summary { cursor: pointer; list-style: none; }
    .directory-add summary::-webkit-details-marker { display: none; }
    .directory-result-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; font-size: .8125rem; }
    .directory-filter-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
    .directory-chip { gap: .45rem; background: var(--grid-header); }
    .directory-chip-active { background: light-dark(#e6e8eb, #363b44); font-weight: 650; }
    .directory-chip-remove { font-size: 1.1rem; line-height: 1; }
    .directory-current-page { background: var(--grid-header); font-weight: 700; }
    .directory-grid { border: 1px solid var(--grid-border); border-radius: .4rem; }
    .directory-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; text-align: left; font-size: .8125rem; }
    .directory-col-name { width: 20%; }
    .directory-col-category { width: 20%; }
    .directory-col-pricing { width: 16%; }
    .directory-col-link { width: 20%; }
    .directory-col-dr { width: 10%; }
    .directory-col-action { width: 14%; }
    .directory-table :is(th, td) { padding: .375rem .65rem; vertical-align: middle; }
    .directory-table th { position: sticky; top: var(--site-header-offset, 3.5rem); z-index: 1; background: var(--grid-header); font-weight: 650; }
    .directory-table th:first-child { border-top-left-radius: .4rem; }
    .directory-table th:last-child { border-top-right-radius: .4rem; }
    .directory-table th a { display: flex; align-items: center; gap: .3rem; justify-content: flex-start; }
    .directory-table th a:hover { text-decoration: underline; }
    .directory-table th a span { opacity: .65; }
    .directory-table td { border-top: 1px solid var(--grid-border); overflow-wrap: anywhere; }
    .directory-table tbody tr:hover { background: var(--grid-hover); }
    .directory-name { display: flex; align-items: center; gap: .45rem; font-weight: 650; }
    .directory-favicon { width: 1rem; height: 1rem; flex: 0 0 1rem; border-radius: .2rem; background: var(--grid-header); }
    .directory-dr { text-align: center; font-variant-numeric: tabular-nums; }
    .directory-table th.directory-dr a, .directory-dr .directory-edit-cell { justify-content: center; }
    .directory-rating { display: inline-flex; flex-direction: column; align-items: center; gap: .2rem; }
    .directory-rating-track {
        display: block;
        width: 40px;
        max-width: 100%;
        height: 3px;
        border-radius: 999px;
        background: var(--grid-border);
    }
    .directory-rating-fill { display: block; height: 100%; border-radius: inherit; background: light-dark(#286549, #8ed8ae); }
    .directory-badge {
        display: inline-block;
        padding: .1rem .45rem;
        border: 1px solid transparent;
        border-radius: 999px;
        background: light-dark(#eef0f2, #343940);
        color: light-dark(#424850, #e0e4e9);
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .directory-badge-free { background: light-dark(#e0f2e6, #193a2b); color: light-dark(#165135, #b7ebcd); }
    .directory-badge-freemium { background: light-dark(#e8ebef, #303946); color: light-dark(#465260, #cbd5e1); }
    .directory-badge-paid { background: light-dark(#e7effc, #203551); color: light-dark(#244f84, #c5dcff); }
    .directory-badge-dofollow { background: transparent; border-color: light-dark(#4e9069, #75c59a); color: light-dark(#216449, #a2e1bb); }
    .directory-table .directory-visit {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        min-height: 32px;
        color: light-dark(#215a42, #a2e1bb);
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: .15em;
        white-space: nowrap;
    }
    .directory-visit:focus-visible { outline: 2px solid var(--grid-accent); outline-offset: 2px; }
    .directory-row-details { cursor: pointer; opacity: .8; }
    .directory-edit-cell {
        display: flex;
        width: 100%;
        min-width: 0;
        align-items: center;
        justify-content: space-between;
        gap: .3rem;
        padding: 0 .2rem;
        border-radius: .2rem;
        text-align: left;
        cursor: pointer;
    }
    .directory-edit-cell[hidden] { display: none; }
    .directory-cell-value { min-width: 0; overflow-wrap: anywhere; }
    .directory-pencil { flex: 0 0 .875rem; opacity: 0; }
    .directory-edit-cell:is(:hover, :focus-visible) { background: var(--grid-hover); outline: 1px solid var(--grid-accent); }
    .directory-edit-cell:is(:hover, :focus-visible) .directory-pencil { opacity: 1; }
    .directory-edit-form { display: grid; gap: .3rem; margin: .2rem 0; }
    .directory-edit-input { width: 100%; min-width: 0; outline: 1px solid var(--grid-accent); }
    @media (hover: none) {
        .directory-pencil { opacity: .65; }
    }
    @media (max-width: 767px) {
        .directory-toolbar .directory-filter { flex: 1 1 8rem; }
        .directory-toolbar select { width: 100%; }
        .directory-grid { border: 0; }
        .directory-table, .directory-table tbody { display: block; }
        .directory-table colgroup { display: none; }
        .directory-table thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
        .directory-table thead:focus-within { position: static; width: auto; height: auto; clip-path: none; display: block; }
        .directory-table tr { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; padding: .8rem 0; border-bottom: 1px solid var(--grid-border); }
        .directory-table :is(th, td) { display: block; border: 0; padding: 0; }
        .directory-dr { text-align: left; }
        .directory-dr .directory-edit-cell { justify-content: space-between; }
        .directory-table td:first-child { grid-column: 1 / -1; }
        .directory-table td:nth-child(n+2)::before { content: attr(data-label); display: block; font-size: .75rem; opacity: .75; }
    }
</style>
@endpush

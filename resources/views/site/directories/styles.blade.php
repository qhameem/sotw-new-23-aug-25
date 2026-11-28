@push('styles')
<style>
    html { scrollbar-gutter: stable; }
    #directory-catalog {
        --grid-border: color-mix(in srgb, currentColor 17%, transparent);
        --grid-header: color-mix(in srgb, currentColor 5%, var(--color-body-bg, white));
        --grid-hover: color-mix(in srgb, currentColor 6%, transparent);
        scroll-margin-top: 4.25rem;
        --grid-accent: var(--color-primary-700, #215a42);
    }
    .directory-breadcrumb { display: flex; align-items: center; gap: .4rem; font-size: .8125rem; }
    .directory-breadcrumb a { text-decoration: underline; text-underline-offset: .15em; }
    .directory-filter-panel { border: 1px solid var(--grid-border); border-radius: .65rem; padding: .875rem; }
    .directory-toolbar { display: grid; gap: .75rem; font-size: .8125rem; }
    .directory-toolbar-row {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: .5rem;
        font-size: .8125rem;
    }
    .directory-search { flex: 1 1 14rem; min-width: 0; }
    .directory-filter { flex: 0 1 auto; min-width: 0; }
    .directory-toolbar :is(label, legend) { font-weight: 600; }
    .directory-filter legend { padding: 0; }
    .directory-pickers { display: flex; flex-wrap: wrap; gap: .5rem; margin-inline-start: auto; }
    .directory-picker { position: relative; min-width: 0; max-width: 100%; }
    .directory-picker select { appearance: none; min-height: 2rem; width: 100%; padding: .25rem 1.75rem; border: 1px solid var(--grid-border); border-radius: .35rem; background: var(--color-body-bg, white); color: inherit; font: inherit; cursor: pointer; }
    .directory-picker select[name="category"] { width: 10rem; max-width: 100%; text-overflow: ellipsis; }
    .directory-picker svg { position: absolute; inset-block-start: 50%; transform: translateY(-50%); pointer-events: none; }
    .directory-picker svg:first-child { inset-inline-start: .45rem; }
    .directory-picker svg:last-child { inset-inline-end: .45rem; opacity: .6; }
    .directory-segments { display: flex; flex-wrap: wrap; gap: 2px; margin-top: .25rem; padding: 2px; border: 1px solid var(--grid-border); border-radius: .45rem; }
    .directory-segments label { position: relative; cursor: pointer; font-weight: 400; }
    .directory-segments span { display: block; padding: .15rem .55rem; border: 1px solid transparent; border-radius: .3rem; }
    .directory-segments input:checked + span, .directory-chip.directory-chip-active { background: light-dark(#e2e2e2, #3b3b3b); border-color: transparent; }
    .directory-segments input:focus-visible + span { outline: 2px solid currentColor; outline-offset: -2px; }
    .directory-filter-panel .directory-result-bar { min-height: calc(2.5rem + 1px); margin-top: .75rem; padding-top: .75rem; border-top: 1px solid var(--grid-border); }
    .directory-toolbar label > :is(input, select) { display: block; margin-top: .25rem; }
    .directory-filter-panel { background: var(--grid-header); }
    .directory-search { position: relative; }
    .directory-search input { width: 100%; padding-inline: 2rem; background: light-dark(#fff, #292929); }
    .directory-search input:focus { outline: 2px solid light-dark(#327322, #8bc76e); outline-offset: 1px; }
    .directory-search input::-webkit-search-cancel-button { display: none; }
    .directory-search svg, .directory-search kbd, .directory-search button { position: absolute; top: 50%; transform: translateY(-50%); }
    .directory-search svg { inset-inline-start: .6rem; pointer-events: none; }
    .directory-search kbd, .directory-search button { inset-inline-end: .5rem; }
    .directory-search kbd { padding-inline: .25rem; border: 1px solid var(--grid-border); border-radius: .2rem; pointer-events: none; }
    .directory-search button { padding-inline: .3rem; font-size: 1rem; cursor: pointer; }
    .directory-search input:placeholder-shown ~ button, .directory-search input:not(:placeholder-shown) ~ kbd { display: none; }
    .directory-match-domain { padding-inline-start: 1.45rem; }
    .directory-name mark, .directory-match-domain mark { background: light-dark(#fce9a3, #654e16); color: inherit; border-radius: .13rem; }
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
    .directory-page-size { position: relative; }
    .directory-page-size summary { gap: .45rem; cursor: pointer; list-style: none; }
    .directory-page-size summary::-webkit-details-marker { display: none; }
    .directory-page-size-options { position: absolute; z-index: 2; inset-inline-start: 0; top: calc(100% + .25rem); display: grid; gap: .2rem; min-width: max-content; padding: .3rem; border: 1px solid var(--grid-border); border-radius: .4rem; background: var(--color-body-bg, white); }
    .directory-page-size-options a[aria-current] { font-weight: 700; background: var(--grid-header); }
    .directory-filter-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
    .directory-chip { min-height: 1.75rem; padding-block: .15rem; gap: .45rem; background: var(--grid-header); }
    .directory-chip-remove { position: relative; display: inline-grid; place-items: center; width: 1em; font-size: 1.1rem; line-height: 1; }
    .directory-chip-loading .directory-chip-remove { color: transparent; }
    .directory-chip-loading .directory-chip-remove::after {
        content: "";
        position: absolute;
        width: .65em;
        height: .65em;
        border: 1px solid var(--grid-accent);
        border-inline-end-color: transparent;
        border-radius: 50%;
        animation: directory-chip-spin .65s linear infinite;
    }
    @keyframes directory-chip-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .directory-chip-loading .directory-chip-remove::after { animation: none; }
    }
    .directory-current-page { background: var(--grid-header); font-weight: 700; }
    .directory-grid { border: 1px solid var(--grid-border); border-radius: .4rem; }
    .directory-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; text-align: left; font-size: .8125rem; }
    .directory-col-name { width: 20%; }
    .directory-col-category { width: 18%; }
    .directory-col-pricing { width: 16%; }
    .directory-col-link { width: 18%; }
    .directory-col-dr { width: 14%; }
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
    .directory-rating {
        display: inline-grid;
        place-items: center;
        width: 1.875rem;
        height: 1.875rem;
        border-radius: 50%;
        --rating-color: light-dark(#687078, #a5abb0);
        background: conic-gradient(var(--rating-color) var(--rating-fill), var(--grid-border) 0);
    }
    .directory-rating-high { --rating-color: light-dark(#327322, #8bc76e); }
    .directory-rating-mid { --rating-color: light-dark(#946000, #e5b956); }
    .directory-rating-value {
        font-family: ui-monospace, monospace;
        font-size: .6875rem;
        display: grid;
        place-items: center;
        width: 1.625rem;
        height: 1.625rem;
        border-radius: 50%;
        background: var(--color-body-bg, white);
    }
    .directory-rating-unknown { color: light-dark(#737373, #92928d); font-size: 1rem; }
    .directory-pricing, .directory-link-type {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        font-weight: 400;
        white-space: nowrap;
    }
    .directory-pricing {
        min-height: 1.5rem;
        padding: .08rem .5rem;
        border-radius: .375rem;
        background: var(--pricing-fill);
        color: var(--pricing-text);
        font-size: .75rem;
    }
    .directory-pricing-dot {
        width: .4375rem;
        height: .4375rem;
        border-radius: 50%;
        background: var(--pricing-dot);
    }
    .directory-pricing-free {
        --pricing-dot: light-dark(#4f8a70, #86ad98);
        --pricing-fill: light-dark(#e5f1e9, #26382e);
        --pricing-text: light-dark(#365f4b, #b9d2c2);
    }
    .directory-pricing-freemium {
        --pricing-dot: light-dark(#557fab, #8fa9c8);
        --pricing-fill: light-dark(#e6eef8, #283649);
        --pricing-text: light-dark(#3b5b80, #bdcfe5);
    }
    .directory-pricing-paid {
        --pricing-dot: light-dark(#9171a5, #b39ac1);
        --pricing-fill: light-dark(#f2eaf6, #3b3045);
        --pricing-text: light-dark(#6b4e7b, #d8c4e2);
    }
    .directory-link-type-check { color: #00bc7d; }
    .directory-link-type-dot {
        width: .5rem;
        height: .5rem;
        margin-inline: .125rem;
        border-radius: 50%;
        background: #ff6467;
    }
    .directory-link-type-unknown { color: light-dark(#616970, #a5abb0); }
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
    .directory-row-details {
        display: flex;
        align-items: center;
        gap: .3rem;
        min-height: 28px;
        cursor: pointer;
        color: light-dark(#515a61, #b5bdc5);
    }
    .directory-row-details:hover { text-decoration: underline; text-underline-offset: .15em; }
    .directory-row-details:focus-visible { outline: 2px solid var(--grid-accent); outline-offset: 2px; }
    .directory-row-details[aria-expanded="true"] svg { transform: rotate(180deg); }
    .directory-table .directory-details-row[hidden] { display: none; }
    .directory-table .directory-details-row > td { padding: .75rem; background: var(--grid-header); }
    .directory-details-panel { border: 1px solid var(--grid-border); border-radius: .5rem; padding: .875rem; background: var(--color-body-bg, white); }
    .directory-details-panel .directory-details-title { margin: 0 0 .75rem; font-size: .95rem; font-weight: 650; line-height: 1.3; }
    .directory-details-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem 1.25rem; margin: 0; }
    .directory-details-wide { grid-column: 1 / -1; }
    .directory-details-field dt { margin: 0 0 .25rem; font-size: .75rem; font-weight: 650; }
    .directory-details-field dd { margin: 0; }
    .directory-details-field .directory-edit-cell { min-height: 34px; padding: .35rem .45rem; }
    .directory-details-field :is(.directory-edit-cell, .directory-edit-input) { background: #f6f6f6; border: 1px solid #909090; color: #242424; }
    .directory-details-field .directory-pencil { opacity: .65; }
    .directory-details-empty { color: light-dark(#515a61, #b5bdc5); }
    .directory-details-actions { display: flex; justify-content: flex-end; margin-top: .75rem; padding-top: .75rem; border-top: 1px solid var(--grid-border); }
    .directory-details-delete { color: light-dark(#a2262c, #ffadb0); }
    .directory-details-delete:hover { background: light-dark(#fcebec, #4e282b); }
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
        .directory-toolbar-row > :is(.directory-search, .directory-filter) { flex: 1 1 100%; }
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
        .directory-table .directory-details-row { display: block; padding: 0; }
        .directory-table .directory-details-row > td { padding: .5rem; }
        .directory-details-grid { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endpush

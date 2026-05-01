@props(['week', 'year', 'startDate', 'endDate'])

<div class="px-4 py-3 flex items-center justify-between" style="background-color: var(--color-body-bg, #ffffff);">
    <div>
        <h2 class="site-heading-text text-base font-medium text-gray-600">Week {{ $week }}</h2>
        <p class="site-body-text text-xs text-gray-500">{{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d') }}</p>
    </div>
</div>

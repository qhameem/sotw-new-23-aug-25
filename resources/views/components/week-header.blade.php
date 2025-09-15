@props(['week', 'year', 'startDate', 'endDate'])

<div class="px-4 py-3 bg-white flex items-center justify-between">
    <div>
        <h2 class=" text-base font-medium text-gray-600">Week {{ $week }}</h2>
        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d') }}</p>
    </div>
</div>
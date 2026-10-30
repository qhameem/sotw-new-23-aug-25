@props(['name' => 'published_at', 'value' => '', 'size' => 'default'])
@php
    $today = \Carbon\Carbon::today('UTC')->toDateString(); // default to today
    $initialValue = old($name, $value) ?: $today;
    $uniqueId = uniqid('datepicker_');
    $isSmall = $size === 'sm';
@endphp
<div class="flex items-center {{ $isSmall ? 'gap-1' : '' }}">
    <input type="date"
           class="scheduled-datepicker {{ $isSmall ? 'h-8 py-0 px-2 text-xs border border-slate-300 rounded-lg shadow-2xs focus:border-sky-500 focus:ring-1 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200' : 'mt-1 py-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-sky-200 focus:ring-opacity-50' }}"
           name="{{ $name }}"
           value="{{ $initialValue }}"
           min="{{ $today }}"
           id="{{ $uniqueId }}"
           {{ $attributes }}>
    <div class="{{ $isSmall ? 'flex items-center gap-1' : 'ml-2 flex' }}">
        <button type="button" class="prev-day {{ $isSmall ? 'h-8 w-7 inline-flex items-center justify-center border border-slate-300 rounded-lg bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' : 'px-2 py-1 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500' }}" data-target="{{ $uniqueId }}">
            &larr;
        </button>
        <button type="button" class="next-day {{ $isSmall ? 'h-8 w-7 inline-flex items-center justify-center border border-slate-300 rounded-lg bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none shadow-2xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' : 'px-2 py-1 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 ml-1' }}" data-target="{{ $uniqueId }}">
            &rarr;
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    function incrementDate(dateString) {
        const parts = dateString.split('-');
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        
        const date = new Date(year, month, day);
        date.setDate(date.getDate() + 1);
        
        const newYear = date.getFullYear();
        const newMonth = String(date.getMonth() + 1).padStart(2, '0');
        const newDay = String(date.getDate()).padStart(2, '0');
        
        return `${newYear}-${newMonth}-${newDay}`;
    }
    
    function decrementDate(dateString) {
        const parts = dateString.split('-');
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        
        const date = new Date(year, month, day);
        date.setDate(date.getDate() - 1);
        
        const newYear = date.getFullYear();
        const newMonth = String(date.getMonth() + 1).padStart(2, '0');
        const newDay = String(date.getDate()).padStart(2, '0');
        
        return `${newYear}-${newMonth}-${newDay}`;
    }
    
    function getTodayString() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Find buttons for this specific component instance
    const targetId = '{{ $uniqueId }}';
    const datepicker = document.getElementById(targetId);
    
    if (datepicker) {
        const prevButton = document.querySelector(`button[data-target="${targetId}"].prev-day`);
        const nextButton = document.querySelector(`button[data-target="${targetId}"].next-day`);
        
        if (prevButton) {
            prevButton.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const currentValue = datepicker.value || getTodayString();
                const newDate = decrementDate(currentValue);
                
                if (newDate >= datepicker.min) {
                    datepicker.value = newDate;
                }
                
                return false;
            };
        }
        
        if (nextButton) {
            nextButton.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const currentValue = datepicker.value || getTodayString();
                const newDate = incrementDate(currentValue);
                
                datepicker.value = newDate;
                
                return false;
            };
        }
    }
})();
</script>
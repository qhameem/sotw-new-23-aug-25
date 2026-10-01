@php
    $scheduled = !$product->is_published;
    $badge = $product->submission_type === 'badge';
    $failed = $badge && !$product->badge_verified && $product->badge_consecutive_failures > 0;
    $badgeStatus = $product->badge_verified ? 'Verified' : ($failed ? 'Failed' : 'Pending');
    $checkedAt = $product->badge_verification_attempts_max_checked_at;
    $badgeTooltip = ($product->badge_placement_url ?: $product->link)."\nLast checked: ".($checkedAt ? \Carbon\Carbon::parse($checkedAt)->timezone('UTC')->format('M j, Y H:i').' UTC' : 'Never');
    $source = ['free' => 'Free/admin approval', 'badge' => 'Badge', 'paid' => 'Paid'][$product->submission_type] ?? ucfirst((string) $product->submission_type);
@endphp
<tr data-product-row data-scheduled="{{ $scheduled ? '1' : '0' }}" class="approval-row border-b border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
    <td @class(['px-4', 'border-l-[3px] border-l-red-500' => $failed])>
        <div class="flex items-center gap-3">
            <input type="checkbox" data-row-select value="{{ $product->id }}" aria-label="Select {{ $product->name }}" class="h-4 w-4 shrink-0 rounded border-slate-400 text-indigo-600">
            <img src="{{ $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/'.$product->logo)) : 'https://www.google.com/s2/favicons?sz=64&domain_url='.urlencode($product->link) }}" alt="" width="32" height="32" loading="lazy" class="h-8 w-8 shrink-0 rounded-lg bg-slate-100 object-cover">
            <div class="min-w-0">
                <div class="flex items-center gap-2"><a href="{{ $product->link }}" target="_blank" rel="noopener nofollow" class="truncate font-semibold hover:underline" title="{{ $product->name }}">{{ $product->name }}</a><span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $source }}</span></div>
                <p class="truncate text-slate-600 dark:text-slate-300" title="{{ $product->tagline }}">{{ $product->tagline }}</p>
            </div>
        </div>
    </td>
    <td class="px-3"><div class="flex items-center gap-1"><button type="button" data-tooltip="{{ $product->user->email ?? 'No email' }}" class="truncate text-left">{{ $product->user->name ?? 'N/A' }}</button>@if($product->user?->hasRole('admin'))<span class="rounded bg-indigo-50 px-1.5 text-[10px] font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Admin</span>@endif</div></td>
    <td class="px-3"><button type="button" data-tooltip="{{ $product->categories->pluck('name')->join("\n") ?: 'No categories' }}" aria-label="All categories for {{ $product->name }}" class="flex w-full items-center gap-1 overflow-hidden text-left">
        @foreach($product->categories->take(3) as $category)<span class="max-w-[70px] truncate rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $category->name }}</span>@endforeach
        @if($product->categories->count() > 3)<span class="shrink-0 rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">+{{ $product->categories->count() - 3 }}</span>@endif
    </button></td>
    <td class="px-3">
        <form id="date-form-{{ $product->id }}" data-date-form action="{{ route('admin.product-approvals.publish-date.update', $product) }}" method="POST" class="flex items-center gap-1">
            @csrf @method('PATCH')
            <div class="relative min-w-0 flex-1">
                <span data-date-label class="block rounded-md px-1 py-2">{{ $product->published_at?->copy()->timezone('UTC')->format('M j, Y') ?? 'Set date' }}</span>
                <input aria-label="Publish date for {{ $product->name }} (UTC)" type="date" name="published_at" value="{{ $product->published_at?->copy()->timezone('UTC')->format('Y-m-d') }}" required data-date-input class="approval-date absolute inset-0 w-full cursor-pointer rounded-md border-0 bg-transparent text-sm opacity-0 focus:opacity-100 dark:bg-slate-900">
            </div>
            <button type="submit" data-date-save hidden aria-label="Save publish date for {{ $product->name }}" class="rounded p-1 text-indigo-600 focus-visible:ring-2 dark:text-indigo-300"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/></svg></button>
        </form>
    </td>
    <td class="px-3"><span @class(['rounded-full px-2 py-1 text-xs font-medium', 'bg-sky-50 text-sky-800 dark:bg-sky-950 dark:text-sky-200' => $scheduled, 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => !$scheduled])>{{ $scheduled ? 'Scheduled' : 'Published' }}</span></td>
    <td class="px-3">@if($badge)<button type="button" data-tooltip="{{ $badgeTooltip }}" @class(['rounded-full px-2 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $product->badge_verified, 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200' => $failed, 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => !$failed && !$product->badge_verified])>{{ $badgeStatus }}</button>@else<span class="text-slate-500" aria-label="Badge not required">—</span>@endif</td>
    <td class="px-3">
        <div class="flex items-center justify-end gap-1">
            @if($scheduled)
                <form action="{{ route('admin.product-approvals.publish-scheduled-now') }}" method="POST">@csrf<input type="hidden" name="publish_scope" value="selected"><input type="hidden" name="products[]" value="{{ $product->id }}"><button class="whitespace-nowrap rounded-lg bg-indigo-600 px-2 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Publish now</button></form>
            @else
                <a href="{{ route('admin.product-approvals.preview', $product) }}" class="rounded-lg border border-slate-300 px-2 py-2 text-xs font-medium dark:border-slate-600">View</a>
            @endif
            <button type="button" data-menu-open="row-menu-{{ $product->id }}" aria-controls="row-menu-{{ $product->id }}" aria-label="Actions for {{ $product->name }}" aria-haspopup="true" aria-expanded="false" class="rounded-lg px-2 py-2 font-bold hover:bg-slate-100 dark:hover:bg-slate-700">⋮</button>
            <div id="row-menu-{{ $product->id }}" data-row-menu hidden class="fixed z-40 w-48 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow-lg dark:border-slate-600 dark:bg-slate-900">
                <a href="{{ route('admin.products.edit', $product) }}?from=approvals" class="block rounded px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800">Edit</a>
                <button type="submit" form="date-form-{{ $product->id }}" data-menu-save disabled class="block w-full rounded px-3 py-2 text-left hover:bg-slate-100 disabled:opacity-40 dark:hover:bg-slate-800">Save publish date</button>
                @if($badge)<form method="POST" action="{{ route('admin.products.verify-badge', $product) }}">@csrf<button class="block w-full rounded px-3 py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-800">Check badge</button></form>@endif
                <form action="{{ route('admin.product-approvals.disapprove', $product) }}" method="POST" data-disapprove-form data-product-name="{{ $product->name }}">@csrf<button type="button" data-disapprove class="block w-full rounded px-3 py-2 text-left text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950">Disapprove</button></form>
            </div>
        </div>
    </td>
</tr>

@php
    $pricing = \App\Models\StartupDirectory::PRICING;
    $linkTypes = \App\Models\StartupDirectory::LINK_TYPES;
    $categories = \App\Models\StartupDirectory::CATEGORIES;
    $label = $directory->name;
    $domain = $directory->domain();
    $rating = $directory->dr;
    $pricingType = $pricing[$directory->pricing] ?? null;
    $linkLabel = $linkTypes[$directory->link_type] ?? 'Unknown';
    $search = $directoryFilters['q'] ?? '';
    $matchPattern = $search !== '' ? '/('.preg_quote($search, '/').')/iu' : null;
    $nameParts = $matchPattern ? preg_split($matchPattern, $label, -1, PREG_SPLIT_DELIM_CAPTURE) : [$label];
    $domainParts = $matchPattern ? preg_split($matchPattern, $domain, -1, PREG_SPLIT_DELIM_CAPTURE) : [$domain];
    $visitLabel = 'Visit '.$label;
@endphp
<tr id="directory-{{ $directory->id }}" @if($editable) data-directory-url="{{ route('admin.startup-directories.update', $directory) }}" @endif>
    <td data-label="Directory">
        <div class="directory-name">
            @if($domain !== '')
                <img class="directory-favicon" src="https://www.google.com/s2/favicons?sz=32&domain={{ rawurlencode($domain) }}"
                    width="16" height="16" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer">
            @endif
            <x-directory-cell :editable="$editable" field="name" :value="$label" :label="'name for '.$label">@foreach($nameParts as $index => $part)@if($index % 2)<mark>{{ $part }}</mark>@else {{ $part }}@endif @endforeach</x-directory-cell>
        </div>
        @if(count($domainParts) > 1)
            <div class="directory-match-domain">@foreach($domainParts as $index => $part)@if($index % 2)<mark>{{ $part }}</mark>@else {{ $part }}@endif @endforeach</div>
        @endif
    </td>
    <td data-label="DR" class="directory-dr">
        <x-directory-cell :editable="$editable" field="dr" :value="$rating" :label="'DR for '.$label">
            @if($rating !== null)
                <span @class(['directory-rating', 'directory-rating-high' => $rating >= 70, 'directory-rating-mid' => $rating >= 40 && $rating < 70])
                    style="--rating-fill: {{ max(0, min(100, $rating)) }}%">
                    <span class="directory-rating-value">{{ $rating }}</span>
                </span>
            @else
                <span class="directory-rating-unknown" aria-label="DR unknown">N/A</span>
            @endif
        </x-directory-cell>
    </td>
    <td data-label="Category">
        <x-directory-cell :editable="$editable" field="category" :value="$directory->category" :label="'category for '.$label">
            {{ $categories[$directory->category] ?? 'Uncategorized' }}
        </x-directory-cell>
    </td>
    <td data-label="Pricing">
        <x-directory-cell :editable="$editable" field="pricing" :value="$directory->pricing" :label="'pricing for '.$label">
            @if($pricingType !== null)
                <span class="directory-pricing directory-pricing-{{ $directory->pricing }}">
                    <span class="directory-pricing-dot" aria-hidden="true"></span>
                    {{ $pricingType }}
                </span>
            @elseif($editable)
                &nbsp;
            @endif
        </x-directory-cell>
    </td>
    <td data-label="Link type">
        <x-directory-cell :editable="$editable" field="link_type" :value="$directory->link_type" :label="'link type for '.$label">
            <span @class([
                'directory-link-type',
                'directory-link-type-unknown' => $linkLabel === 'Unknown',
            ])>
                @if($directory->link_type === 'dofollow')
                    <svg class="directory-link-type-check" width="12" height="12" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <circle cx="8" cy="8" r="8" fill="currentColor" />
                        <path d="m4.5 8 2.25 2.25 4.75-4.75" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                @elseif($directory->link_type === 'nofollow')
                    <span class="directory-link-type-dot" aria-hidden="true"></span>
                @endif
                {{ $linkLabel }}
            </span>
        </x-directory-cell>
    </td>
    <td data-label="Visit">
        <a class="directory-visit"
            href="{{ $directory->submission_url ?? $directory->website_url }}" target="_blank" rel="noopener noreferrer"
            aria-label="{{ $visitLabel }}" title="{{ $visitLabel }}">
            Visit <span aria-hidden="true">↗</span>
        </a>
        @if($editable)
            <details class="mt-1 text-xs">
                <summary class="directory-row-details">Row details</summary>
                <dl class="space-y-2 py-2">
                    @foreach([
                        'best_for' => 'Best for',
                        'website_url' => 'Website URL',
                        'submission_url' => 'Submission URL',
                        'source_url' => 'Source URL',
                        'requirements' => 'Submission requirements',
                        'verified_on' => 'Last verified',
                        'priority' => 'Recommended priority',
                    ] as $field => $detailLabel)
                        @php($value = $field === 'verified_on' ? $directory->verified_on?->toDateString() : $directory->{$field})
                        <div>
                            <dt class="font-medium">{{ $detailLabel }}</dt>
                            <dd class="break-all">
                                <x-directory-cell :editable="true" :field="$field" :value="$value" :label="$detailLabel.' for '.$label">{{ $value ?? 'Add value' }}</x-directory-cell>
                            </dd>
                        </div>
                    @endforeach
                </dl>
                <form method="POST" action="{{ route('admin.startup-directories.destroy', $directory) }}" data-directory-delete>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="directory-control text-red-700" aria-label="Delete {{ $label }}">Delete listing</button>
                </form>
            </details>
        @endif
    </td>
</tr>

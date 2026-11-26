@php
    $passedCheck = ($check['status'] ?? null) === 'pass';
    $guideUrl = !empty($check['key']) ? $guideLibrary->urlForKey($resolvedToolSlug, $check['key']) : null;
@endphp

<details id="{{ $check['id'] }}" @if($expanded) open @endif @class(['group scroll-mt-24', 'open:bg-amber-50/60' => ($check['status'] ?? null) === 'warning'])>
    <summary @if(($check['status'] ?? null) === 'warning' && ($loop->index ?? null) === 3) x-ref="firstAdditionalWarning" @endif
        @class(['flex cursor-pointer list-none items-center gap-2 text-sm text-slate-900 hover:bg-slate-50 [&::-webkit-details-marker]:hidden', 'px-3 py-2' => $passedCheck, 'px-5 py-3 font-semibold' => !$passedCheck])>
        @if($passedCheck)
            <span aria-hidden="true" class="font-bold text-emerald-700">✓</span>
        @elseif(in_array($check['status'] ?? null, ['warning', 'fail'], true))
            <span aria-hidden="true" @class(['h-2.5 w-2.5 shrink-0 rounded-full', 'bg-amber-600' => $check['status'] === 'warning', 'bg-rose-600' => $check['status'] === 'fail'])></span>
        @endif
        <span class="min-w-0 flex-1 break-words">
            @if($check['broken_links'])
                {{ $check['broken_links'] }} internal {{ \Illuminate\Support\Str::plural('link', $check['broken_links']) }} returned 404 or 410
            @elseif($check['empty_alt'])
                {{ $check['empty_alt'] }} images have empty alt text
            @else
                {{ $check['label'] }}
            @endif
        </span>
        @unless($passedCheck)
            <span class="hidden text-xs font-normal text-slate-500 sm:inline">{{ $check['category_label'] }}</span>
        @endunless
        <svg aria-hidden="true" class="h-4 w-4 shrink-0 group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="m5 7.5 5 5 5-5" />
        </svg>
    </summary>

    <div @class(['space-y-3 pb-4 text-sm text-slate-700', 'px-3' => $passedCheck, 'px-5' => !$passedCheck])>
        <p>{{ $check['summary'] }}</p>

        @if(!empty($check['meta']['evidence']))
            <details>
                <summary class="cursor-pointer font-semibold">Affected resources</summary>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($check['meta']['evidence'] as $evidence)
                        <li class="break-all">{{ $evidence }}</li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if(!empty($check['meta']['preview_url']))
            <img src="{{ $check['meta']['preview_url'] }}" alt="{{ $check['label'] }} preview" loading="lazy"
                @class(['rounded-lg bg-slate-50 object-contain', 'max-h-40 w-full' => ($check['key'] ?? null) === 'open_graph_image', 'h-12 w-12 p-1' => ($check['key'] ?? null) !== 'open_graph_image'])>
        @endif

        @if(!empty($check['fix']) || $check['broken_links'])
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                <p class="font-semibold text-slate-900">How to fix</p>
                <p>{{ $check['broken_links'] ? 'Update or remove the broken links. Redirect old URLs to relevant pages when appropriate.' : $check['fix'] }}</p>
            </div>
        @endif

        @if($guideUrl)
            <a href="{{ $guideUrl }}" class="font-medium underline underline-offset-2">Read the {{ $check['label'] }} guide</a>
        @endif
    </div>
</details>

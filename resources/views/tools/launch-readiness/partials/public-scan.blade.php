@php
    $url = $item->final_url ?: $item->submitted_url;
    $host = parse_url($url, PHP_URL_HOST);
    $domain = preg_replace('/^www\./i', '', $host ?: $url);
    $canVisit = $host && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    $score = max(0, min(100, (int) $item->launch_score));
    [$scoreText, $scoreFill, $scoreStroke] = match (true) {
        $score >= 90 => ['text-emerald-800', 'bg-emerald-50', 'text-emerald-600'],
        $score >= 70 => ['text-amber-900', 'bg-amber-50', 'text-amber-500'],
        default => ['text-rose-800', 'bg-rose-50', 'text-rose-600'],
    };
    $avatarColors = ['bg-orange-700', 'bg-neutral-900', 'bg-green-800', 'bg-violet-700', 'bg-blue-700', 'bg-slate-600'];
    $avatarColor = $avatarColors[hexdec(substr(md5($domain), 0, 2)) % count($avatarColors)];
    $scannedAgo = $item->scanned_at?->diffForHumans();
    $counts = [
        'Passed' => [(int) $item->passed_checks, 'bg-emerald-600'],
        'Warnings' => [(int) $item->warning_checks, 'bg-amber-500'],
        'Errors' => [(int) $item->failed_checks, 'bg-rose-600'],
    ];
    $hasChecks = array_sum(array_column($counts, 0)) > 0;
@endphp

<li @class([
    'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-5 py-3.5 '.$listColumns => $listView,
    'grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-4 rounded-2xl border border-[#e1e1db] bg-white px-5 py-6' => ! $listView,
])>
    <div class="flex min-w-0 items-center gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] text-sm font-bold text-white {{ $avatarColor }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($domain, 0, 1)) }}</span>
        <div class="min-w-0">
            <h2 class="truncate text-base font-bold text-neutral-950" title="{{ $domain }}">{{ $domain }}</h2>
            @if($listView)
                <p class="truncate text-sm text-neutral-600" title="{{ $url }}">{{ $url }}</p>
            @elseif($scannedAgo)
                <time datetime="{{ $item->scanned_at->toAtomString() }}" class="block text-sm text-neutral-600">{{ $scannedAgo }}</time>
            @endif
        </div>
    </div>

    @if($listView)
        <span class="inline-flex h-9 min-w-11 items-center justify-center justify-self-start rounded-full px-2 text-base font-bold {{ $scoreText }} {{ $scoreFill }}">
            <span class="sr-only">Score </span>{{ $score }}<span class="sr-only"> out of 100</span>
        </span>
    @else
        <span class="relative grid h-12 w-12 place-items-center text-base font-bold">
            <svg viewBox="0 0 48 48" class="absolute inset-0 h-full w-full {{ $scoreStroke }}" fill="none" stroke="currentColor" stroke-width="4.5" aria-hidden="true">
                <circle cx="24" cy="24" r="20" opacity=".16" />
                @if($score > 0)
                    <circle cx="24" cy="24" r="20" pathLength="100" stroke-dasharray="{{ $score }} 100" stroke-linecap="round" transform="rotate(-90 24 24)" />
                @endif
            </svg>
            <span class="{{ $scoreText }}"><span class="sr-only">Score </span>{{ $score }}<span class="sr-only"> out of 100</span></span>
        </span>
    @endif

    <div class="col-span-2 min-w-0 {{ $listView ? 'lg:col-span-1' : 'mt-0.5' }}">
        <div class="flex h-1.5 gap-0.5 overflow-hidden rounded-full bg-neutral-100" aria-hidden="true">
            @if($hasChecks)
                @foreach($counts as [$count, $color])
                    @if($count > 0)
                        <span class="min-w-0 basis-0 {{ $color }}" style="flex-grow: {{ $count }}"></span>
                    @endif
                @endforeach
            @endif
        </div>
        <ul role="list" class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[13px] text-neutral-700">
            @foreach($counts as $label => [$count, $color])
                <li class="inline-flex items-center gap-1.5 whitespace-nowrap">
                    <span class="h-2 w-2 rounded-full {{ $color }}" aria-hidden="true"></span>
                    {{ $count }} <span class="lowercase">{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    @if($listView)
        <div class="col-span-2 text-sm text-neutral-600 lg:col-span-1">
            @if($scannedAgo)
                <time datetime="{{ $item->scanned_at->toAtomString() }}"><span class="lg:sr-only">Scanned </span>{{ $scannedAgo }}</time>
            @endif
        </div>
    @endif

    <div class="col-span-2 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold {{ $listView ? 'lg:col-span-1 lg:justify-end' : 'mt-2 pb-2' }}">
        <a href="{{ route('launch-readiness.results.show', ['toolSlug' => $toolSlug, 'toolScan' => $item]) }}"
            aria-label="View report for {{ $domain }}" class="whitespace-nowrap text-blue-700 underline underline-offset-4 hover:text-blue-900">View report</a>
        @if($canVisit)
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="Visit {{ $domain }} (opens in a new tab)"
                class="inline-flex items-center gap-1 whitespace-nowrap text-neutral-700 hover:text-neutral-950">
                <span class="underline underline-offset-4">Visit site</span>
                <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12l8-8M5 4h7v7" /></svg>
            </a>
        @endif
    </div>
</li>

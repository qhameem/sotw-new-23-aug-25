@php
    $categories = collect($report['categories'] ?? [])->values()->map(function (array $category, int $categoryIndex) {
        $category['checks'] = collect($category['checks'] ?? [])->values()->map(function (array $check, int $checkIndex) use ($category, $categoryIndex) {
            $check['id'] = "readiness-check-{$categoryIndex}-{$checkIndex}";
            $check['category_index'] = $categoryIndex;
            $check['category_label'] = $category['label'];
            $check['empty_alt'] = 0;
            if (($check['key'] ?? null) === 'image_alt_text' && preg_match('/^0 of \d+ images have no alt attribute\. (\d+) have empty alt text\./', $check['summary'] ?? '', $matches)) {
                $check['empty_alt'] = (int) $matches[1];
            }
            $check['broken_links'] = collect($check['meta']['evidence'] ?? [])
                ->filter(fn ($entry) => is_string($entry) && preg_match('/^HTTP (404|410) · /', $entry))->count();

            return $check;
        });

        return $category;
    });
    $checks = $categories->pluck('checks')->collapse();
    $groups = $checks->groupBy(fn ($check) => match ($check['status'] ?? null) {
        'fail' => 'fail',
        'warning' => 'warning',
        'pass' => 'pass',
        default => 'pending',
    });
    $warnings = $groups->get('warning', collect())->sortByDesc('broken_links')->values();
    $errors = $groups->get('fail', collect());
    $passed = $groups->get('pass', collect());
    $pending = $groups->get('pending', collect());
    $blockers = $checks->filter(fn ($check) => !empty($check['meta']['launch_blocker']));
    $priority = $errors->concat($warnings)
        ->filter(fn ($check) => empty($check['meta']['launch_blocker']) && (!empty($check['fix']) || $check['broken_links']))
        ->sortBy(fn ($check) => match (true) {
            $check['status'] === 'fail' => 0,
            $check['broken_links'] > 0 => 1,
            ($check['key'] ?? null) === 'image_alt_text' => 2,
            default => 3,
        })->take(3);
    $socialImage = $checks->first(fn ($check) => ($check['key'] ?? null) === 'open_graph_image' && !empty($check['meta']['preview_url']));
@endphp

@if($checks->isNotEmpty())
<div class="space-y-5" x-data="{
    status: 'all',
    category: '',
    showAllWarnings: false,
    jumpToCheck(id) {
        this.status = 'all';
        this.category = '';
        this.showAllWarnings = true;
        this.$nextTick(() => {
            const check = document.getElementById(id);
            if (!check) return;
            check.open = true;
            check.querySelector('summary')?.focus({ preventScroll: true });
            check.scrollIntoView({
                block: 'start',
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
            });
        });
    }
}">
    @foreach(['Launch blockers' => $blockers, 'Fix these first' => $priority] as $heading => $items)
        @if($items->isNotEmpty())
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <h2 class="border-b border-slate-200 px-5 py-3 text-lg font-bold text-slate-900">{{ $heading }}</h2>
                <ul class="divide-y divide-slate-100">
                    @foreach($items as $issue)
                        @php
                            $showBadges = $heading === 'Fix these first';
                            [$impactLabel, $impactColor, $dotColor] = match (true) {
                                $issue['status'] === 'fail' || $issue['broken_links'] > 0 => ['High impact', 'border-red-200 bg-red-50 text-red-800', 'bg-red-600'],
                                ($issue['key'] ?? null) === 'compression' => ['Low impact', 'border-slate-300 bg-slate-100 text-slate-700', 'bg-slate-400'],
                                default => ['Medium impact', 'border-amber-200 bg-amber-50 text-amber-900', 'bg-amber-500'],
                            };
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-3 sm:flex-nowrap">
                            <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                @if($showBadges)
                                    <span class="inline-flex w-32 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-md border px-2 py-1 text-left text-[11px] font-semibold leading-none {{ $impactColor }}">
                                        <span aria-hidden="true" class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotColor }}"></span>
                                        {{ $impactLabel }}
                                    </span>
                                @endif
                                <p class="min-w-0 break-words text-sm text-slate-600 sm:flex-1">
                                    <strong class="font-bold text-slate-900">{{ $issue['label'] }}.</strong>
                                    @if($issue['broken_links'])
                                        {{ $issue['broken_links'] }} internal {{ \Illuminate\Support\Str::plural('link', $issue['broken_links']) }} returned HTTP 404 or 410.
                                    @elseif($issue['empty_alt'])
                                        Review {{ $issue['empty_alt'] }} images with empty alt text.
                                    @else
                                        {{ $showBadges ? ($issue['summary'] ?? $issue['fix'] ?? '') : ($issue['fix'] ?? $issue['summary'] ?? '') }}
                                    @endif
                                </p>
                            </div>
                            <a href="#{{ $issue['id'] }}" @click.prevent="jumpToCheck(@js($issue['id']))"
                                aria-label="View check for {{ $issue['label'] }}"
                                class="shrink-0 text-sm font-semibold text-blue-700 underline underline-offset-2 hover:text-blue-900">
                                View check
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2" role="group" aria-label="Filter by check status">
            @foreach(['all' => ['All', $checks], 'warning' => ['Warnings', $warnings], 'fail' => ['Errors', $errors], 'pass' => ['Passed', $passed], 'pending' => ['Incomplete', $pending]] as $value => [$label, $items])
                @continue($value === 'pending' && $items->isEmpty())
                <button type="button" @click="status = @js($value)" :aria-pressed="status === @js($value)"
                    class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 aria-pressed:border-slate-900 aria-pressed:bg-slate-900 aria-pressed:text-white">
                    {{ $label }} {{ $items->count() }}
                </button>
            @endforeach
        </div>
        <select aria-label="Filter by category" x-model="category" class="max-w-full rounded-lg border-slate-300 bg-white py-2 text-sm text-slate-900">
            <option value="">All categories</option>
            @foreach($categories as $index => $category)
                <option value="{{ $index }}">{{ $category['label'] }}</option>
            @endforeach
        </select>
    </div>

    @foreach(['fail' => ['Errors', $errors], 'warning' => ['Warnings', $warnings], 'pending' => ['Incomplete checks', $pending]] as $value => [$label, $items])
        @continue($items->isEmpty())
        <section x-show="(status === 'all' || status === @js($value)) && (!category || @js($items->pluck('category_index')->map(fn ($index) => (string) $index)->unique()->values()->all()).includes(category))"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <h2 class="px-5 py-3 text-lg font-bold text-slate-900">{{ $label }} ({{ $items->count() }})</h2>
            <div class="divide-y divide-slate-100 border-t border-slate-100">
                @foreach($items as $check)
                    <div x-show="(!category || category === @js((string) $check['category_index'])) && (@js($value !== 'warning' || $loop->index < 3) || showAllWarnings || category || status === 'warning')">
                        @include('tools.launch-readiness.partials.report-check', ['expanded' => $value === 'warning' && $loop->first])
                    </div>
                @endforeach
            </div>
            @if($value === 'warning' && $items->count() > 3)
                <button type="button" x-show="!showAllWarnings && !category && status === 'all'" @click="showAllWarnings = true; $nextTick(() => $refs.firstAdditionalWarning?.focus())"
                    class="w-full border-t border-slate-100 px-4 py-3 text-sm font-semibold text-[color:var(--lr-brand)] hover:underline">
                    Show {{ $items->count() - 3 }} more warnings
                </button>
            @endif
        </section>
    @endforeach

    @if($passed->isNotEmpty())
        <section x-show="(status === 'all' || status === 'pass') && (!category || @js($passed->pluck('category_index')->map(fn ($index) => (string) $index)->unique()->values()->all()).includes(category))"
            class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <h2 class="mb-4 text-lg font-bold text-slate-900"><span aria-hidden="true" class="mr-1 text-emerald-700">✓</span> Passed checks ({{ $passed->count() }})</h2>
            <div class="grid items-start gap-3 sm:grid-cols-2">
                @foreach($passed->groupBy('category_index') as $index => $items)
                    <section x-show="!category || category === @js((string) $index)" class="overflow-hidden rounded-lg border border-slate-200">
                        <h3 class="bg-slate-100 px-3 py-2 text-sm font-bold text-slate-900">{{ $items->first()['category_label'] }} ({{ $items->count() }})</h3>
                        <div class="divide-y divide-slate-100">
                            @foreach($items as $check)
                                @include('tools.launch-readiness.partials.report-check', ['expanded' => false])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    @endif

    <p x-cloak x-show="!@js($checks->map(fn ($check) => [(string) $check['category_index'], in_array($check['status'] ?? null, ['pass', 'warning', 'fail'], true) ? $check['status'] : 'pending'])->values()->all()).some(([index, result]) => (!category || category === index) && (status === 'all' || status === result))"
        class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">No checks match these filters.</p>

    @if($socialImage)
        <section x-show="status === 'all' && !category" class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-lg font-bold text-slate-900">Example social preview</h2>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <img src="{{ $socialImage['meta']['preview_url'] }}" alt="Open Graph image" loading="lazy" class="max-h-44 w-full rounded-lg bg-slate-50 object-contain sm:w-56">
                <div class="min-w-0">
                    <p class="break-all text-sm text-slate-600">{{ parse_url($summary['final_url'] ?? '', PHP_URL_HOST) }}</p>
                    @if(!empty($summary['page_title']))
                        <p class="break-words font-semibold text-slate-900">{{ $summary['page_title'] }}</p>
                    @endif
                    <p class="mt-1 text-sm text-slate-600">{{ $socialImage['summary'] }}</p>
                </div>
            </div>
        </section>
    @endif
</div>
@endif

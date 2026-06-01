@if($recentHistory->isNotEmpty())
    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Recent public scans</h2>
                <p class="mt-1 text-sm text-slate-500">Everyone can browse saved results, whether they signed in or not.</p>
            </div>
            <a href="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="text-sm font-medium text-slate-700 transition hover:text-slate-900">View all</a>
        </div>

        <div class="space-y-3">
            @foreach($recentHistory as $item)
                <a href="{{ route('launch-readiness.results.show', ['toolSlug' => $toolSlug, 'toolScan' => $item]) }}"
                   class="flex flex-col gap-3 rounded-2xl border border-slate-200 px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $item->final_url ?: $item->submitted_url }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ optional($item->scanned_at)->diffForHumans() }} · {{ $item->status_label }}</p>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <p class="text-lg font-semibold text-slate-900">{{ $item->launch_score }}</p>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Launch</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-semibold text-slate-900">{{ $item->seo_score }}</p>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">SEO</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-semibold text-slate-900">{{ $item->ai_score }}</p>
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">AI</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

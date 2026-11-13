@php
    $guideLibrary = app(\App\Support\LaunchReadinessGuideLibrary::class);
    $checkerSlug = request()->route('toolSlug') ?? app(\App\Support\ToolSettings::class)->slug(\App\Support\ToolSettings::LAUNCH_READINESS_KEY);
    $categoryDetails = [
        'meta_information' => ['document', 'Title, description, canonical URL, favicon, viewport, and language.'],
        'content_structure' => ['list', 'Main heading, heading hierarchy, and image alt text.'],
        'technical_optimization' => ['settings', 'Page status, HTTPS, redirects, indexability, headers, compression, robots.txt, and sitemap.'],
        'accessibility_basics' => ['view', 'Form labels and page landmarks.'],
        'social_and_rich_results' => ['share', 'Open Graph tags and image, Twitter Card, and structured data.'],
        'links_analysis' => ['link', 'Internal, external, and trust links.'],
        'ai_and_launch_signals' => ['ai', 'Calls to action, content clarity, original perspective, media, crawlability, and JavaScript dependence.'],
    ];
@endphp

<div class="space-y-8 text-sm leading-6 text-slate-600">
    <section>
        <h2 class="mb-3 text-xl font-semibold text-slate-900">What this tool checks</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($report['categories'] ?? [] as $category)
                <details @class(['group rounded-xl border border-slate-200 bg-white p-4', 'sm:col-span-2' => $loop->last && count($report['categories']) % 2 === 1])>
                    <summary class="flex cursor-pointer list-none items-start gap-3 [&::-webkit-details-marker]:hidden">
                        @if(isset($categoryDetails[$category['key']]))
                            @svg('carbon-'.$categoryDetails[$category['key']][0], 'mt-0.5 h-5 w-5 shrink-0 text-slate-600')
                        @endif
                        <span class="flex-1">
                            <span class="block font-semibold text-slate-900">{{ $category['label'] }}</span>
                            <span class="block text-xs">{{ count($category['checks']) }} checks · {{ $categoryDetails[$category['key']][1] ?? '' }}</span>
                        </span>
                        @svg('carbon-chevron-down', 'h-5 w-5 shrink-0 transition group-open:rotate-180')
                    </summary>
                    <ul class="mt-2 space-y-1">
                        @foreach($category['checks'] as $check)
                            @php($guideUrl = $guideLibrary->urlForKey($checkerSlug, $check['key']))
                            <li>
                                @if($guideUrl)
                                    <a href="{{ $guideUrl }}" class="underline underline-offset-2">{{ $check['label'] }}</a>
                                @else
                                    {{ $check['label'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="mb-2 text-xl font-semibold text-slate-900">How it works</h2>
        <p>Enter a public homepage URL and select Analyze. Review errors first, then warnings. Follow the suggested fixes and scan again after making changes.</p>
    </section>

    <section class="space-y-4">
        <h2 class="text-xl font-semibold text-slate-900">Sample report</h2>
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-4">
                <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full" style="background: conic-gradient(#047857 82%, #e2e8f0 0)">
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-white font-bold text-slate-900">82/100</span>
                </div>
                <div class="grid flex-1 grid-cols-3 gap-2 text-center">
                    @foreach([['SEO', 90, 40], ['AI visibility', 75, 35], ['Trust', 80, 25]] as [$label, $score, $weight])
                        <div class="rounded-lg bg-slate-100 p-2">
                            <p class="text-xs">{{ $label }}</p>
                            <p class="font-bold text-slate-900">{{ $score }}/100</p>
                            <p class="text-xs">{{ $weight }}% weight</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <ul class="mt-4 divide-y divide-slate-200">
                @foreach([
                    ['Pass', 'bg-emerald-100 text-emerald-800', 'HTTPS', 'The homepage loads securely.', 'Meets the check criteria.'],
                    ['Warning', 'bg-amber-100 text-amber-900', 'Meta description', 'Add more detail about what visitors can do.', 'Needs review.'],
                    ['Error', 'bg-red-100 text-red-800', 'Indexability', 'Remove noindex if the homepage should appear in search.', 'Failed the check.'],
                ] as [$status, $colors, $check, $finding, $meaning])
                    <li class="grid gap-1 py-3 sm:grid-cols-[1fr_11rem] sm:gap-4">
                        <div><strong class="text-slate-900">{{ $check }}</strong><p>{{ $finding }}</p></div>
                        <div><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $colors }}">{{ $status }}</span><p class="text-xs">{{ $meaning }}</p></div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold text-slate-900">Frequently asked questions</h2>
        <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer font-semibold text-slate-900">Is the checker free?</summary>
            <p>Yes. You can run a homepage audit without creating an account.</p>
        </details>
        <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer font-semibold text-slate-900">What should I verify manually?</summary>
            <p>The checker reads the public homepage response. Verify content added by JavaScript in a browser, and manually test tasks such as keyboard navigation, form submission, and checkout.</p>
        </details>
        <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer font-semibold text-slate-900">Who can see my scan?</summary>
            <p>Saving to history can list the scan in public history. Turn off Save to history to omit it from that list. Share result links only with people you want to see the report.</p>
        </details>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-lg font-semibold text-slate-900">Ready to launch your product?</h2>
        <p class="mt-1">Once you’ve addressed the findings, submit your product to Software on the Web.</p>
        <div class="mt-3 flex flex-wrap gap-4">
            <a href="{{ route('products.create') }}" class="rounded-lg bg-[#eb9e2b] px-4 py-2 font-semibold text-slate-950">Submit product</a>
            <a href="{{ route('pricing') }}" class="rounded-lg border border-slate-400 px-4 py-2 font-semibold text-slate-900">Compare launch plans</a>
        </div>
    </section>
</div>

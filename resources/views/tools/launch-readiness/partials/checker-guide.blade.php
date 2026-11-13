@php
    $guideLibrary = app(\App\Support\LaunchReadinessGuideLibrary::class);
    $checkerSlug = request()->route('toolSlug') ?? app(\App\Support\ToolSettings::class)->slug(\App\Support\ToolSettings::LAUNCH_READINESS_KEY);
@endphp

<div class="space-y-8 text-sm leading-6 text-slate-600">
    <section>
        <h2 class="mb-3 text-xl font-semibold text-slate-900">What this tool checks</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($report['categories'] ?? [] as $category)
                <details class="rounded-xl border border-slate-200 bg-white p-4">
                    <summary class="cursor-pointer font-semibold text-slate-900">{{ $category['label'] }}</summary>
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
        <p class="mt-2">The launch score combines SEO (40%), AI visibility signals (35%), and trust (25%). A pass meets the check’s criteria, a warning needs review, and an error flags a failed check.</p>
        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="font-semibold text-slate-900">Example finding</h3>
            <p><strong>Error · Indexability</strong> — Your public homepage has a noindex directive. If you want search engines to index the page, remove that directive and rerun the audit.</p>
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="text-xl font-semibold text-slate-900">Frequently asked questions</h2>
        <div>
            <h3 class="font-semibold text-slate-900">Is the checker free?</h3>
            <p>Yes. You can run a homepage audit without creating an account.</p>
        </div>
        <div>
            <h3 class="font-semibold text-slate-900">What should I verify manually?</h3>
            <p>The checker reads the public homepage response. Verify content added by JavaScript in a browser, and manually test tasks such as keyboard navigation, form submission, and checkout.</p>
        </div>
        <div>
            <h3 class="font-semibold text-slate-900">Who can see my scan?</h3>
            <p>Saving to history can list the scan in public history. Turn off Save to history to omit it from that list. Share result links only with people you want to see the report.</p>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-lg font-semibold text-slate-900">Ready to launch your product?</h2>
        <p class="mt-1">Once you’ve addressed the findings, submit your product to Software on the Web.</p>
        <div class="mt-3 flex flex-wrap gap-4">
            <a href="{{ route('products.create') }}" class="font-semibold underline underline-offset-2">Submit product</a>
            <a href="{{ route('pricing') }}" class="underline underline-offset-2">Compare launch plans</a>
        </div>
    </section>
</div>

@if($readiness)
    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7"
        aria-labelledby="submission-readiness-heading"
        x-data="{
            scan: @js($readiness),
            statusUrl: @js(route('products.submission.success', ['product' => $product, 'readiness_status' => 1])),
            async refreshScan() {
                for (let attempt = 0; this.scan.state === 'pending' && attempt < 60; attempt++) {
                    await new Promise(resolve => setTimeout(resolve, 5000));

                    try {
                        const response = await fetch(this.statusUrl, { headers: { Accept: 'application/json' } });
                        if (!response.ok) break;
                        this.scan = await response.json();
                    } catch {
                        break;
                    }
                }
            }
        }"
        x-init="refreshScan()">
        <h2 id="submission-readiness-heading" class="text-lg font-semibold text-gray-900">Launch readiness</h2>
        <div class="mt-2 space-y-2 text-sm text-gray-700" aria-live="polite">
            <p x-show="scan.state === 'pending'" @if($readiness['state'] !== 'pending') x-cloak @endif>Scan in progress. You can leave this page and return later.</p>
            <p x-show="scan.state === 'unavailable'" @if($readiness['state'] !== 'unavailable') x-cloak @endif>No public report is available for this submission.</p>
            <div x-show="scan.state === 'complete'" @if($readiness['state'] !== 'complete') x-cloak @endif>
                <p><strong x-text="scan.score">{{ $readiness['score'] ?? '' }}</strong> / 100</p>
                <p class="mt-1">
                    <span x-text="scan.passed">{{ $readiness['passed'] ?? '' }}</span> passed ·
                    <span x-text="scan.warnings">{{ $readiness['warnings'] ?? '' }}</span> warnings ·
                    <span x-text="scan.errors">{{ $readiness['errors'] ?? '' }}</span> errors
                </p>
                <a @if(isset($readiness['url'])) href="{{ $readiness['url'] }}" @endif :href="scan.url"
                    class="mt-2 inline-block font-semibold text-blue-700 underline underline-offset-4">View full report</a>
            </div>
        </div>
    </section>
@endif

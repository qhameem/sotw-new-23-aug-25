@extends('layouts.app')

@section('content')
@php
    $badgeTitle = $currentBadge?->title ?: 'Software on the Web badge';
    $badgeAltText = trim($currentBadge?->alt_text ?: 'Featured on Software on the Web');
    $badgeImageUrl = $embedData['badge_image_url'];
    $destinationUrl = $embedData['destination_url'];
    $defaultSnippet = $embedData['snippet'];
    $smallSnippet = str_replace('width="200"', 'width="160"', $defaultSnippet);
    $largeSnippet = str_replace('width="200"', 'width="260"', $defaultSnippet);
@endphp

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="max-w-3xl">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">How to add your Software on the Web badge</h1>
        <p class="mt-4 text-base leading-7 text-gray-600">
            Add the badge to your homepage, link it back to <strong>Software on the Web</strong>, and keep it live so our verification can pass.
            The easiest place is usually your homepage footer, but your header or an "As Seen On" section also works.
        </p>
    </div>

    <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-8">
            <section class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-6 shadow-sm sm:p-8">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">Current Badge</p>
                        <h2 class="mt-2 text-2xl font-semibold text-gray-900">{{ $badgeTitle }}</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600">
                            This is the current badge we expect to find on your homepage when verification runs.
                        </p>
                    </div>
                    <a
                        href="{{ $destinationUrl }}"
                        target="_blank"
                        rel="dofollow noopener noreferrer"
                        class="hidden shrink-0 rounded-full border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:border-emerald-400 hover:bg-white sm:inline-flex"
                    >
                        Opens to homepage
                    </a>
                </div>

                <div class="mt-6 rounded-xl border border-emerald-200 bg-white p-6">
                    <div class="flex min-h-[180px] items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gradient-to-br from-gray-50 to-white p-6">
                        <a href="{{ $destinationUrl }}" target="_blank" rel="dofollow noopener noreferrer">
                            <img src="{{ $badgeImageUrl }}" alt="{{ $badgeAltText }}" class="h-auto w-[200px] max-w-full border-0" />
                        </a>
                    </div>
                    <p class="mt-4 text-xs leading-6 text-gray-500">
                        Default display size: <code class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">width="200"</code>
                    </p>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Step 1</p>
                        <h2 class="mt-2 text-2xl font-semibold text-gray-900">Copy the badge code</h2>
                        <p class="mt-3 text-sm leading-6 text-gray-600">
                            Paste this exact code into your homepage HTML. The link must stay <code class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">rel="dofollow"</code>.
                        </p>
                    </div>
                    <button
                        type="button"
                        id="copy-badge-code-button"
                        data-default-label="Copy code"
                        class="inline-flex shrink-0 items-center gap-2 rounded-full border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:border-emerald-400 hover:bg-emerald-50"
                        onclick="copyBadgeCode('default-badge-code', 'copy-badge-code-button')"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-8-4h8M8 8V6a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2h-2m-4 0H6a2 2 0 01-2-2V8a2 2 0 012-2h8a2 2 0 012 2v10z" />
                        </svg>
                        <span>Copy code</span>
                    </button>
                </div>

                <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-gray-950">
                    <pre id="default-badge-code" class="overflow-x-auto px-5 py-4 text-sm leading-7 text-emerald-100">{{ $defaultSnippet }}</pre>
                </div>

                <p id="copy-badge-code-feedback" class="mt-3 hidden text-sm font-semibold text-emerald-700">
                    Badge code copied. Paste it into your homepage and publish your changes.
                </p>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Step 2</p>
                <h2 class="mt-2 text-2xl font-semibold text-gray-900">Paste it into your homepage</h2>
                <p class="mt-3 text-sm leading-6 text-gray-600">
                    Use your homepage, not a hidden page. These placements work best for verification:
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">Best option</h3>
                        <p class="mt-2 text-sm text-gray-600">Inside your homepage <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">&lt;footer&gt;</code> area.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">Also good</h3>
                        <p class="mt-2 text-sm text-gray-600">Inside the homepage <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">&lt;header&gt;</code> or hero section.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">Also valid</h3>
                        <p class="mt-2 text-sm text-gray-600">Inside an "As Seen On", "Featured In", or trust/logo section on the homepage.</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4 rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <p class="text-sm font-semibold text-amber-900">If you edit raw HTML</p>
                    <div class="space-y-3 text-sm leading-6 text-amber-900/90">
                        <p>Footer example:</p>
                        <pre class="overflow-x-auto rounded-xl bg-white px-4 py-3 text-xs text-gray-800">&lt;footer&gt;
  ...
  {!! nl2br(e($defaultSnippet)) !!}
  ...
&lt;/footer&gt;</pre>
                        <p>Header or section example:</p>
                        <pre class="overflow-x-auto rounded-xl bg-white px-4 py-3 text-xs text-gray-800">&lt;section class="as-seen-on"&gt;
  &lt;h2&gt;Featured On&lt;/h2&gt;
  {!! nl2br(e($defaultSnippet)) !!}
&lt;/section&gt;</pre>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Step 3</p>
                <h2 class="mt-2 text-2xl font-semibold text-gray-900">Find the right place in popular platforms</h2>
                <div class="mt-6 space-y-4">
                    <div class="rounded-xl border border-gray-200 p-5">
                        <h3 class="text-lg font-semibold text-gray-900">WordPress</h3>
                        <p class="mt-2 text-sm text-gray-600">Open the homepage editor and add a <strong>Custom HTML</strong> block inside the footer section, homepage content, or a widget area.</p>
                        <p class="mt-2 text-sm text-gray-600">If you use Elementor: open the homepage, drag in an <strong>HTML</strong> widget, and paste the code.</p>
                        <p class="mt-2 text-sm text-gray-600">If you use a footer builder or theme options panel: look for <strong>Footer Builder</strong>, <strong>Widgets</strong>, or <strong>Custom HTML</strong>.</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-5">
                        <h3 class="text-lg font-semibold text-gray-900">Shopify</h3>
                        <p class="mt-2 text-sm text-gray-600">Go to <strong>Online Store → Themes → Customize</strong>, open your homepage, then add a <strong>Custom Liquid</strong> block or an HTML-capable section.</p>
                        <p class="mt-2 text-sm text-gray-600">For footer placement, open the footer section inside the theme customizer and add the badge there if your theme supports custom content blocks.</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-5">
                        <h3 class="text-lg font-semibold text-gray-900">Webflow</h3>
                        <p class="mt-2 text-sm text-gray-600">Open the homepage in the Designer, add an <strong>Embed</strong> element where you want the badge, then paste the code.</p>
                        <p class="mt-2 text-sm text-gray-600">Footer placement usually means dropping the embed inside your global footer component.</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-5">
                        <h3 class="text-lg font-semibold text-gray-900">Wix</h3>
                        <p class="mt-2 text-sm text-gray-600">Edit the homepage, then use <strong>Add Elements → Embed Code → Embed HTML</strong> and paste the code.</p>
                        <p class="mt-2 text-sm text-gray-600">Place it in the homepage footer or a visible trust section, then publish.</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-5">
                        <h3 class="text-lg font-semibold text-gray-900">Squarespace</h3>
                        <p class="mt-2 text-sm text-gray-600">Edit the homepage and insert a <strong>Code Block</strong> in the section where you want the badge.</p>
                        <p class="mt-2 text-sm text-gray-600">For footer placement, edit the footer area and add the code block there.</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-5">
                        <h3 class="text-lg font-semibold text-gray-900">Static HTML, cPanel, or aaPanel</h3>
                        <p class="mt-2 text-sm text-gray-600">Open your homepage file such as <code class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">index.html</code>, <code class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">index.php</code>, or the homepage template file.</p>
                        <p class="mt-2 text-sm text-gray-600">Paste the badge code inside the homepage <code class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">&lt;footer&gt;</code>, <code class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">&lt;header&gt;</code>, or a visible section within the <code class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">&lt;body&gt;</code>, then save and publish.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Step 4</p>
                <h2 class="mt-2 text-2xl font-semibold text-gray-900">Customize the badge size</h2>
                <p class="mt-3 text-sm leading-6 text-gray-600">
                    Change the width value in the image tag. Keep the height automatic so the badge does not stretch.
                </p>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-900">Small</p>
                        <p class="mt-2 text-xs text-gray-600">Use this if your footer has limited space.</p>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-white px-3 py-3 text-[11px] leading-6 text-gray-800">{{ $smallSnippet }}</pre>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-sm font-semibold text-gray-900">Default</p>
                        <p class="mt-2 text-xs text-gray-600">Recommended for most homepages.</p>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-white px-3 py-3 text-[11px] leading-6 text-gray-800">{{ $defaultSnippet }}</pre>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-900">Large</p>
                        <p class="mt-2 text-xs text-gray-600">Use this for hero or "Featured On" sections.</p>
                        <pre class="mt-3 overflow-x-auto rounded-xl bg-white px-3 py-3 text-[11px] leading-6 text-gray-800">{{ $largeSnippet }}</pre>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    A safe rule: change only <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">width="200"</code> to another value like <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">160</code>, <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">220</code>, or <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">260</code>.
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Checklist</p>
                <ul class="mt-4 space-y-3 text-sm text-gray-700">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">1</span>
                        <span>Copy the badge code from this page.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">2</span>
                        <span>Paste it on your homepage footer, header, or visible trust section.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">3</span>
                        <span>Publish the page changes.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">4</span>
                        <span>Back on <code class="rounded bg-gray-100 px-1 py-0.5 text-gray-700">/add-product</code>, enter your homepage URL and verify the badge.</span>
                    </li>
                </ul>
            </section>

            <section class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Important</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-amber-900">
                    <li>The badge should be visible on your homepage.</li>
                    <li>Do not change the badge image URL to another image.</li>
                    <li>Do not add <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">nofollow</code> to the link.</li>
                    <li>Keep the badge live after launch so future badge checks keep passing.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>

<script>
    function copyBadgeCode(sourceId, buttonId) {
        const source = document.getElementById(sourceId);
        const button = document.getElementById(buttonId);
        const feedback = document.getElementById('copy-badge-code-feedback');

        if (!source || !button) {
            return;
        }

        const defaultLabel = button.getAttribute('data-default-label') || 'Copy code';
        const text = source.textContent;

        navigator.clipboard.writeText(text).then(() => {
            const label = button.querySelector('span');
            if (label) {
                label.textContent = 'Copied';
            }

            if (feedback) {
                feedback.classList.remove('hidden');
            }

            window.setTimeout(() => {
                if (label) {
                    label.textContent = defaultLabel;
                }

                if (feedback) {
                    feedback.classList.add('hidden');
                }
            }, 2200);
        });
    }
</script>
@endsection

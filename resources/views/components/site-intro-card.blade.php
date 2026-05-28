@props([
    'class' => '',
])

<div {{ $attributes->class(['pb-4 pt-4 md:pt-3', $class]) }}>
    <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm leading-6 text-slate-700">
        <p>
            <strong class="font-semibold text-slate-900">Software on the Web</strong> (softwareontheweb.com) is a weekly curated launch platform for software products, featuring handpicked tools across AI, productivity, design, and developer workflows.
        </p>
    </div>
</div>

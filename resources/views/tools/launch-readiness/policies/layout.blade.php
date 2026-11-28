@extends('layouts.launch-readiness')

@section('title', $policyTitle.' - Launch Readiness Checker')

@section('content')
    @include('tools.launch-readiness.partials.breadcrumb', ['currentPageLabel' => $policyTitle])

    <article>
        <h1 class="text-4xl font-bold tracking-tight text-neutral-950 sm:text-[44px]">{{ $policyTitle }}</h1>
        <div class="mt-6 space-y-6 rounded-2xl border border-neutral-200 bg-white p-5 text-sm leading-6 sm:p-7 [&_h2]:mb-1 [&_h2]:text-base [&_h2]:font-semibold [&_a]:underline [&_a]:underline-offset-4">
            @yield('policy')
        </div>
    </article>
@endsection

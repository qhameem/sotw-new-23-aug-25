@extends('layouts.launch-readiness')

@section('title', 'Sign In - ' . $toolBrandingSiteName)
@section('meta_description', 'Sign in to save and revisit launch-readiness scans with a separate tool account.')

@section('content')
    <div class="mx-auto max-w-md space-y-8 pt-8">
        <section class="text-center">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Continue to your tool account</h1>
            <p class="mt-3 text-sm leading-6 text-slate-500">
                Use Google or email. New tool accounts are created automatically the first time you continue.
            </p>
        </section>

        @include('tools.launch-readiness.auth.partials.panel', ['intended' => $intended])
    </div>
@endsection

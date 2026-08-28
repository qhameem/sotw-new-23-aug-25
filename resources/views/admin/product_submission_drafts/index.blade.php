@extends('layouts.app')

@php
    $hideSidebar = true;
    $mainContentMaxWidth = 'max-w-none';
    $containerMaxWidth = 'max-w-none';
@endphp

@section('header-title', 'Unsubmitted Products')

@section('content')
<div class="mx-auto w-full max-w-none px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-8 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:px-8">
        <h1 class="text-2xl font-semibold text-slate-900">Unsubmitted Products</h1>
        <p class="mt-1 text-sm text-slate-600">Autosaved product drafts from all users.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Last saved</th><th class="px-5 py-3 text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($drafts as $draft)
                        <tr class="align-top">
                            <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $draft->title() }}</div>@if($draft->link)<a class="mt-1 block max-w-lg truncate text-xs text-slate-500 hover:text-primary-600" href="{{ $draft->link }}" target="_blank" rel="noopener">{{ $draft->link }}</a>@endif</td>
                            <td class="px-5 py-4"><div class="font-medium text-slate-800">{{ $draft->user?->name ?: 'Unknown user' }}</div><div class="text-xs text-slate-500">{{ $draft->user?->email ?: 'No email' }}</div></td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ ($draft->last_autosaved_at ?? $draft->updated_at)?->format('M j, Y g:i A') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right"><a class="font-medium text-primary-600 hover:underline" href="{{ $draft->toSummaryArray()['resume_url'] }}">Resume draft</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">No unsubmitted product drafts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($drafts->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $drafts->links() }}</div>@endif
    </div>
</div>
@endsection

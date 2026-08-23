@extends('layouts.app')

@section('content')
@php
    $templateData = $templates->map(fn ($template) => [
        'id' => $template->id,
        'name' => $template->name,
        'instruction' => $template->instruction,
        'is_active' => $template->is_active,
    ])->values();
@endphp
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8" x-data="{
    templates: @js($templateData), selectedId: @js(optional($templates->firstWhere('is_active', true))->id), name: '', instruction: '',
    load(id) { const item = this.templates.find(row => row.id === Number(id)); this.selectedId = item ? item.id : null; this.name = item ? item.name : ''; this.instruction = item ? item.instruction : ''; }
}" x-init="load(selectedId)">
    <h1 class="text-3xl font-bold text-gray-900">Product description instructions</h1>
    <p class="mt-2 text-sm text-gray-600">The active template applies to the next AI-generated product description.</p>
    @if(session('success'))<div class="mt-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>@endif
    <div class="mt-8 grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <button type="button" @click="load(null)" class="mb-4 w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">New template</button>
            <div class="space-y-2">
                <template x-for="template in templates" :key="template.id">
                    <button type="button" @click="load(template.id)" class="w-full rounded-lg border px-3 py-3 text-left" :class="selectedId === template.id ? 'border-primary-400 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'">
                        <span class="block text-sm font-medium text-gray-900" x-text="template.name"></span>
                        <span x-show="template.is_active" class="mt-1 block text-xs font-semibold text-primary-700">Active</span>
                    </button>
                </template>
            </div>
        </aside>
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.settings.product-description-templates.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="template_id" :value="selectedId || ''">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Template name</label>
                    <input id="name" name="name" type="text" required maxlength="120" x-model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="instruction" class="block text-sm font-medium text-gray-700">LLM instruction</label>
                    <textarea id="instruction" name="instruction" rows="14" required maxlength="10000" x-model="instruction" placeholder="Example: Write only a concise overview in two paragraphs. Use a direct, neutral style." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    @error('instruction')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">Save and activate</button>
            </form>
        </section>
    </div>
</div>
@endsection

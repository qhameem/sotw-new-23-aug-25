@php
    $heroHeadline = \App\Support\HomeHero::headline();
    $canEditHero = auth()->check() && auth()->user()->hasRole('admin');
    $heroFeatures = \App\Support\HomeHero::features();
    $heroFeatureIcons = \App\Support\HomeHero::featureIcons();
@endphp
<section aria-labelledby="home-hero-heading" class="px-4 pt-8 sm:pt-10" style="padding-bottom: 43px;"
    x-data="homeHeroRotation({ phrases: @js($heroHeadline['highlights']), drafts: @js(old('highlights', $heroHeadline['highlights'])), editing: @js($canEditHero && $errors->homeHero->any()) })">
    <h2 id="home-hero-heading" class="max-w-2xl text-[44px] font-bold leading-tight tracking-tight text-gray-900">
        @if($canEditHero)
            <button type="button" @click="editing = true; $nextTick(() => $refs.headline.focus())" :aria-expanded="editing" aria-controls="home-hero-editor" title="Edit headline" class="rounded text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500">
                {{ $heroHeadline['headline'] }} <x-hero-rotating-text :phrases="$heroHeadline['highlights']" />
                <span class="sr-only">Edit headline</span>
            </button>
        @else
            {{ $heroHeadline['headline'] }} <x-hero-rotating-text :phrases="$heroHeadline['highlights']" />
        @endif
    </h2>
    @if($canEditHero)
        <form id="home-hero-editor" x-ref="editor" action="{{ route('admin.home-hero.update') }}" method="POST" x-show="editing" x-cloak @keydown.escape.prevent="cancel()" class="mt-4 space-y-3 rounded-xl border border-gray-200 p-4">
            @csrf
            @method('PATCH')
            <div>
                <label for="hero-headline" class="block text-sm font-medium text-gray-700">Headline</label>
                <input id="hero-headline" x-ref="headline" name="headline" required maxlength="150" value="{{ old('headline', $heroHeadline['headline']) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            </div>
            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-gray-700">Rotating highlighted phrases</legend>
                <p class="text-xs text-gray-500">First phrase appears before rotation starts. Add up to 20 phrases.</p>
                <template x-for="(phrase, index) in drafts" :key="index">
                    <div class="flex flex-wrap items-center gap-2">
                        <label :for="'hero-phrase-' + index" class="sr-only" x-text="'Phrase ' + (index + 1)"></label>
                        <input :id="'hero-phrase-' + index" :name="'highlights[' + index + ']'" x-model="drafts[index]" required maxlength="150" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm">
                        <button type="button" @click="move(index, -1)" :disabled="index === 0" :aria-label="'Move phrase ' + (index + 1) + ' up'" class="p-2 text-sm disabled:opacity-40">↑</button>
                        <button type="button" @click="move(index, 1)" :disabled="index === drafts.length - 1" :aria-label="'Move phrase ' + (index + 1) + ' down'" class="p-2 text-sm disabled:opacity-40">↓</button>
                        <button type="button" @click="drafts.splice(index, 1)" :disabled="drafts.length === 1" :aria-label="'Remove phrase ' + (index + 1)" class="p-2 text-sm disabled:opacity-40">Remove</button>
                    </div>
                </template>
                <button type="button" @click="drafts.push(''); $nextTick(() => document.getElementById('hero-phrase-' + (drafts.length - 1)).focus())" :disabled="drafts.length >= 20" class="rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:opacity-40">Add phrase</button>
            </fieldset>
            @foreach($errors->homeHero->all() as $error)
                <p role="alert" class="text-sm text-red-600">{{ $error }}</p>
            @endforeach
            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-[var(--color-primary-button-text)]">Save headline</button>
                <button type="button" @click="cancel()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancel</button>
            </div>
        </form>
        @if(session('home_hero_saved'))
            <x-admin-save-message class="mt-3 text-sm text-primary-700">Headline saved.</x-admin-save-message>
        @endif
    @endif
    <div @if($canEditHero) x-data="{ editingDescription: @js($errors->homeHeroDescription->any()) }" @endif>
        <p class="mt-4 max-w-xl leading-relaxed" style="font-size: 14px; color: #666666 !important;">
            @if($canEditHero)
                <button type="button" @click="editingDescription = true; $nextTick(() => $refs.description.focus())" :aria-expanded="editingDescription" aria-controls="home-hero-description-editor" title="Edit description" class="rounded text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500">
                    {{ \App\Support\HomeHero::description() }}
                    <span class="sr-only">Edit description</span>
                </button>
            @else
                {{ \App\Support\HomeHero::description() }}
            @endif
        </p>
        @if($canEditHero)
            <form id="home-hero-description-editor" action="{{ route('admin.home-hero.description.update') }}" method="POST" x-show="editingDescription" x-cloak @keydown.escape.prevent="editingDescription = false; $el.reset()" class="mt-4 space-y-3 rounded-xl border border-gray-200 p-4">
                @csrf
                @method('PATCH')
                <label for="hero-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="hero-description" x-ref="description" name="description" required maxlength="2000" rows="4" class="w-full rounded-lg border-gray-300 text-sm">{{ old('description', \App\Support\HomeHero::description()) }}</textarea>
                @foreach($errors->homeHeroDescription->all() as $error)
                    <p role="alert" class="text-sm text-red-600">{{ $error }}</p>
                @endforeach
                <div class="flex gap-3">
                    <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-[var(--color-primary-button-text)]">Save description</button>
                    <button type="button" @click="editingDescription = false; $el.form.reset()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancel</button>
                </div>
            </form>
            @if(session('home_hero_description_saved'))
                <x-admin-save-message class="mt-3 text-sm text-primary-700">Description saved.</x-admin-save-message>
            @endif
        @endif
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-2" style="font-size: 14px; color: #666666 !important;">
        <span>Get more people to find your startup on</span>
        <ul class="inline-flex items-center gap-2" aria-label="Search and AI platforms">
            @foreach(['Google' => 'google.png', 'Gemini' => 'gemini.svg', 'Claude' => 'claude.svg', 'ChatGPT' => 'chatgpt-custom.png', 'Perplexity' => 'perplexity.svg', 'Grok' => 'grok.svg'] as $platform => $icon)
                <li class="group relative flex items-center">
                    <span tabindex="0" aria-describedby="hero-platform-{{ $loop->index }}" class="inline-flex rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500">
                        <img src="{{ asset('images/ai/'.$icon) }}" alt="{{ $platform }}" width="16" height="16" class="h-4 w-4 {{ $platform === 'ChatGPT' ? 'object-cover' : 'object-contain' }}">
                    </span>
                    <span id="hero-platform-{{ $loop->index }}" role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100" style="color: #ffffff !important;">{{ $platform }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <form action="{{ route('products.create') }}" method="GET" x-data="{ submitting: false }" @submit="if (submitting) { $event.preventDefault(); return; } submitting = true" @pageshow.window="submitting = false" :aria-busy="submitting" class="mt-6 flex flex-col gap-3 rounded-2xl border-2 border-gray-900 bg-white p-2 shadow-[4px_4px_0_0_var(--color-primary-500)] sm:flex-row sm:items-center">
        <label for="hero-website-url" class="sr-only">Your software website URL</label>
        <input id="hero-website-url" name="link" type="url" required maxlength="2000" autocomplete="url" inputmode="url" placeholder="https://your-website.com"
            class="min-w-0 flex-1 rounded-lg border-0 bg-transparent px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0">
        <button type="submit" :disabled="submitting" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2 text-sm font-semibold text-[var(--color-primary-button-text)] transition-colors hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-wait">
            Submit your product
            <svg x-show="!submitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" /></svg>
            <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.3"/><path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </button>
    </form>

    <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-3 text-sm text-gray-600" aria-label="Launch features">
        @foreach([
            ['path' => 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2'],
            ['path' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8m-8 4h5'],
            ['path' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z'],
        ] as $feature)
            <li class="inline-flex items-center gap-2">
                <x-hero-feature-icon :index="$loop->index" :path="$heroFeatureIcons[$loop->index] ?? null" :default-path="$feature['path']" :editable="$canEditHero" />
                <x-hero-feature-label :label="$heroFeatures[$loop->index]" :index="$loop->index" :editable="$canEditHero" />
            </li>
        @endforeach
    </ul>
</section>

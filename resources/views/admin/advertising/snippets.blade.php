@php
    $pageOptions = [
        'all' => 'All Pages',
        'home' => 'Home',
        'products.*' => 'Products',
        'articles.*' => 'Articles',
    ];
    $locationOptions = [
        'head' => 'Head',
        'body' => 'Body',
        'sidebar' => 'Sidebar',
    ];
@endphp

<div class="space-y-8" x-data="snippetAudienceTools('{{ route('admin.advertising.detect-audience') }}')">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-5 sm:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">Code Snippets</h2>
                    <p class="mt-1 max-w-2xl text-sm text-slate-600">
                        Add snippets once, then control where they render and who should never see them.
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
                    Country exclusions rely on a proxy or CDN header such as <span class="font-medium text-slate-800">CF-IPCountry</span>.
                </div>
            </div>
        </div>

        <form action="{{ route('admin.code-snippets.store') }}" method="POST" class="px-6 py-6 sm:px-8">
            @csrf
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="page" class="block text-sm font-medium text-slate-700">Page</label>
                            <select name="page" id="page"
                                class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @foreach ($pageOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('page') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="location" class="block text-sm font-medium text-slate-700">Location</label>
                            <select name="location" id="location"
                                class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @foreach ($locationOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('location') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-medium text-slate-700">Snippet code</label>
                        <textarea name="code" id="code" rows="9"
                            class="mt-2 block w-full rounded-2xl border-slate-300 font-mono text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            placeholder="<script>...</script>">{{ old('code') }}</textarea>
                        @error('code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-6 rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Audience Exclusions</h3>
                        <p class="mt-2 text-sm text-slate-600">Leave these blank to show the snippet to everyone on the selected pages.</p>
                    </div>

                    <div>
                        <label for="excluded_ips" class="block text-sm font-medium text-slate-700">Excluded IP addresses</label>
                        <textarea name="excluded_ips" id="excluded_ips" rows="5"
                            class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            placeholder="203.0.113.10&#10;198.51.100.24">{{ old('excluded_ips') }}</textarea>
                        <p class="mt-2 text-xs text-slate-500">Use commas, spaces, or new lines for multiple addresses.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button"
                                @click="appendDetectedIp('excluded_ips')"
                                class="inline-flex items-center rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-medium text-sky-700 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="loadingAudience">
                                Use current public IP
                            </button>
                        </div>
                        @error('excluded_ips')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="excluded_countries" class="block text-sm font-medium text-slate-700">Excluded countries</label>
                        <select name="excluded_countries[]" id="excluded_countries" multiple size="8"
                            class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                            @foreach ($countries as $countryCode => $countryName)
                                <option value="{{ $countryCode }}" @selected(in_array($countryCode, old('excluded_countries', []), true))>
                                    {{ $countryName }} ({{ $countryCode }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Hold Command on Mac to select multiple countries.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button"
                                @click="selectDetectedCountry('excluded_countries')"
                                class="inline-flex items-center rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="loadingAudience">
                                Use current country
                            </button>
                        </div>
                        @error('excluded_countries')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('excluded_countries.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                @click="loadAudience()"
                                class="inline-flex items-center rounded-2xl border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="loadingAudience">
                                <span x-show="!loadingAudience">Detect current network</span>
                                <span x-show="loadingAudience">Detecting...</span>
                            </button>
                            <span class="text-xs text-slate-500" x-show="audienceLoaded">
                                Current request:
                                <span class="font-medium text-slate-800" x-text="detectedIp || 'IP unavailable'"></span>
                                <span class="text-slate-400">/</span>
                                <span class="font-medium text-slate-800" x-text="detectedCountryLabel || 'Country unavailable'"></span>
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-red-600" x-show="audienceError" x-text="audienceError"></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                    Save Snippet
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-2 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Existing Snippets</h2>
                <p class="mt-1 text-sm text-slate-600">Review targeting rules, adjust exclusions, and update snippets inline.</p>
            </div>
            <div class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                {{ $snippets->count() }} total
            </div>
        </div>

        <div class="mt-6 space-y-5">
            @forelse ($snippets as $snippet)
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50/70 shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700">
                                    {{ $locationOptions[$snippet->location] ?? $snippet->location }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
                                    {{ $pageOptions[$snippet->page] ?? $snippet->page }}
                                </span>
                                <span class="text-xs text-slate-500">Updated {{ $snippet->updated_at?->diffForHumans() }}</span>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-600">
                                    {{ count($snippet->excluded_ips ?? []) }} IP exclusion{{ count($snippet->excluded_ips ?? []) === 1 ? '' : 's' }}
                                </span>
                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-600">
                                    {{ count($snippet->excluded_countries ?? []) }} countr{{ count($snippet->excluded_countries ?? []) === 1 ? 'y' : 'ies' }}
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.code-snippets.destroy', $snippet) }}" method="POST"
                            onsubmit="return confirm('Delete this snippet?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center rounded-2xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-100">
                                Delete
                            </button>
                        </form>
                    </div>

                    <div class="px-5 py-5">
                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                            <div>
                                <p class="mb-2 text-sm font-medium text-slate-700">Current snippet</p>
                                <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-slate-100"><code>{{ e($snippet->code) }}</code></pre>
                            </div>

                            <div class="space-y-4">
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="text-sm font-medium text-slate-700">Excluded IPs</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse ($snippet->excluded_ips ?? [] as $ip)
                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">{{ $ip }}</span>
                                        @empty
                                            <span class="text-sm text-slate-500">No IP exclusions.</span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="text-sm font-medium text-slate-700">Excluded countries</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse ($snippet->excluded_countries ?? [] as $countryCode)
                                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800">
                                                {{ $countries[$countryCode] ?? $countryCode }} ({{ $countryCode }})
                                            </span>
                                        @empty
                                            <span class="text-sm text-slate-500">No country exclusions.</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <details class="mt-5 rounded-2xl border border-slate-200 bg-white">
                            <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-slate-700">
                                Edit snippet settings
                            </summary>
                            <form action="{{ route('admin.code-snippets.update', $snippet) }}" method="POST" class="border-t border-slate-200 px-4 py-4">
                                @csrf
                                @method('PUT')

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="page-{{ $snippet->id }}" class="block text-sm font-medium text-slate-700">Page</label>
                                        <select name="page" id="page-{{ $snippet->id }}"
                                            class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                            @foreach ($pageOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($snippet->page === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="location-{{ $snippet->id }}" class="block text-sm font-medium text-slate-700">Location</label>
                                        <select name="location" id="location-{{ $snippet->id }}"
                                            class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                            @foreach ($locationOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($snippet->location === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label for="code-{{ $snippet->id }}" class="block text-sm font-medium text-slate-700">Snippet code</label>
                                    <textarea name="code" id="code-{{ $snippet->id }}" rows="8"
                                        class="mt-2 block w-full rounded-2xl border-slate-300 font-mono text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ $snippet->code }}</textarea>
                                </div>

                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div>
                                        <label for="excluded-ips-{{ $snippet->id }}" class="block text-sm font-medium text-slate-700">Excluded IP addresses</label>
                                        <textarea name="excluded_ips" id="excluded-ips-{{ $snippet->id }}" rows="5"
                                            class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ implode(PHP_EOL, $snippet->excluded_ips ?? []) }}</textarea>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="button"
                                                @click="appendDetectedIp('excluded-ips-{{ $snippet->id }}')"
                                                class="inline-flex items-center rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-medium text-sky-700 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="loadingAudience">
                                                Use current public IP
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="excluded-countries-{{ $snippet->id }}" class="block text-sm font-medium text-slate-700">Excluded countries</label>
                                        <select name="excluded_countries[]" id="excluded-countries-{{ $snippet->id }}" multiple size="8"
                                            class="mt-2 block w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                            @foreach ($countries as $countryCode => $countryName)
                                                <option value="{{ $countryCode }}" @selected(in_array($countryCode, $snippet->excluded_countries ?? [], true))>
                                                    {{ $countryName }} ({{ $countryCode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <button type="button"
                                                @click="selectDetectedCountry('excluded-countries-{{ $snippet->id }}')"
                                                class="inline-flex items-center rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="loadingAudience">
                                                Use current country
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button"
                                            @click="loadAudience()"
                                            class="inline-flex items-center rounded-2xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="loadingAudience">
                                            <span x-show="!loadingAudience">Detect current network</span>
                                            <span x-show="loadingAudience">Detecting...</span>
                                        </button>
                                        <span class="text-xs text-slate-500" x-show="audienceLoaded">
                                            Current request:
                                            <span class="font-medium text-slate-800" x-text="detectedIp || 'IP unavailable'"></span>
                                            <span class="text-slate-400">/</span>
                                            <span class="font-medium text-slate-800" x-text="detectedCountryLabel || 'Country unavailable'"></span>
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs text-red-600" x-show="audienceError" x-text="audienceError"></p>
                                </div>

                                <div class="mt-5 flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                                        Update Snippet
                                    </button>
                                </div>
                            </form>
                        </details>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">No snippets yet</h3>
                    <p class="mt-2 text-sm text-slate-600">Create your first snippet above, then fine-tune its exclusions here.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>

<script>
    function snippetAudienceTools(detectAudienceUrl) {
        return {
            loadingAudience: false,
            audienceLoaded: false,
            audienceError: '',
            detectedIp: '',
            detectedCountryCode: '',
            detectedCountryLabel: '',
            async loadAudience() {
                if (this.loadingAudience) {
                    return;
                }

                this.loadingAudience = true;
                this.audienceError = '';

                try {
                    const response = await fetch(detectAudienceUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Unable to detect the current network identity right now.');
                    }

                    const payload = await response.json();

                    this.detectedIp = payload.ip ?? '';
                    this.detectedCountryCode = payload.country_code ?? '';
                    this.detectedCountryLabel = payload.country_name && payload.country_code
                        ? `${payload.country_name} (${payload.country_code})`
                        : (payload.country_code ?? payload.country_name ?? '');
                    this.audienceLoaded = true;
                } catch (error) {
                    this.audienceError = error.message ?? 'Unable to detect the current network identity right now.';
                } finally {
                    this.loadingAudience = false;
                }
            },
            async appendDetectedIp(fieldId) {
                if (!this.detectedIp) {
                    await this.loadAudience();
                }

                if (!this.detectedIp) {
                    this.audienceError = 'No public IP was detected for this request.';
                    return;
                }

                const field = document.getElementById(fieldId);

                if (!field) {
                    return;
                }

                const entries = field.value
                    .split(/[\s,]+/)
                    .map((value) => value.trim())
                    .filter(Boolean);

                if (!entries.includes(this.detectedIp)) {
                    entries.push(this.detectedIp);
                    field.value = entries.join('\n');
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            },
            async selectDetectedCountry(fieldId) {
                if (!this.detectedCountryCode) {
                    await this.loadAudience();
                }

                if (!this.detectedCountryCode) {
                    this.audienceError = 'No country code was detected for this request.';
                    return;
                }

                const field = document.getElementById(fieldId);

                if (!field) {
                    return;
                }

                const option = Array.from(field.options).find((candidate) => candidate.value === this.detectedCountryCode);

                if (option) {
                    option.selected = true;
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            },
        };
    }
</script>

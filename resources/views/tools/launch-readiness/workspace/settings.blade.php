@extends('layouts.launch-readiness-app')

@section('title', 'Settings - ' . $toolBrandingSiteName)
@section('meta_description', 'Manage your launch-readiness tool profile and account settings.')

@section('content')
    <div class="mx-auto max-w-7xl space-y-8">
        <section>
            <h1 class="text-3xl font-semibold tracking-tight text-[var(--lr-text)]">Settings</h1>
            <p class="mt-2 text-base text-[var(--lr-muted)]">Manage your profile and account settings</p>
        </section>

        <div class="border-t pt-8" style="border-color: var(--lr-border);">
            <div class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside>
                    <nav class="space-y-2">
                        <a href="{{ route('launch-readiness.settings', ['toolSlug' => $toolSlug, 'tab' => 'profile']) }}" class="block rounded-[20px] px-4 py-3 text-sm font-medium transition" style="{{ $activeTab === 'profile' ? 'background: var(--lr-panel-strong); color: var(--lr-text);' : 'color: var(--lr-muted);' }}">Profile</a>
                        <a href="{{ route('launch-readiness.settings', ['toolSlug' => $toolSlug, 'tab' => 'password']) }}" class="block rounded-[20px] px-4 py-3 text-sm font-medium transition" style="{{ $activeTab === 'password' ? 'background: var(--lr-panel-strong); color: var(--lr-text);' : 'color: var(--lr-muted);' }}">Password</a>
                        <a href="{{ route('launch-readiness.settings', ['toolSlug' => $toolSlug, 'tab' => 'two-factor']) }}" class="block rounded-[20px] px-4 py-3 text-sm font-medium transition" style="{{ $activeTab === 'two-factor' ? 'background: var(--lr-panel-strong); color: var(--lr-text);' : 'color: var(--lr-muted);' }}">Two-Factor Auth</a>
                        @if($toolUserIsAdmin ?? false)
                            <a href="{{ route('launch-readiness.settings', ['toolSlug' => $toolSlug, 'tab' => 'branding']) }}" class="block rounded-[20px] px-4 py-3 text-sm font-medium transition" style="{{ $activeTab === 'branding' ? 'background: var(--lr-panel-strong); color: var(--lr-text);' : 'color: var(--lr-muted);' }}">Branding</a>
                        @endif
                    </nav>
                </aside>

                <section class="max-w-3xl">
                    @if($activeTab === 'profile')
                        <div class="space-y-10">
                            <div>
                                <h2 class="text-2xl font-semibold text-[var(--lr-text)]">Profile</h2>
                                <p class="mt-2 text-base text-[var(--lr-muted)]">Update your name and email address</p>
                            </div>

                            <form method="POST" action="{{ route('launch-readiness.settings.profile.update', ['toolSlug' => $toolSlug]) }}" class="space-y-8">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label for="settings_name" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Name</label>
                                    <input id="settings_name" name="name" type="text" value="{{ old('name', $toolUser->name) }}" class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0" style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);" placeholder="Your name">
                                </div>
                                <div>
                                    <label for="settings_email" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Email</label>
                                    <input id="settings_email" name="email" type="email" value="{{ old('email', $toolUser->email) }}" class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0" style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);">
                                </div>
                                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-full border border-slate-900 bg-slate-900 px-5 text-sm font-semibold text-white transition hover:border-slate-800 hover:bg-slate-800">Save</button>
                            </form>

                            <div class="pt-8">
                                <h3 class="text-xl font-semibold text-[var(--lr-text)]">Delete account</h3>
                                <p class="mt-2 text-base text-[var(--lr-muted)]">Delete your account and all of its resources</p>
                                <form method="POST" action="{{ route('launch-readiness.settings.account.destroy', ['toolSlug' => $toolSlug]) }}" class="mt-6">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-full border border-rose-300 px-5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Delete account</button>
                                </form>
                            </div>
                        </div>
                    @elseif($activeTab === 'password')
                        <div class="space-y-8">
                            <div>
                                <h2 class="text-2xl font-semibold text-[var(--lr-text)]">Password</h2>
                                <p class="mt-2 text-base text-[var(--lr-muted)]">Tool accounts currently use Google sign-in or email one-time codes.</p>
                            </div>

                            <div class="rounded-[24px] border p-6" style="border-color: var(--lr-border); background: var(--lr-panel-soft);">
                                <p class="text-base font-medium text-[var(--lr-text)]">Password sign-in is not enabled for launch-readiness tool accounts yet.</p>
                                <p class="mt-3 text-base leading-7 text-[var(--lr-muted)]">This section is reserved for a future password-based login flow. Your current account access is still protected through Google sign-in or email verification codes.</p>
                            </div>
                        </div>
                    @elseif($activeTab === 'two-factor')
                        <div class="space-y-8">
                            <div>
                                <h2 class="text-2xl font-semibold text-[var(--lr-text)]">Two-Factor Auth</h2>
                                <p class="mt-2 text-base text-[var(--lr-muted)]">Review how your tool account is currently verified.</p>
                            </div>

                            <div class="rounded-[24px] border p-6" style="border-color: var(--lr-border); background: var(--lr-panel-soft);">
                                <p class="text-base font-medium text-[var(--lr-text)]">Authenticator-based 2FA is not enabled for tool accounts yet.</p>
                                <p class="mt-3 text-base leading-7 text-[var(--lr-muted)]">For now, access is verified either through Google OAuth or the 6-digit email sign-in code flow. This keeps sign-in simple while leaving room for stronger 2FA later.</p>
                            </div>
                        </div>
                    @elseif($activeTab === 'branding' && ($toolUserIsAdmin ?? false))
                        <div class="space-y-10">
                            <div>
                                <h2 class="text-2xl font-semibold text-[var(--lr-text)]">Project Branding</h2>
                                <p class="mt-2 text-base text-[var(--lr-muted)]">Update the logo, site name, favicon, typography, and background color for this tool.</p>
                            </div>

                            <form
                                method="POST"
                                action="{{ route('launch-readiness.dashboard.branding.update', ['toolSlug' => $toolSlug]) }}"
                                enctype="multipart/form-data"
                                class="space-y-8"
                                x-data="{
                                    fontColor: @js(old('font_color', $toolBranding['font_color'] ?? '#161616')),
                                    backgroundColor: @js(old('background_color', $toolBranding['background_color'] ?? '#f5f5f4'))
                                }"
                            >
                                @csrf
                                @method('PATCH')

                                <div class="space-y-8">
                                    <div class="space-y-6">
                                        <div>
                                            <label for="branding_site_name" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Site name</label>
                                            <input
                                                id="branding_site_name"
                                                name="site_name"
                                                type="text"
                                                value="{{ old('site_name', $toolBrandingSiteName) }}"
                                                class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                maxlength="120"
                                            >
                                        </div>

                                        <div>
                                            <label for="branding_tool_slug" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Tool slug</label>
                                            <input
                                                id="branding_tool_slug"
                                                name="tool_slug"
                                                type="text"
                                                value="{{ old('tool_slug', $toolSlug) }}"
                                                class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                placeholder="website-launch-checker"
                                                maxlength="120"
                                            >
                                            <p class="mt-2 text-xs text-[var(--lr-muted)]">Used in the URL: <span class="font-medium text-[var(--lr-text)]">/tools/{{ old('tool_slug', $toolSlug) }}</span></p>
                                        </div>

                                        <div>
                                            <label for="branding_homepage_h1" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Homepage H1</label>
                                            <input
                                                id="branding_homepage_h1"
                                                name="homepage_h1"
                                                type="text"
                                                value="{{ old('homepage_h1', $toolHomepageH1) }}"
                                                class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                maxlength="160"
                                            >
                                        </div>

                                        <div>
                                            <label for="branding_homepage_title_tag" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Homepage title tag</label>
                                            <input
                                                id="branding_homepage_title_tag"
                                                name="homepage_title_tag"
                                                type="text"
                                                value="{{ old('homepage_title_tag', $toolHomepageTitleTag) }}"
                                                class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                maxlength="255"
                                            >
                                        </div>

                                        <div>
                                            <label for="branding_homepage_meta_description" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Homepage meta description</label>
                                            <textarea
                                                id="branding_homepage_meta_description"
                                                name="homepage_meta_description"
                                                rows="4"
                                                class="block w-full rounded-2xl border px-4 py-3 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                maxlength="255"
                                            >{{ old('homepage_meta_description', $toolHomepageMetaDescription) }}</textarea>
                                        </div>

                                        <div>
                                            <label for="branding_font_url" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Google Font link</label>
                                            <input
                                                id="branding_font_url"
                                                name="font_url"
                                                type="url"
                                                value="{{ old('font_url', $toolBranding['font_url'] ?? '') }}"
                                                class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                placeholder="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&display=swap"
                                            >
                                            <p class="mt-2 text-xs text-[var(--lr-muted)]">Paste any Google Fonts CSS link. The first font family in the URL will be used automatically.</p>
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-3">
                                            <div>
                                                <label for="branding_font_size" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Font size</label>
                                                <input
                                                    id="branding_font_size"
                                                    name="font_size"
                                                    type="number"
                                                    min="14"
                                                    max="20"
                                                    value="{{ old('font_size', $toolBranding['font_size'] ?? 16) }}"
                                                    class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                    style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                >
                                            </div>

                                            <div>
                                                <label for="branding_font_color" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Font color</label>
                                                <div class="flex items-center gap-3">
                                                    <input
                                                        id="branding_font_color"
                                                        type="color"
                                                        x-model="fontColor"
                                                        class="block h-[52px] w-16 rounded-[0.9rem] border border-[var(--lr-border)] bg-[var(--lr-panel-strong)] px-2"
                                                    >
                                                    <input
                                                        name="font_color"
                                                        type="text"
                                                        x-model="fontColor"
                                                        class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                        style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                        placeholder="#161616"
                                                        maxlength="7"
                                                        spellcheck="false"
                                                    >
                                                </div>
                                            </div>

                                            <div>
                                                <label for="branding_background_color" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Background color</label>
                                                <div class="flex items-center gap-3">
                                                    <input
                                                        id="branding_background_color"
                                                        type="color"
                                                        x-model="backgroundColor"
                                                        class="block h-[52px] w-16 rounded-[0.9rem] border border-[var(--lr-border)] bg-[var(--lr-panel-strong)] px-2"
                                                    >
                                                    <input
                                                        name="background_color"
                                                        type="text"
                                                        x-model="backgroundColor"
                                                        class="block h-12 w-full rounded-2xl border px-4 text-sm outline-none transition placeholder:text-[var(--lr-subtle)] focus:ring-0"
                                                        style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text);"
                                                        placeholder="#f5f5f4"
                                                        maxlength="7"
                                                        spellcheck="false"
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="branding_logo" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Logo</label>
                                            <input
                                                id="branding_logo"
                                                name="logo"
                                                type="file"
                                                accept=".svg,.png,.jpg,.jpeg,.webp,.gif"
                                                class="block w-full text-sm text-[var(--lr-muted)] file:mr-4 file:rounded-full file:border-0 file:bg-[var(--lr-panel-strong)] file:px-4 file:py-2 file:font-medium file:text-[var(--lr-text)]"
                                            >
                                            <input type="hidden" name="remove_logo" value="0">
                                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-[var(--lr-muted)]">
                                                <input type="checkbox" name="remove_logo" value="1" class="h-4 w-4 rounded border-[var(--lr-border)] text-[var(--lr-success)] focus:ring-0">
                                                <span>Remove custom logo</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label for="branding_favicon" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">Favicon</label>
                                            <input
                                                id="branding_favicon"
                                                name="favicon"
                                                type="file"
                                                accept=".ico,.png,.svg"
                                                class="block w-full text-sm text-[var(--lr-muted)] file:mr-4 file:rounded-full file:border-0 file:bg-[var(--lr-panel-strong)] file:px-4 file:py-2 file:font-medium file:text-[var(--lr-text)]"
                                            >
                                            <input type="hidden" name="remove_favicon" value="0">
                                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-[var(--lr-muted)]">
                                                <input type="checkbox" name="remove_favicon" value="1" class="h-4 w-4 rounded border-[var(--lr-border)] text-[var(--lr-success)] focus:ring-0">
                                                <span>Remove custom favicon</span>
                                            </label>
                                        </div>

                                        <div>
                                            <label for="branding_og_image" class="mb-3 block text-sm font-semibold text-[var(--lr-text)]">OG image</label>
                                            <input
                                                id="branding_og_image"
                                                name="og_image"
                                                type="file"
                                                accept=".png,.jpg,.jpeg,.webp"
                                                class="block w-full text-sm text-[var(--lr-muted)] file:mr-4 file:rounded-full file:border-0 file:bg-[var(--lr-panel-strong)] file:px-4 file:py-2 file:font-medium file:text-[var(--lr-text)]"
                                            >
                                            <p class="mt-2 text-xs text-[var(--lr-muted)]">Recommended for link previews and social sharing. Best as a wide image such as 1200×630.</p>
                                            <input type="hidden" name="remove_og_image" value="0">
                                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-[var(--lr-muted)]">
                                                <input type="checkbox" name="remove_og_image" value="1" class="h-4 w-4 rounded border-[var(--lr-border)] text-[var(--lr-success)] focus:ring-0">
                                                <span>Remove custom OG image</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="space-y-5 rounded-[24px] border p-6" style="border-color: var(--lr-border); background: var(--lr-panel-soft);">
                                        <div>
                                            <p class="text-sm font-semibold text-[var(--lr-text)]">Preview</p>
                                            <p class="mt-1 text-sm text-[var(--lr-muted)]">These values will appear across the public tool pages and the signed-in workspace.</p>
                                        </div>

                                        <div class="flex items-center gap-4 rounded-[20px] border p-4" style="border-color: var(--lr-border); background: var(--lr-panel);">
                                            <img src="{{ $toolBrandingLogoUrl }}" alt="{{ $toolBrandingSiteName }} logo" class="h-12 w-12 rounded-2xl object-contain">
                                            <div>
                                                <p class="text-lg font-semibold text-[var(--lr-text)]">{{ $toolBrandingSiteName }}</p>
                                                <p class="mt-1 text-sm text-[var(--lr-muted)]">/tools/{{ $toolSlug }}</p>
                                                <p class="text-sm text-[var(--lr-muted)]">{{ $toolBranding['font_family'] ?? 'Inter' }} · {{ $toolBranding['font_size'] ?? 16 }}px</p>
                                            </div>
                                        </div>

                                        <div class="rounded-[20px] border p-4" style="border-color: var(--lr-border); background: var(--lr-panel);">
                                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--lr-muted)]">Homepage SEO Preview</p>
                                            <p class="mt-3 text-lg font-semibold text-[var(--lr-text)]">{{ old('homepage_title_tag', $toolHomepageTitleTag) }}</p>
                                            <p class="mt-2 text-sm font-medium text-emerald-700">{{ url('/tools/' . old('tool_slug', $toolSlug)) }}</p>
                                            <p class="mt-2 text-sm leading-6 text-[var(--lr-muted)]">{{ old('homepage_meta_description', $toolHomepageMetaDescription) }}</p>
                                            <p class="mt-4 text-base font-semibold text-[var(--lr-text)]">{{ old('homepage_h1', $toolHomepageH1) }}</p>
                                        </div>

                                        <div class="grid gap-4 lg:grid-cols-2">
                                            <div class="flex items-center gap-4">
                                                <div class="rounded-2xl border p-3" style="border-color: var(--lr-border); background: var(--lr-panel);">
                                                    @if($toolBrandingFaviconUrl)
                                                        <img src="{{ $toolBrandingFaviconUrl }}" alt="{{ $toolBrandingSiteName }} favicon" class="h-8 w-8 rounded-xl object-contain">
                                                    @else
                                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border text-xs font-semibold text-[var(--lr-muted)]" style="border-color: var(--lr-border);">ICO</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-[var(--lr-muted)]">
                                                    <p class="font-medium text-[var(--lr-text)]">Current favicon</p>
                                                    <p>{{ $toolBrandingFaviconUrl ? 'Custom favicon uploaded' : 'Using default site favicon' }}</p>
                                                </div>
                                            </div>

                                            <div class="rounded-[20px] border p-4" style="border-color: var(--lr-border); background: var(--lr-panel);">
                                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--lr-muted)]">OG Image Preview</p>
                                                @if($toolBrandingOgImageUrl)
                                                    <img src="{{ $toolBrandingOgImageUrl }}" alt="{{ $toolBrandingSiteName }} OG image" class="mt-3 aspect-[1200/630] w-full rounded-2xl object-cover">
                                                    <p class="mt-3 text-sm text-[var(--lr-muted)]">Custom OG image uploaded.</p>
                                                @else
                                                    <img src="{{ $toolOgImage }}" alt="{{ $toolBrandingSiteName }} default OG image" class="mt-3 aspect-[1200/630] w-full rounded-2xl object-cover">
                                                    <p class="mt-3 text-sm text-[var(--lr-muted)]">Using the default OG image.</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="rounded-[20px] border p-4" style="border-color: var(--lr-border); background: {{ $toolBranding['background_color'] ?? '#f5f5f4' }};">
                                            <p class="text-sm font-semibold" style="color: {{ $toolBranding['font_color'] ?? '#161616' }};">Color preview</p>
                                            <p class="mt-1 text-sm" style="color: {{ $toolBranding['font_color'] ?? '#161616' }};">This preview uses the current project background and font color.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-full border border-slate-900 bg-slate-900 px-5 text-sm font-semibold text-white transition hover:border-slate-800 hover:bg-slate-800">Save branding</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection

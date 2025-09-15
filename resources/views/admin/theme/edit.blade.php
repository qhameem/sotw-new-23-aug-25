@extends('layouts.app')

@section('title', 'Theme Settings')

@section('header-title')
    Theme Settings
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white  overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 ">
                    @if (session('success'))
                        <div class="mb-4 font-medium text-sm text-green-600 ">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4">
                            <div class="font-medium text-red-600 ">{{ __('Whoops! Something went wrong.') }}</div>
                            <ul class="mt-3 list-disc list-inside text-sm text-red-600 ">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.theme.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="font_url" :value="__('Google Font URL')" />
                            <x-text-input id="font_url" class="block mt-1 w-full" type="url" name="font_url" :value="old('font_url', $currentFontUrl)" required autofocus />
                            <p class="mt-2 text-sm text-gray-500 ">
                                Paste the full Google Font URL here. Example: <code>https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap</code>
                            </p>
                            <p class="mt-1 text-sm text-gray-500 ">
                                Current active font family: <strong>{{ $currentFontFamily }}</strong>
                            </p>
                        </div>

                        {{-- Font Family Selection --}}
                        @if (!empty($availableFonts) && count($availableFonts) > 1)
                        <div class="mt-4 p-4 bg-gray-50 rounded-lg border">
                            <h4 class="font-semibold text-md text-gray-800">Font Configuration</h4>
                            
                            <div class="mt-4">
                                <x-input-label for="default_font_family" :value="__('Select Default Font Family')" />
                                <select name="default_font_family" id="default_font_family" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">
                                    @foreach ($availableFonts as $font)
                                        <option value="{{ $font }}" @if ($font === $currentFontFamily) selected @endif>
                                            {{ $font }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-sm text-gray-600">
                                    The selected font will be applied as the default throughout the site.
                                </p>
                            </div>

                            <div class="mt-6">
                                <h5 class="font-semibold text-sm text-gray-700">Using Other Available Fonts</h5>
                                <p class="mt-2 text-sm text-gray-600">
                                    Your Google Font link included multiple font families. While one is set as the default, you can apply the others using specific Tailwind CSS classes.
                                </p>
                                <div class="mt-3 p-3 bg-gray-100 rounded-md text-sm">
                                    <p class="font-medium text-gray-800">Instructions:</p>
                                    <p class="mt-1">To use an alternative font for a specific element, add the corresponding class directly in your Blade templates:</p>
                                    <ul class="mt-2 list-disc list-inside space-y-1">
                                        @foreach ($availableFonts as $font)
                                            @if ($font !== $currentFontFamily)
                                                @php
                                                    // Create a slug-like class name, e.g., "Noto Serif" -> "font-noto-serif"
                                                    $fontClass = 'font-' . Str::slug($font);
                                                @endphp
                                                <li>For <strong>{{ $font }}</strong>, use the class: <code class="px-1 py-0.5 bg-gray-200 rounded text-xs">{{ $fontClass }}</code></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                    <p class="mt-3 text-xs text-gray-500">
                                        Example: <code><h1 class="text-2xl {{ 'font-' . Str::slug($availableFonts[1] ?? 'example') }}">Title with alternate font</h1></code>
                                    </p>
                                </div>
                                <p class="mt-3 text-xs text-gray-500">
                                    <strong>Note:</strong> For these classes to work, you must add them to your <code class="text-xs">tailwind.config.js</code> file. This will be handled in a future update. For now, this is a manual process.
                                </p>
                            </div>
                        </div>
                        @endif


                        {{-- Primary Color Picker --}}
                        <div class="mt-6" x-data="{
                            primaryBgColor: {{ Js::from(old('primary_color', $currentPrimaryColor ?? '#3b82f6')) }},
                            primaryTextColor: {{ Js::from(old('primary_button_text_color', $currentPrimaryButtonTextColor ?? '')) }},
                            suggestedTextColor: '#ffffff',
                            contrastRatio: 0,
                            contrastLevel: 'Fail',

                            isValidHex(color) {
                                return /^#([0-9A-F]{6}|[0-9A-F]{3})$/i.test(color);
                            },
                            getLuminance(hex) {
                                const rgb = this.hexToRgb(hex);
                                if (!rgb) return 0;
                                const [r, g, b] = [rgb.r, rgb.g, rgb.b].map(c => {
                                    c /= 255;
                                    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
                                });
                                return 0.2126 * r + 0.7152 * g + 0.0722 * b;
                            },
                            hexToRgb(hex) {
                                if (!this.isValidHex(hex)) return null;
                                let r = 0, g = 0, b = 0;
                                if (hex.length === 4) {
                                    r = parseInt(hex[1] + hex[1], 16);
                                    g = parseInt(hex[2] + hex[2], 16);
                                    b = parseInt(hex[3] + hex[3], 16);
                                } else if (hex.length === 7) {
                                    r = parseInt(hex.substring(1, 3), 16);
                                    g = parseInt(hex.substring(3, 5), 16);
                                    b = parseInt(hex.substring(5, 7), 16);
                                }
                                return { r, g, b };
                            },
                            getContrast(hex1, hex2) {
                                const lum1 = this.getLuminance(hex1);
                                const lum2 = this.getLuminance(hex2);
                                const brightest = Math.max(lum1, lum2);
                                const darkest = Math.min(lum1, lum2);
                                return (brightest + 0.05) / (darkest + 0.05);
                            },
                            updateSuggestedTextColor() {
                                if (!this.isValidHex(this.primaryBgColor)) return;
                                const lum = this.getLuminance(this.primaryBgColor);
                                this.suggestedTextColor = lum > 0.5 ? '#000000' : '#ffffff';
                            },
                            updateContrastInfo() {
                                const effectiveTextColor = this.primaryTextColor && this.isValidHex(this.primaryTextColor) ? this.primaryTextColor : this.suggestedTextColor;
                                if (!this.isValidHex(this.primaryBgColor) || !this.isValidHex(effectiveTextColor)) {
                                    this.contrastRatio = 0;
                                    this.contrastLevel = 'Invalid Color(s)';
                                    return;
                                }
                                const ratio = this.getContrast(this.primaryBgColor, effectiveTextColor);
                                this.contrastRatio = ratio.toFixed(2);
                                if (ratio >= 7) this.contrastLevel = 'AAA Normal';
                                else if (ratio >= 4.5) this.contrastLevel = 'AA Normal / AAA Large';
                                else if (ratio >= 3) this.contrastLevel = 'AA Large';
                                else this.contrastLevel = 'Fail';
                            },
                            initColors() {
                                this.updateSuggestedTextColor();
                                this.updateContrastInfo();
                                this.updateBgDisplay();
                                const primaryColorPicker = document.getElementById('primary_color_picker');
                                if (primaryColorPicker) {
                                    if (this.isValidHex(this.primaryBgColor)) {
                                        primaryColorPicker.value = this.primaryBgColor;
                                    } else {
                                        primaryColorPicker.value = '#000000'; // Default if Tailwind class
                                    }
                                }
                                const textColorPicker = document.getElementById('primary_text_color_picker');
                                if (textColorPicker) {
                                    textColorPicker.value = (this.primaryTextColor && this.isValidHex(this.primaryTextColor)) ? this.primaryTextColor : '#FFFFFF';
                                }
                            },
                            updateBgColorFromPicker(event) {
                                this.primaryBgColor = event.target.value;
                                this.updateSuggestedTextColor();
                                this.updateContrastInfo();
                                this.updateBgDisplay();
                            },
                            updateBgColorFromText(event) {
                                let newColor = event.target.value.trim();
                                const isTailwindClass = /^[a-z]+-[0-9]{2,3}$/.test(newColor);

                                if (this.isValidHex(newColor)) {
                                    this.primaryBgColor = newColor;
                                    const picker = document.getElementById('primary_color_picker');
                                    if (picker) picker.value = this.primaryBgColor;
                                } else if (isTailwindClass) {
                                    this.primaryBgColor = newColor;
                                    // When a tailwind class is entered, the color picker cannot reflect it.
                                    // We can disable it or set it to a default color like black.
                                    const picker = document.getElementById('primary_color_picker');
                                    if (picker) picker.value = '#000000'; // Reset picker as it can't show tailwind class
                                } else {
                                    // If the input is neither a valid hex nor a valid tailwind class, revert.
                                    event.target.value = this.primaryBgColor;
                                    return; // Stop further updates if input is invalid
                                }
                                
                                this.updateSuggestedTextColor();
                                this.updateContrastInfo();
                                this.updateBgDisplay();
                            },
                            updateTextColorFromPicker(event) {
                                this.primaryTextColor = event.target.value;
                                this.updateContrastInfo();
                            },
                            updateTextColorFromText(event) {
                                let newColor = event.target.value;
                                if (newColor === '') {
                                    this.primaryTextColor = '';
                                } else {
                                    if (!newColor.startsWith('#')) newColor = '#' + newColor;
                                    if (this.isValidHex(newColor)) {
                                        this.primaryTextColor = newColor;
                                    } else {
                                        event.target.value = this.primaryTextColor;
                                    }
                                }
                                const picker = document.getElementById('primary_text_color_picker');
                                if (picker) {
                                    picker.value = (this.primaryTextColor && this.isValidHex(this.primaryTextColor)) ? this.primaryTextColor : '#FFFFFF';
                                }
                                this.updateContrastInfo();
                            },
                            applySuggestedTextColor() {
                                this.primaryTextColor = this.suggestedTextColor;
                                const picker = document.getElementById('primary_text_color_picker');
                                if (picker) picker.value = this.primaryTextColor;
                                this.updateContrastInfo();
                            },
                            updateBgDisplay() {
                                const displaySpan = document.getElementById('current_color_display');
                                const displaySpanText = document.getElementById('current_color_display_text');
                                if (!displaySpan || !displaySpanText) return;

                                const isTailwindClass = /^[a-z]+-[0-9]{2,3}$/.test(this.primaryBgColor);

                                if (this.isValidHex(this.primaryBgColor)) {
                                    displaySpan.style.backgroundColor = this.primaryBgColor;
                                    const lum = this.getLuminance(this.primaryBgColor);
                                    displaySpan.style.color = lum > 0.5 ? '#000000' : '#FFFFFF';
                                    displaySpanText.textContent = this.primaryBgColor;
                                } else if (isTailwindClass) {
                                    // For tailwind classes, we can't show the color directly.
                                    // We can show the class name in the preview span.
                                    displaySpan.style.backgroundColor = '#f0f0f0'; // A neutral background
                                    displaySpan.style.color = '#000000';
                                    displaySpanText.textContent = this.primaryBgColor;
                                } else {
                                    // Handle invalid input if necessary
                                    displaySpan.style.backgroundColor = '#ffffff';
                                    displaySpan.style.color = '#000000';
                                    displaySpanText.textContent = 'Invalid';
                                }
                            }
                        }" x-init="initColors()">
                            <x-input-label for="primary_color_picker" :value="__('Primary Button Background Color')" />
                            <div class="mt-2 flex items-center space-x-3">
                                <input type="color"
                                       id="primary_color_picker"
                                       class="w-16 h-10 p-0 border-gray-300  rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500    "
                                       :value="primaryBgColor"
                                       @input="updateBgColorFromPicker($event)">
                                <x-text-input
                                    id="primary_color_text"
                                    type="text"
                                    class="block w-48"
                                    x-model="primaryBgColor"
                                    @change="updateBgColorFromText($event)"
                                    placeholder="#RRGGBB or blue-500" />
                                <span id="current_color_display" class="px-3 py-1.5 rounded text-sm font-medium">
                                    <span id="current_color_display_text" x-text="primaryBgColor"></span>
                                </span>
                            </div>
                            <input type="hidden" name="primary_color" :value="primaryBgColor">

                            <p class="mt-3 text-sm text-gray-500 ">
                                Select the primary button background color.
                            </p>
                            <p class="mt-1 text-sm text-gray-500 ">
                                Current active primary background:
                                <span class="font-semibold">{{ $currentPrimaryColor ?? '#3b82f6' }}</span>
                            </p>
                        </div>

                        {{-- Primary Button Text Color --}}
                        <div class="mt-6 pt-6 border-t border-gray-200 ">
                            <x-input-label for="primary_text_color_picker" :value="__('Primary Button Text Color')" />
                             <div class="mt-2 flex items-center space-x-3">
                                <input type="color"
                                       id="primary_text_color_picker"
                                       class="w-16 h-10 p-0 border-gray-300  rounded-md shadow-sm"
                                       :value="primaryTextColor"
                                       @input="updateTextColorFromPicker($event)">
                                <x-text-input
                                    id="primary_text_color_text"
                                    type="text"
                                    class="block w-28"
                                    x-model="primaryTextColor"
                                    @change="updateTextColorFromText($event)"
                                    placeholder="#RRGGBB" />
                                <button type="button" @click="applySuggestedTextColor()" class="px-3 py-1.5 text-xs bg-gray-200  rounded hover:bg-gray-300 ">
                                    Apply Suggested: <span x-text="suggestedTextColor" class="font-mono"></span>
                                </button>
                            </div>
                            <input type="hidden" name="primary_button_text_color" :value="primaryTextColor">

                            <div class="mt-3 p-3 rounded-md" :class="{
                                'bg-green-50  border-green-300 ': parseFloat(contrastRatio) >= 4.5,
                                'bg-yellow-50  border-yellow-300 ': parseFloat(contrastRatio) >= 3 && parseFloat(contrastRatio) < 4.5,
                                'bg-red-50  border-red-300 ': parseFloat(contrastRatio) < 3 || contrastLevel === 'Invalid',
                            }" style="border-width: 1px;">
                                <p class="text-sm font-medium" :class="{
                                    'text-green-700 ': parseFloat(contrastRatio) >= 4.5,
                                    'text-yellow-700 ': parseFloat(contrastRatio) >= 3 && parseFloat(contrastRatio) < 4.5,
                                    'text-red-700 ': parseFloat(contrastRatio) < 3 || contrastLevel === 'Invalid',
                                }">
                                    Contrast Ratio: <strong x-text="contrastRatio"></strong>
                                    (<span x-text="contrastLevel"></span>)
                                </p>
                                <p class="text-xs mt-1" :class="{
                                    'text-green-600 ': parseFloat(contrastRatio) >= 4.5,
                                    'text-yellow-600 ': parseFloat(contrastRatio) >= 3 && parseFloat(contrastRatio) < 4.5,
                                    'text-red-600 ': parseFloat(contrastRatio) < 3 || contrastLevel === 'Invalid',
                                }">
                                    WCAG AA requires 4.5:1 for normal text, 3:1 for large text. AAA requires 7:1 and 4.5:1 respectively.
                                </p>
                                <div class="mt-2 text-xs">
                                    Preview:
                                    <span class="px-2 py-1 rounded" :style="{ backgroundColor: primaryBgColor, color: (primaryTextColor && isValidHex(primaryTextColor)) ? primaryTextColor : suggestedTextColor }">Button Text</span>
                                </div>
                            </div>
    
                            {{-- Live Preview Button --}}
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 ">Live Preview:</label>
                                <button type="button"
                                        class="mt-1 px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2"
                                        :style="{ backgroundColor: primaryBgColor, color: (primaryTextColor && isValidHex(primaryTextColor)) ? primaryTextColor : suggestedTextColor }">
                                    Sample Button
                                </button>
                            </div>
    
                             <p class="mt-1 text-sm text-gray-500 ">
                                Current active primary button text color:
                                <span class="font-semibold">{{ $currentPrimaryButtonTextColor ?: '(auto - defaults to black or white based on background)' }}</span>
                            </p>
                        </div>


                        {{-- Branding Section --}}
                        <div class="mt-8 pt-6 border-t border-gray-200 ">
                            <h3 class="text-lg font-medium text-gray-900  mb-4">{{ __('Branding: Logo & Favicon') }}</h3>

                            {{-- Site Logo Management --}}
                            <div class="mt-6" x-data="{
                                logoPreviewUrl: '{{ $currentLogoUrl ?? '' }}',
                                currentLogoUrl: '{{ $currentLogoUrl ?? '' }}',
                                handleLogoChange(event) {
                                    const file = event.target.files[0];
                                    if (file) {
                                        if (file.size > 2 * 1024 * 1024) { // 2MB
                                            alert('File too large. Max 2MB.');
                                            event.target.value = ''; // Reset file input
                                            this.logoPreviewUrl = this.currentLogoUrl; // Revert to current or default
                                            return;
                                        }
                                        const allowedTypes = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/gif'];
                                        if (!allowedTypes.includes(file.type)) {
                                            alert('Invalid file type. Allowed: SVG, PNG, JPG, GIF.');
                                            event.target.value = ''; // Reset file input
                                            this.logoPreviewUrl = this.currentLogoUrl; // Revert to current or default
                                            return;
                                        }
                                        this.logoPreviewUrl = URL.createObjectURL(file);
                                        document.getElementById('remove_logo_hidden').value = '0';
                                    } else {
                                        this.logoPreviewUrl = this.currentLogoUrl; // Revert if no file selected (e.g., dialog cancelled)
                                    }
                                },
                                removeLogo() {
                                    this.logoPreviewUrl = '{{ $defaultLogoUrl ?? '' }}'; // Or an empty string if no default
                                    document.getElementById('site_logo').value = ''; // Clear file input
                                    document.getElementById('remove_logo_hidden').value = '1';
                                    document.getElementById('logo_alt_text').value = ''; // Clear alt text when logo is removed
                                }
                            }" x-init="logoPreviewUrl = '{{ $currentLogoUrl ?? ($defaultLogoUrl ?? '') }}'">
                                <x-input-label for="site_logo" :value="__('Site Logo')" />
                                <div class="mt-2 flex flex-col sm:flex-row sm:items-start sm:space-x-4">
                                    <div class="shrink-0 mb-4 sm:mb-0">
                                        <img x-show="logoPreviewUrl" :src="logoPreviewUrl" alt="Current/New Site Logo" class="h-16 w-auto object-contain bg-gray-100  p-1 rounded border border-gray-300 " style="max-width: 250px; min-height: 4rem;">
                                        <div x-show="!logoPreviewUrl" class="h-16 w-40 bg-gray-100  rounded border border-gray-300  flex items-center justify-center text-gray-500  text-sm">
                                            {{ __('No Logo') }}
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <input id="site_logo" name="site_logo" type="file" class="block w-full text-sm text-gray-900  border border-gray-300  rounded-lg cursor-pointer bg-gray-50  focus:outline-none focus:border-primary-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50  file:text-primary-700  hover:file:bg-primary-100 "
                                               aria-describedby="site_logo_help"
                                               accept="image/svg+xml,image/png,image/jpeg,image/gif,.svg,.png,.jpg,.jpeg,.gif"
                                               @change="handleLogoChange($event)">
                                        <input type="hidden" name="remove_logo" id="remove_logo_hidden" value="0">
                                        <p class="mt-1 text-xs text-gray-500 " id="site_logo_help">
                                            {{ __('SVG, PNG, JPG, GIF (Max. 2MB). Recommended: 250x60px.') }}
                                        </p>
                                        <button type="button" @click="removeLogo()" x-show="logoPreviewUrl && logoPreviewUrl !== '{{ $defaultLogoUrl ?? '' }}'" class="mt-2 text-sm text-red-600  hover:text-red-800 ">
                                            {{ __('Remove Logo') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <x-input-label for="logo_alt_text" :value="__('Logo Alt Text')" />
                                    <x-text-input id="logo_alt_text" name="logo_alt_text" type="text" class="mt-1 block w-full" :value="old('logo_alt_text', $currentLogoAltText ?? '')" placeholder="{{ __('Descriptive text for the logo') }}" />
                                    <p class="mt-1 text-xs text-gray-500 ">
                                        {{ __('Describe the logo for accessibility and SEO.') }}
                                    </p>
                                </div>
                            </div>

                            {{-- Site Favicon Management --}}
                            <div class="mt-8 pt-6 border-t border-gray-200 " x-data="{
                                faviconPreviewUrl: '{{ $currentFaviconUrl ?? '' }}',
                                currentFaviconUrl: '{{ $currentFaviconUrl ?? '' }}',
                                handleFaviconChange(event) {
                                    const file = event.target.files[0];
                                    if (file) {
                                        const allowedTypes = ['image/vnd.microsoft.icon', 'image/x-icon', 'image/png', 'image/svg+xml'];
                                        // For .ico, type might be empty or application/octet-stream, so check extension too
                                        const isIco = file.name.toLowerCase().endsWith('.ico');
                                        if (!isIco && !allowedTypes.includes(file.type)) {
                                            alert('Invalid file type. Allowed: ICO, PNG, SVG.');
                                            event.target.value = '';
                                            this.faviconPreviewUrl = this.currentFaviconUrl;
                                            return;
                                        }
                                        if (file.size > 100 * 1024) { // 100KB
                                            alert('File too large. Max 100KB for favicon.');
                                            event.target.value = '';
                                            this.faviconPreviewUrl = this.currentFaviconUrl;
                                            return;
                                        }
                                        this.faviconPreviewUrl = URL.createObjectURL(file);
                                        document.getElementById('remove_favicon_hidden').value = '0';
                                    } else {
                                        this.faviconPreviewUrl = this.currentFaviconUrl;
                                    }
                                },
                                removeFavicon() {
                                    this.faviconPreviewUrl = '{{ $defaultFaviconUrl ?? '' }}';
                                    document.getElementById('site_favicon').value = '';
                                    document.getElementById('remove_favicon_hidden').value = '1';
                                }
                            }" x-init="faviconPreviewUrl = '{{ $currentFaviconUrl ?? ($defaultFaviconUrl ?? '') }}'">
                                <x-input-label for="site_favicon" :value="__('Site Favicon')" />
                                <div class="mt-2 flex flex-col sm:flex-row sm:items-start sm:space-x-4">
                                    <div class="shrink-0 mb-4 sm:mb-0">
                                        {{-- Mock browser tab preview --}}
                                        <div class="w-48 h-12 bg-gray-200  rounded-t-md flex items-center px-2 shadow-md border border-gray-300 ">
                                            <img x-show="faviconPreviewUrl" :src="faviconPreviewUrl" alt="Favicon Preview" class="h-4 w-4 mr-2 object-contain">
                                            <div x-show="!faviconPreviewUrl" class="h-4 w-4 mr-2 bg-gray-400  rounded-sm flex items-center justify-center">
                                                {{-- Placeholder for no favicon --}}
                                            </div>
                                            <span class="text-xs text-gray-700  truncate">{{ config('app.name', 'Site Title') }}</span>
                                        </div>
                                        <div class="w-48 h-6 bg-gray-100  rounded-b-md shadow-md border-x border-b border-gray-300  flex items-center px-2">
                                            <span class="text-xs text-gray-500  truncate">example.com</span>
                                        </div>

                                        <div x-show="!faviconPreviewUrl && !currentFaviconUrl" class="mt-1 text-xs text-gray-500 ">
                                            {{ __('No Favicon') }}
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <input id="site_favicon" name="site_favicon" type="file" class="block w-full text-sm text-gray-900  border border-gray-300  rounded-lg cursor-pointer bg-gray-50  focus:outline-none focus:border-primary-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50  file:text-primary-700  hover:file:bg-primary-100 "
                                               aria-describedby="site_favicon_help"
                                               accept="image/vnd.microsoft.icon,image/x-icon,image/png,image/svg+xml,.ico,.png,.svg"
                                               @change="handleFaviconChange($event)">
                                        <input type="hidden" name="remove_favicon" id="remove_favicon_hidden" value="0">
                                        <p class="mt-1 text-xs text-gray-500 " id="site_favicon_help">
                                            {{ __('ICO, PNG, SVG (Max. 100KB). Recommended: 32x32px or SVG.') }}
                                        </p>
                                        <button type="button" @click="removeFavicon()" x-show="faviconPreviewUrl && faviconPreviewUrl !== '{{ $defaultFaviconUrl ?? '' }}'" class="mt-2 text-sm text-red-600  hover:text-red-800 ">
                                            {{ __('Remove Favicon') }}
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm text-gray-500 ">
                                    {{ __('For PNG/SVG, common sizes (16x16, 32x32, apple-touch-icon) may be auto-generated. ICO files should ideally contain multiple sizes.') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8">
                            <x-primary-button>
                                {{ __('Save Settings') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
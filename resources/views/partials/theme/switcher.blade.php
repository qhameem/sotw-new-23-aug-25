@php
    $isFloating = $floating ?? true;
    $additionalClasses = $class ?? '';
@endphp
<div class="theme-switcher {{ $isFloating ? '' : 'theme-switcher--inline' }} {{ $additionalClasses }}" data-theme-switcher>
    <button type="button" class="theme-switcher__button" data-theme-toggle onclick="window.siteTheme.cycle()">
        <svg class="theme-switcher__icon theme-switcher__icon--light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"></path>
        </svg>
        <svg class="theme-switcher__icon theme-switcher__icon--dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"></path>
        </svg>
        <span class="sr-only">Change color theme</span>
    </button>
</div>

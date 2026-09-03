<style>
    :root {
        --theme-dark-navbar: {{ config('theme.dark_navbar_bg_color', '#111827') }};
        --theme-dark-canvas: {{ config('theme.dark_body_bg_color', '#0b1120') }};
        --theme-dark-surface: {{ config('theme.dark_surface_color', '#111827') }};
        --theme-dark-surface-muted: {{ config('theme.dark_muted_surface_color', '#1e293b') }};
        --theme-dark-border: {{ config('theme.dark_border_color', '#334155') }};
        --theme-dark-text: {{ config('theme.dark_font_color', '#f8fafc') }};
        --theme-dark-text-muted: {{ config('theme.dark_body_text_color', '#cbd5e1') }};
        --theme-dark-product-hover: {{ config('theme.dark_product_hover_color', '#1e293b') }};
    }
</style>
<script>
    (() => {
        const storageKey = 'sotw-theme';
        const allowedThemes = ['light', 'dark'];

        const storedTheme = () => {
            try {
                const value = localStorage.getItem(storageKey);
                return allowedThemes.includes(value) ? value : 'light';
            } catch (_) {
                return 'light';
            }
        };

        const applyTheme = (preference, animate = false) => {
            const theme = allowedThemes.includes(preference) ? preference : 'light';
            const isDark = theme === 'dark';
            const root = document.documentElement;

            if (animate) {
                root.classList.add('theme-transitioning');
                window.setTimeout(() => root.classList.remove('theme-transitioning'), 180);
            }

            root.classList.toggle('dark', isDark);
            root.dataset.themePreference = theme;
            root.dataset.theme = isDark ? 'dark' : 'light';

            document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                const labels = { light: 'Light theme', dark: 'Dark theme' };
                button.setAttribute('aria-label', `${labels[theme]}. Activate next theme.`);
                button.setAttribute('title', labels[theme]);
            });
        };

        window.siteTheme = {
            get: storedTheme,
            refresh() {
                applyTheme(storedTheme());
            },
            set(preference) {
                try { localStorage.setItem(storageKey, preference); } catch (_) {}
                applyTheme(preference, true);
            },
            cycle() {
                const current = storedTheme();
                const next = current === 'dark' ? 'light' : 'dark';
                this.set(next);
            },
        };

        applyTheme(storedTheme());
        window.addEventListener('storage', (event) => {
            if (event.key === storageKey) applyTheme(storedTheme(), true);
        });
        document.addEventListener('DOMContentLoaded', () => applyTheme(storedTheme()));
        document.addEventListener('livewire:navigated', () => applyTheme(storedTheme()));
    })();
</script>

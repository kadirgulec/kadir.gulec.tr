/**
 * Admin panel behavior. Alpine comes with Livewire, so this file only adds
 * the theme switch and small helpers the components share.
 * The initial theme is applied by an inline script in the <head>.
 */

const THEME_KEY = 'theme';

function storeTheme(theme) {
    try {
        if (theme === 'system') {
            localStorage.removeItem(THEME_KEY);
        } else {
            localStorage.setItem(THEME_KEY, theme);
        }
    } catch {
        // Storage can be blocked (private mode); the switch still works for this page.
    }
}

function storedTheme() {
    try {
        return localStorage.getItem(THEME_KEY) ?? 'system';
    } catch {
        return 'system';
    }
}

function applyTheme(theme) {
    const isDark = theme === 'system' ? matchMedia('(prefers-color-scheme: dark)').matches : theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);
}

document.addEventListener('alpine:init', () => {
    // Same "theme" key as the notebook, so both sides stay in the same mode.
    window.Alpine.data('themeSwitch', () => ({
        theme: storedTheme(),
        set(theme) {
            this.theme = theme;
            storeTheme(theme);
            applyTheme(theme);
        },
    }));
});

matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => applyTheme(storedTheme()));

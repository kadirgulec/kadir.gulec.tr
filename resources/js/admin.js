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

document.addEventListener('alpine:init', () => {
    /**
     * Multi-select with free entry (technologies, tags). The selection is a
     * list of names, entangled with a Livewire property; unknown names are
     * created when the form is saved.
     */
    window.Alpine.data('combobox', ({ selected, options, allowCreate = true }) => ({
        selected,
        options,
        allowCreate,
        query: '',
        open: false,
        active: 0,
        get matches() {
            const query = this.query.trim().toLocaleLowerCase('tr');
            const free = this.options.filter((option) => !this.isSelected(option));

            return query === '' ? free.slice(0, 50) : free.filter((option) => option.toLocaleLowerCase('tr').includes(query)).slice(0, 50);
        },
        get canCreate() {
            const query = this.query.trim();

            return this.allowCreate && query !== '' && ![...this.options, ...this.selected].some((option) => option.toLocaleLowerCase('tr') === query.toLocaleLowerCase('tr'));
        },
        isSelected(option) {
            return this.selected.some((item) => item.toLocaleLowerCase('tr') === option.toLocaleLowerCase('tr'));
        },
        add(option) {
            const value = option.trim();

            if (value !== '' && !this.isSelected(value)) {
                this.selected = [...this.selected, value];
            }

            this.query = '';
            this.active = 0;
            this.$refs.input.focus();
        },
        remove(option) {
            this.selected = this.selected.filter((item) => item !== option);
        },
        enter() {
            if (this.matches[this.active] !== undefined && this.query.trim() !== '' && !this.canCreate) {
                this.add(this.matches[this.active]);
            } else if (this.canCreate) {
                this.add(this.query);
            } else if (this.matches[this.active] !== undefined) {
                this.add(this.matches[this.active]);
            }
        },
        backspace() {
            if (this.query === '' && this.selected.length > 0) {
                this.selected = this.selected.slice(0, -1);
            }
        },
        move(step) {
            const count = this.matches.length + (this.canCreate ? 1 : 0);
            this.open = true;
            this.active = count === 0 ? 0 : (this.active + step + count) % count;
        },
    }));

    /**
     * Markdown editor: toolbar actions around the selection and a preview tab
     * that renders the text with the site's stylesheet inside an iframe.
     */
    window.Alpine.data('markdownEditor', ({ previewUrl, section }) => ({
        tab: 'write',
        previewHtml: '',
        loading: false,
        textarea() {
            return this.$refs.editorArea;
        },
        wrap(before, after = before, placeholder = '') {
            const area = this.textarea();
            const { selectionStart: start, selectionEnd: end, value } = area;
            const text = value.slice(start, end) || placeholder;

            area.setRangeText(before + text + after, start, end, 'end');
            area.selectionStart = start + before.length;
            area.selectionEnd = start + before.length + text.length;
            area.dispatchEvent(new Event('input', { bubbles: true }));
            area.focus();
        },
        line(prefix) {
            const area = this.textarea();
            const start = area.value.lastIndexOf('\n', area.selectionStart - 1) + 1;

            area.setRangeText(prefix, start, start, 'end');
            area.dispatchEvent(new Event('input', { bubbles: true }));
            area.focus();
        },
        block(opening, closing, placeholder) {
            const area = this.textarea();
            const { selectionStart: start, selectionEnd: end, value } = area;
            const text = value.slice(start, end) || placeholder;
            const lead = start > 0 && value[start - 1] !== '\n' ? '\n\n' : '';

            area.setRangeText(`${lead}${opening}\n${text}\n${closing}\n`, start, end, 'end');
            area.dispatchEvent(new Event('input', { bubbles: true }));
            area.focus();
        },
        sidenote() {
            const area = this.textarea();
            const numbers = [...area.value.matchAll(/\[\^(\d+)\]/g)].map((match) => Number(match[1]));
            const next = numbers.length ? Math.max(...numbers) + 1 : 1;

            area.setRangeText(`[^${next}]`, area.selectionEnd, area.selectionEnd, 'end');
            area.value = area.value.replace(/\s*$/, '') + `\n\n[^${next}]: Kenar notu`;
            area.dispatchEvent(new Event('input', { bubbles: true }));
            area.focus();
        },
        async preview() {
            this.tab = 'preview';
            this.loading = true;

            try {
                const response = await fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'text/html',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        markdown: this.textarea().value,
                        section,
                        dark: document.documentElement.classList.contains('dark'),
                    }),
                });

                this.previewHtml = await response.text();
            } catch {
                this.previewHtml = '<p style="font-family: sans-serif; padding: 1rem">Önizleme yüklenemedi.</p>';
            } finally {
                this.loading = false;
            }
        },
    }));
});

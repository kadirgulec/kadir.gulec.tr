import './pwa.js';

/**
 * Public site behavior: the desk lamp theme switch and hand-drawn strokes.
 * The initial theme is applied by an inline script in the <head> to avoid a flash.
 */

const THEME_KEY = 'theme';
const LAMP_DURATION = 380;
const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function applyTheme(isDark) {
    document.documentElement.classList.toggle('dark', isDark);

    document.querySelectorAll('[data-lamp]').forEach((lamp) => {
        lamp.setAttribute('aria-pressed', String(!isDark));
        lamp.setAttribute('aria-label', isDark ? 'Lambayı aç (aydınlık mod)' : 'Lambayı kapat (karanlık mod)');
    });
}

function storeTheme(isDark) {
    try {
        localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
    } catch {
        // Storage can be blocked (private mode); the switch still works for this page.
    }
}

/**
 * Turning the lamp on spreads the light out of the lamp head,
 * turning it off folds the light back into it.
 */
function switchTheme(lamp) {
    const turnOff = !document.documentElement.classList.contains('dark');

    storeTheme(turnOff);

    if (!document.startViewTransition || prefersReducedMotion()) {
        applyTheme(turnOff);

        return;
    }

    const rect = lamp.getBoundingClientRect();
    const x = rect.left + rect.width / 2;
    const y = rect.top + rect.height / 2;
    const radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
    const circles = [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`];

    const root = document.documentElement;
    root.classList.add('theme-switching');
    root.classList.toggle('lamp-off', turnOff);

    const transition = document.startViewTransition(() => applyTheme(turnOff));

    transition.ready.then(() => {
        root.animate(
            { clipPath: turnOff ? [...circles].reverse() : circles },
            {
                duration: LAMP_DURATION,
                easing: 'cubic-bezier(.4, 0, .2, 1)',
                pseudoElement: turnOff ? '::view-transition-old(root)' : '::view-transition-new(root)',
            },
        );
    });

    transition.finished.finally(() => root.classList.remove('theme-switching', 'lamp-off'));
}

function initLamps() {
    applyTheme(document.documentElement.classList.contains('dark'));

    document.querySelectorAll('[data-lamp]').forEach((lamp) => {
        lamp.addEventListener('click', () => switchTheme(lamp));
    });

    // Follow the system setting until the visitor picks a side.
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
        let stored = null;

        try {
            stored = localStorage.getItem(THEME_KEY);
        } catch {
            // Ignore blocked storage.
        }

        if (stored === null) {
            applyTheme(event.matches);
        }
    });
}

/**
 * `.draw` strokes, `.reveal` groups and `.press` stamps animate once, the first time they scroll into view.
 */
function initDrawings() {
    const drawings = document.querySelectorAll('.draw, .reveal, .press');

    if (!('IntersectionObserver' in window)) {
        drawings.forEach((drawing) => drawing.classList.add('is-drawn'));

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-drawn');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.6 },
    );

    drawings.forEach((drawing) => observer.observe(drawing));
}

/**
 * Mobile: the small "kg" stamp shows up in the corner while the signature at the top is out of view.
 */
function initHomeStamp() {
    const stamp = document.querySelector('[data-home-stamp]');
    const signature = document.querySelector('[data-signature]');

    if (!stamp || !signature || !('IntersectionObserver' in window)) {
        return;
    }

    new IntersectionObserver(([entry]) => stamp.classList.toggle('is-shown', !entry.isIntersecting)).observe(signature);
}

/**
 * Spoilers stay under the marker until the reader asks for them.
 */
function initSpoilers() {
    document.querySelectorAll('[data-spoiler]').forEach((spoiler) => {
        const text = spoiler.querySelector('.spoiler-text');
        const toggle = spoiler.querySelector('[data-spoiler-toggle]');

        text?.setAttribute('aria-hidden', 'true');

        toggle?.addEventListener('click', () => {
            spoiler.classList.add('is-revealed');
            text?.removeAttribute('aria-hidden');
            text?.setAttribute('tabindex', '-1');
            text?.focus({ preventScroll: true });
            toggle.remove();
        });
    });
}

/**
 * Copy buttons on code blocks.
 */
function initCopyButtons() {
    document.querySelectorAll('[data-code-block]').forEach((block) => {
        const button = block.querySelector('[data-copy]');
        const code = block.querySelector('code');

        if (!button || !code || !navigator.clipboard) {
            button?.remove();

            return;
        }

        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(code.textContent);
                button.textContent = 'kopyalandı ✓';
            } catch {
                button.textContent = 'kopyalanamadı';
            }

            setTimeout(() => (button.textContent = 'kopyala'), 1800);
        });
    });
}

/**
 * Share icons: the device's share sheet where there is one (phones, the installed app), otherwise the link is copied.
 */
function initShareButtons() {
    document.querySelectorAll('[data-share]').forEach((share) => {
        const button = share.querySelector('[data-share-button]');
        const status = share.querySelector('[data-share-status]');
        const url = share.dataset.shareUrl;

        if (!button || !url || (!navigator.share && !navigator.clipboard)) {
            share.remove();

            return;
        }

        // Without a share sheet (most desktops) the button only copies, so it shows the link icon.
        if (!navigator.share) {
            share.querySelector('[data-share-icon]')?.setAttribute('hidden', '');
            share.querySelector('[data-copy-icon]')?.removeAttribute('hidden');
            button.title = 'Bağlantıyı kopyala';

            if (button.hasAttribute('aria-label')) {
                button.setAttribute('aria-label', 'Bu sayfanın bağlantısını kopyala');
            }
        }

        let resetStatus;
        const say = (message) => {
            status.textContent = message;
            clearTimeout(resetStatus);
            resetStatus = setTimeout(() => (status.textContent = ''), 1800);
        };

        button.addEventListener('click', async () => {
            if (navigator.share) {
                try {
                    await navigator.share({ title: document.title, url });

                    return;
                } catch (error) {
                    // Closing the share sheet is not a failure; anything else falls back to copying.
                    if (error.name === 'AbortError') {
                        return;
                    }
                }
            }

            try {
                await navigator.clipboard.writeText(url);
                say('bağlantı kopyalandı ✓');
            } catch {
                say('kopyalanamadı');
            }
        });
    });
}

/**
 * Wide scrollers (the yearly chain grid) start scrolled to the end, where the newest days are.
 */
function initScrollToEnd() {
    document.querySelectorAll('[data-scroll-end]').forEach((scroller) => {
        scroller.scrollLeft = scroller.scrollWidth;
    });
}

initLamps();
initDrawings();
initHomeStamp();
initSpoilers();
initCopyButtons();
initShareButtons();
initScrollToEnd();

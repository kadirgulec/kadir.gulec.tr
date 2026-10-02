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
 * Strokes inside `.draw` elements are drawn once, the first time they scroll into view.
 */
function initDrawings() {
    const drawings = document.querySelectorAll('.draw');

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

initLamps();
initDrawings();

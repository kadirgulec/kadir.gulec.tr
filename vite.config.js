import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // Public site. Turkish needs the latin-ext subset (ğ, ş, İ).
                // Above-the-fold variants are preloaded. Headings and handwriting use "block"
                // so the very different fallback never flashes; body text keeps "fallback"
                // so it stays readable on slow connections.
                bunny('Fraunces', {
                    weights: [400, 600, 800],
                    styles: ['normal', 'italic'],
                    subsets: ['latin', 'latin-ext'],
                    display: 'block',
                    preload: [{ weight: 600 }, { weight: 800 }],
                }),
                bunny('Nunito Sans', {
                    weights: [400, 600, 700],
                    styles: ['normal', 'italic'],
                    subsets: ['latin', 'latin-ext'],
                    display: 'fallback',
                    preload: [{ weight: 400 }],
                }),
                bunny('Caveat', {
                    weights: [500, 700],
                    subsets: ['latin', 'latin-ext'],
                    display: 'block',
                    preload: [{ weight: 700 }],
                }),
                bunny('JetBrains Mono', {
                    weights: [400, 600],
                    subsets: ['latin', 'latin-ext'],
                    display: 'fallback',
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});

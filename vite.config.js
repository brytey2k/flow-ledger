import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Geist', {
                    weights: [300, 400, 500, 600, 700],
                    preload: [{ weight: 400 }, { weight: 600 }],
                }),
                bunny('Geist Mono', {
                    weights: [400, 500, 600],
                }),
                // The marketing page's headings (resources/css/landing.css); its body text is Geist, like the app.
                bunny('Sora', {
                    weights: [500, 600],
                    preload: [{ weight: 600 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

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
                }),
                bunny('Geist Mono', {
                    weights: [400, 500, 600],
                }),
                // Landing page (resources/css/landing.css) keeps its own Coral-theme
                // typography, independent of the authenticated app's Steward Teal system.
                bunny('Sora', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Instrument Sans', {
                    weights: [400, 500, 600, 700],
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

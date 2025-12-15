import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import statamic from '@statamic/cms/vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        statamic(),
        tailwindcss(),
        laravel({
            valetTls: 'statamic-6-advanced-seo.test',
            input: ['resources/js/cp.js', 'resources/css/cp.css'],
            publicDirectory: 'resources/dist',
        }),
    ],
});

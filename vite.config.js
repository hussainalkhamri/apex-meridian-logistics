import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    resolve: {
        // The maritime router requires TinyQueue's CommonJS constructor, not its ESM namespace.
        alias: [{ find: /^tinyqueue$/, replacement: fileURLToPath(new URL('./node_modules/tinyqueue/tinyqueue.js', import.meta.url)) }],
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

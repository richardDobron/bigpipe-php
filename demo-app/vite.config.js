import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // One entrypoint, which imports the CSS: the build is a single classic script, see below.
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // The CSS as a stylesheet of its own, not injected by the script.
        cssCodeSplit: false,
        rollupOptions: {
            // A classic script, not a module: loaded in the <head>, it runs before the inline scripts of
            // BigPipe, so every pagelet is shown as soon as it arrives. The dev server only serves
            // modules, which run once the page is parsed: the inline scripts queue their calls until then.
            output: {
                format: 'iife',
                inlineDynamicImports: true,
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

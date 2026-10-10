import { fileURLToPath } from 'node:url';
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
    resolve: {
        // modal-vanilla, which dialogs use, imports the Node.js module `events`.
        alias: { events: fileURLToPath(new URL('./node_modules/events/events.js', import.meta.url)) },
    },
    build: {
        // The CSS as a stylesheet of its own, not injected by the script.
        cssCodeSplit: false,
        rollupOptions: {
            // A classic script, not a module: loaded in the <head>, it defines `require` before the
            // inline scripts of BigPipe run, so every pagelet is shown as soon as it arrives. The dev
            // server only serves modules; there, the scripts of BigPipe are module scripts too.
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

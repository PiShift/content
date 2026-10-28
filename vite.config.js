import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

/**
 * Builds the prebuilt admin into dist/ (with a Laravel-style manifest), for projects that
 * don't build the admin with their own Vite. Projects publish it with
 * `php artisan vendor:publish --tag=content-assets`.
 */
export default defineConfig({
    root: import.meta.dirname,
    base: '/vendor/content/',
    plugins: [react(), tailwindcss()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: 'resources/admin/standalone.tsx',
        },
    },
});

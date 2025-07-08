import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        // Process CSS to create a single bundled file
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                // Remove additionalData that's causing issues
            },
        },
        // Optimize CSS output for production
        build: {
            cssCodeSplit: false, // Don't split CSS into multiple files
            minify: 'terser',
            sourcemap: false,
            cssMinify: true,
        },
    },
    build: {
        outDir: 'public/build',
        assetsDir: '',
        manifest: true,
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
});

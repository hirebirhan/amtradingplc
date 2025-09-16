import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

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
        devSourcemap: true,
        preprocessorOptions: {
            scss: {},
        },
    },
    server: {
        host: true,
        port: 5173,
        strictPort: true,
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: ['**/.env*'],
        },
    },
    optimizeDeps: {
        // Prevent auto entry detection warning when using Laravel plugin without HTML entry
        entries: [],
    },
})

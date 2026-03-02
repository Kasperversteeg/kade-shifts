import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    server: {
        host: "0.0.0.0",
        port: 5179,
        strictPort: true,
        https: false,
        hmr: {
            protocol: "wss",
            host: "kade-shifts.ddev.site",
            clientPort: 5179,
        },
        cors: {
            origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico', 'apple-touch-icon.png'],
            manifest: {
                name: 'Kade Shifts',
                short_name: 'Shifts',
                description: 'Track your work hours easily',
                theme_color: '#4d5930',
                background_color: '#f5f2e3',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                navigateFallback: '/',
                globPatterns: ['**/*.{js,css,ico,png,svg}'],
            },
        }),
    ],
});

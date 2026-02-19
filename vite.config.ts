import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            command: 'ddev php artisan wayfinder:generate',
        }),
    ],
    resolve: {
        alias: {
            '@/actions': resolve(__dirname, 'resources/js/wayfinder'),
            '@/routes': resolve(__dirname, 'resources/js/wayfinder/routes'),
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});

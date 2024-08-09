import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                'resources/brainframe/css/style.scss', 'resources/brainframe/js/app.js'],
            refresh: true,
        }),
    ],
});
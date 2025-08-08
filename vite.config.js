import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => tag.startsWith('l-')
                }
            }
        }),
        laravel({
            input: [
                'resources/brainframe/css/style.scss',
                'resources/brainframe/js/app.js'
            ],
            refresh: true,
        }),
    ],
    // Dieser Block ist neu und entscheidend für Docker!
    server: {
        host: '0.0.0.0', // Sorgt dafür, dass Vite auf allen Netzwerk-Interfaces im Container lauscht
        port: 5173,      // Der Port, den wir in docker-compose.yml mappen
        hmr: {
            host: 'localhost', // Sagt dem Browser, wo er den HMR-Server findet
        }
    }
});
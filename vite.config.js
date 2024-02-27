import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/assets/sass/app.scss',
                'resources/assets/sass/custom/custom.scss',
                'resources/assets/sass/home.scss',
                'resources/assets/sass/theme/theme.scss',
                'resources/assets/js/app.js',
            ],
            refresh: true,
        }),
    ],
});

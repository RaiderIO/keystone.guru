import {defineConfig} from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/assets/js/test/setup.js'],
        include: ['resources/assets/js/**/*.test.{js,ts}', 'scripts/build/**/*.test.mjs'],
    },
});

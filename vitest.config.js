import {defineConfig} from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        include: ['resources/assets/js/**/*.test.{js,ts}'],
    },
});

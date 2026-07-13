import {defineConfig} from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        // Restore `vi.spyOn` originals before each test. The jsdom environment (and thus globals
        // like `history`) is shared across tests in a file; since Vitest 4 no longer auto-restores
        // spies between tests, re-spying a shared global would otherwise accumulate call history
        // from earlier tests (e.g. menuitemsanchor's `history.replaceState` count).
        restoreMocks: true,
        setupFiles: ['resources/assets/js/test/setup.js'],
        include: ['resources/assets/js/**/*.test.{js,ts}', 'scripts/build/**/*.test.mjs'],
    },
});

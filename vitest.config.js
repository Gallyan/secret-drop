import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'happy-dom',
        include: ['tests/js/**/*.test.js'],
        restoreMocks: true,
        coverage: {
            provider: 'v8',
            include: ['resources/js/**/*.js'],
        },
    },
});

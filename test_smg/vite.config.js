import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/game.js',
                'resources/js/character.js',
                'resources/js/dice.js',
                'resources/js/gameState.js',
                'resources/js/ui.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});

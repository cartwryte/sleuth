/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
    server: {
        writeHotFile: resolve(__dirname, 'src/Frontend/dist/hot'),
    },
    build: {
        manifest: true,
        outDir: 'src/Frontend/dist',
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'src/Frontend/js/luminary.js'),
            name: 'Luminary',
            fileName: 'luminary',
            formats: ['iife'],
        },
        rollupOptions: {
            output: {
                assetFileNames: '[name].[ext]',
            }
        }
    }
})

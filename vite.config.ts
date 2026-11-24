import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: [
        'resources/js/**',
        'resources/css/**',
        'resources/views/**',
        'routes/**',
        'app/Http/Controllers/**',
      ],
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    tailwindcss(),
    // Wayfinder: auto-generate typed routes on each build
    {
      name: 'wayfinder-generate',
      buildStart() {
        const { execSync } = require('child_process')
        try {
          execSync('php8.3 artisan wayfinder:generate', { stdio: 'ignore' })
        } catch {
          try { execSync('php artisan wayfinder:generate', { stdio: 'ignore' }) } catch {}
        }
      },
    },
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
})

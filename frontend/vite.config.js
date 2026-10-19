import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.svg', 'apple-touch-icon.png'],
      manifest: {
        name: 'CondoFlow',
        short_name: 'CondoFlow',
        description: 'Gestione condominiale semplice e moderna',
        theme_color: '#4f46e5',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        scope: '/',
        icons: [
          { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
          { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
          { src: '/icons/icon-512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        // The API responses aren't part of the precached app shell — they're
        // handled by this separate NetworkFirst runtime route instead, so a
        // recent GET still resolves offline/on a flaky connection.
        navigateFallbackDenylist: [/^\/api/, /^\/sanctum/, /^\/storage/],
        runtimeCaching: [
          {
            urlPattern: /^\/api\//,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              networkTimeoutSeconds: 6,
              expiration: { maxEntries: 100, maxAgeSeconds: 60 * 60 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
        ],
      },
      devOptions: {
        enabled: false,
        type: 'module',
      },
    }),
  ],
  server: {
    port: 5173,
    proxy: {
      '/api': { target: 'http://localhost:8000', changeOrigin: true },
      '/sanctum': { target: 'http://localhost:8000', changeOrigin: true },
      '/storage': { target: 'http://localhost:8000', changeOrigin: true },
    },
  },
  // `vite preview` serves the production build (needed to test the service
  // worker/PWA locally, since it's disabled in `vite dev`) — same proxy as
  // the dev server so it can talk to the local backend too.
  preview: {
    port: 5173,
    proxy: {
      '/api': { target: 'http://localhost:8000', changeOrigin: true },
      '/sanctum': { target: 'http://localhost:8000', changeOrigin: true },
      '/storage': { target: 'http://localhost:8000', changeOrigin: true },
    },
  },
})

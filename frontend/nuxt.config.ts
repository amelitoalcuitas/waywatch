// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  ssr: false,
  modules: [
    '@pinia/nuxt',
    '@nuxt/ui',
    '@nuxtjs/leaflet',
    '@tailwindcss/postcss'
  ],
  css: ['~/assets/css/main.css'],
  leaflet: {
    markerCluster: true
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost/api'
    }
  },
  vite: {
    server: {
      hmr: true,
      watch: {
        usePolling: true
      }
    }
  }
});

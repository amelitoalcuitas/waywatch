// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  ssr: false,
  app: {
    head: {
      title: 'WayWatch',
      meta: [{ name: 'description', content: 'WayWatch' }]
    }
  },
  modules: ['@pinia/nuxt', '@nuxt/ui', '@tailwindcss/postcss'],
  css: ['~/assets/css/main.css', 'maplibre-gl/dist/maplibre-gl.css'],
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
    },
    optimizeDeps: {
      include: ['maplibre-gl']
    }
  },

  ui: {
    colorMode: false
  }
});

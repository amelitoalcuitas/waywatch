// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  ssr: false,
  app: {
    head: {
      title: 'WayWatch',
      meta: [
        {
          name: 'viewport',
          content:
            'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no'
        }
      ]
    }
  },
  modules: ['@pinia/nuxt', '@nuxt/ui', '@tailwindcss/postcss'],
  css: ['~/assets/css/main.css', 'maplibre-gl/dist/maplibre-gl.css'],
  runtimeConfig: {
    public: {
      apiBase:
        import.meta.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8080/api'
    }
  },
  vite: {
    server: {
      hmr: true,
      watch: {
        usePolling: true
      },
      proxy: {
        '/api': {
          target: 'http://localhost:8080',
          changeOrigin: true
        },
        '/storage': {
          target: 'http://localhost:8080',
          changeOrigin: true
        }
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

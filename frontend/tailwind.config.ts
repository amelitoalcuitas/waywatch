import type { Config } from 'tailwindcss'

export default {
  content: ['./app/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        primary: '#059212',
      },
    },
  },
  plugins: [],
} satisfies Config

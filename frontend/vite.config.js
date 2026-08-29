import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'
import path from 'path'

export default defineConfig({
  plugins: [
    vue({
      template: { transformAssetUrls }
    }),
    quasar({
      sassVariables: 'src/css/quasar.variables.scss'
    })
  ],
  resolve: {
    alias: {
      'src': path.resolve(__dirname, './src'),
      'boot': path.resolve(__dirname, './src/boot'),
      'components': path.resolve(__dirname, './src/components'),
      'layouts': path.resolve(__dirname, './src/layouts'),
      'pages': path.resolve(__dirname, './src/pages'),
      'stores': path.resolve(__dirname, './src/stores'),
    }
  },
  server: {
    port: 9000,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      }
    }
  }
})

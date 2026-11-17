import { fileURLToPath, URL } from 'node:url'
import Vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import Vuetify, { transformAssetUrls } from 'vite-plugin-vuetify'
import VueRouter from 'vue-router/vite'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    VueRouter({ dts: 'src/typed-router.d.js' }), Vue({
      template: { transformAssetUrls },
    }), // https://github.com/vuetifyjs/vuetify-loader/tree/master/packages/vite-plugin#readme
    Vuetify({
      autoImport: true,
      styles: {
        configFile: 'src/styles/settings.scss',
      },
    })],
  define: { 'process.env': {} },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('src', import.meta.url)),
    },
    extensions: [
      '.js',
      '.json',
      '.jsx',
      '.mjs',
      '.ts',
      '.tsx',
      '.vue',
    ],
  },
  base: '/admin/p/perm/app/',
  server: {
    port: 3000,
    proxy: {
      '/admin/p/perm/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: '../admin/app',
    emptyOutDir: true,
    rolldownOptions: {
      output: {
        assetFileNames: 'assets/min-[name]-[hash][extname]',
        chunkFileNames: 'assets/min-[name]-[hash].js',
      },
    },
  },
})

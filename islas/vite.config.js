import { defineConfig } from 'vite';

// Salida estática dentro del tema: el servidor no necesita Node para servir las islas.
export default defineConfig({
  publicDir: false,
  build: {
    outDir: '../assets/js/islas',
    emptyOutDir: true,
    target: 'es2020',
    minify: 'esbuild',
    rollupOptions: {
      input: { cargador: 'src/cargador.js' },
      output: {
        format: 'es',
        entryFileNames: '[name].js',
        chunkFileNames: '[name]-[hash].js',
      },
    },
  },
});

import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    manifest: true,
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: 'resources/js/main.js',
    },
  },
});


/*
npm run dev
APP_ENV=dev php -S localhost:8000 -t public
*/
import { defineConfig } from "vite";

export default defineConfig({
    publicDir: false,
    build: {
        manifest: true,
        outDir: "public/build",
        emptyOutDir: true,
        rollupOptions: {
            input: "resources/js/main.js",
        },
    },
});

/*
npm run dev
APP_ENV=dev php -S localhost:8000 -t public
*/

/*
for img in *.jpg *.jpeg *.png; do
  base="${img%.*}"

  # Mobile (600px)
  convert "$img" \
    -resize 600x \
    -strip \
    -define webp:method=6 \
    -define webp:auto-filter=true \
    -quality 82 \
    "${base}-600.webp"

  # Desktop (1200px)
  convert "$img" \
    -resize 1200x \
    -strip \
    -define webp:method=6 \
    -define webp:auto-filter=true \
    -quality 82 \
    "${base}-1200.webp"
done
*/

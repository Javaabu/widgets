import { defineConfig } from "vite"

/**
 * Builds the prebuilt assets in dist/ for apps that don't build javaabu/widgets
 * with their own Vite. javaabu/manifest finds them through dist/.vite/manifest.json,
 * keyed by these entry paths. dist/ is committed; CI fails when it's out of date.
 *
 * The React components and pages aren't prebuilt: apps build them from resources/js.
 */
export const entries = ["resources/js/blade/admin.ts", "resources/css/widgets.css"]

export default defineConfig({
  // Relative URLs, as javaabu/manifest serves dist/ from a sub-path.
  base: "./",
  publicDir: false,
  build: {
    outDir: "dist",
    emptyOutDir: true,
    manifest: true,
    target: "es2020",
    sourcemap: false,
    reportCompressedSize: false,
    rollupOptions: {
      input: entries,
    },
  },
})

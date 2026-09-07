import { defineConfig } from 'vite'
import path from 'node:path'

/**
 * Build for the MelisCms React brick.
 *
 * Produces a single IIFE bundle (public/ui-react/brick.js) loaded at runtime by the
 * MelisCore React shell when the module is active. React / ReactRouter are EXTERNAL,
 * mapped to the host globals exposed in MelisCore's main.tsx — so the brick reuses the
 * host React instance (hooks, context, Router all work across the boundary).
 */
export default defineConfig({
  esbuild: { jsx: 'automatic' },
  // Keep the on-disk (symlink) path as a module's identity instead of following it to its real
  // location. A per-module plugin-config source (e.g. melis-cache-internal, installed as a Composer
  // `path` symlink) imports the kit via a relative `../../../melis-cms/…` path that is only correct
  // from the vendor tree — following the symlink to local-modules/ would break that resolution.
  resolve: { preserveSymlinks: true },
  build: {
    outDir: path.resolve(import.meta.dirname, '..', 'public', 'ui-react'),
    emptyOutDir: false,
    lib: {
      entry: path.resolve(import.meta.dirname, 'src/brick.tsx'),
      formats: ['iife'],
      name: 'MelisCmsBrick',
      fileName: () => 'brick.js',
    },
    rollupOptions: {
      external: ['react', 'react-dom', 'react/jsx-runtime', 'react-router-dom'],
      output: {
        globals: {
          react: 'MelisReact',
          'react-dom': 'MelisReactDOM',
          'react/jsx-runtime': 'MelisReactJsxRuntime',
          'react-router-dom': 'MelisReactRouterDOM',
        },
      },
    },
  },
})

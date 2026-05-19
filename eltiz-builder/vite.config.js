import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig( {
	plugins: [ react() ],

	build: {
		outDir: 'assets',
		emptyOutDir: false,
		rollupOptions: {
			input: {
				editor: resolve( __dirname, 'editor/src/index.jsx' ),
			},
			output: {
				entryFileNames: 'js/[name].min.js',
				chunkFileNames: 'js/[name]-[hash].js',
				assetFileNames: asset => {
					if ( asset.name?.endsWith( '.css' ) ) return 'css/editor.min.css';
					return 'assets/[name][extname]';
				},
			},
			external: [
				'@wordpress/element',
				'@wordpress/i18n',
				'@wordpress/api-fetch',
			],
			globals: {
				'@wordpress/element':    'wp.element',
				'@wordpress/i18n':       'wp.i18n',
				'@wordpress/api-fetch':  'wp.apiFetch',
			},
		},
		minify: 'esbuild',
		sourcemap: false,
	},

	resolve: {
		alias: {
			'@': resolve( __dirname, 'editor/src' ),
		},
	},
} );

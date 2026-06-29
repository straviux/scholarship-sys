import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';

export default defineConfig(({ mode }) => {
	const env = loadEnv(mode, process.cwd(), '');
	const hmrHost = env.VITE_HMR_HOST || 'localhost';

	return {
		server: {
			host: '0.0.0.0',
			port: 5173,
			hmr: {
				host: hmrHost,
			},
		},
		optimizeDeps: {
			include: ['primevue/divider', 'primevue/paginator'],
		},
		build: {
			ssrManifest: false,
			modulePreload: { polyfill: true },
			rollupOptions: {
				output: {
					manualChunks(id) {
						if (id.includes('node_modules/chart.js') || id.includes('node_modules/chartjs-')) {
							return 'vendor-charts';
						}

						if (id.includes('node_modules/xlsx')) {
							return 'vendor-xlsx';
						}

						if (
							id.includes('node_modules/quill') ||
							id.includes('node_modules/@vueup/vue-quill') ||
							id.includes('node_modules/md-editor-v3') ||
							id.includes('node_modules/marked') ||
							id.includes('node_modules/markdown-it') ||
							id.includes('node_modules/dompurify')
						) {
							return 'vendor-editor';
						}
					},
				},
			},
			chunkSizeWarningLimit: 2500,
			minify: 'terser',
			terserOptions: {
				compress: {
					drop_console: true,
					drop_debugger: true,
				},
			},
		},
		plugins: [
			laravel({
				input: ['resources/js/app.js', 'resources/css/mobile.css'],
				refresh: true,
			}),
			vue({
				template: {
					transformAssetUrls: {
						base: null,
						includeAbsolute: false,
					},
				},
			}),
			Components({
				resolvers: [PrimeVueResolver()],
			}),
			tailwindcss(),
		],
	};
});

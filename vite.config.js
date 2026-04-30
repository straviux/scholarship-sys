import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import os from 'os';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';

function getLocalIP() {
	const interfaces = os.networkInterfaces();
	for (const name of Object.keys(interfaces)) {
		for (const iface of interfaces[name]) {
			if (iface.family === 'IPv4' && !iface.internal) {
				return iface.address;
			}
		}
	}
	return 'localhost';
}

const localIP = getLocalIP();

export default defineConfig({
	server: {
		host: '0.0.0.0', // bind to all interfaces
		port: 5173,
		hmr: {
			host: localIP,
		},
	},
	build: {
		ssrManifest: false,
		modulePreload: { polyfill: true },
		rollupOptions: {
			output: {
				manualChunks(id) {
					// Charts — only used on dashboard/reports pages
					if (id.includes('node_modules/chart.js') || id.includes('node_modules/chartjs-')) {
						return 'vendor-charts';
					}
					// Excel export — only loaded on demand
					if (id.includes('node_modules/xlsx')) {
						return 'vendor-xlsx';
					}
					// Rich text / markdown tooling is only used by a subset of pages.
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
});

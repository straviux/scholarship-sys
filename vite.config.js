import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

import os from 'os';

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
	plugins: [
		laravel({
			input: 'resources/js/app.js',
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
	],
});

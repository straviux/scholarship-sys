import './bootstrap';
import '../css/app.css';

// Normalize display scaling by detecting device pixel ratio
// This accounts for Windows DPI scaling (125%, 150%, etc.)
const dpr = window.devicePixelRatio || 1;
document.documentElement.style.setProperty('--device-pixel-ratio', dpr);

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
// import VueDeviceDetect from '@basitcodeenv/vue3-device-detect';
import PrimeVue from 'primevue/config';
import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';
import VueDeviceDetect from '@basitcodeenv/vue3-device-detect';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import 'primeicons/primeicons.css';
import permissionDirective from './directives/permission';

const appName = import.meta.env.VITE_APP_NAME || 'Scholarship Management System';

const Noir = definePreset(Aura, {
	semantic: {
		primary: {
			50: '{zinc.50}',
			100: '{zinc.100}',
			200: '{zinc.200}',
			300: '{zinc.300}',
			400: '{zinc.400}',
			500: '{zinc.500}',
			600: '{zinc.600}',
			700: '{zinc.700}',
			800: '{zinc.800}',
			900: '{zinc.900}',
			950: '{zinc.950}',
		},
		colorScheme: {
			light: {
				primary: {
					color: '{zinc.800}',
					inverseColor: '#ffffff',
					hoverColor: '{zinc.900}',
					activeColor: '{zinc.800}',
				},
				highlight: {
					background: '{zinc.950}',
					focusBackground: '{zinc.700}',
					color: '#ffffff',
					focusColor: '#ffffff',
				},
			},
			dark: {
				primary: {
					color: '{zinc.50}',
					inverseColor: '{zinc.950}',
					hoverColor: '{zinc.100}',
					activeColor: '{zinc.200}',
				},
				highlight: {
					background: 'rgba(250, 250, 250, .16)',
					focusBackground: 'rgba(250, 250, 250, .24)',
					color: 'rgba(255,255,255,.87)',
					focusColor: 'rgba(255,255,255,.87)',
				},
			},
		},
	},
});

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: (name) =>
		resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
	setup({ el, App, props, plugin }) {
		return createApp({ render: () => h(App, props) })
			.use(plugin)
			.use(ZiggyVue)
			.use(VueDeviceDetect)
			.use(PrimeVue, {
				ripple: true,
				theme: {
					preset: Noir,
					options: {
						prefix: 'p',
						darkModeSelector: false,
						cssLayer: false,
					},
				},
			})
			.component('VueDatePicker', VueDatePicker)
			.directive('can', permissionDirective)
			.mount(el);
	},
	progress: {
		// The delay after which the progress bar will appear, in milliseconds...
		delay: 250,

		// The color of the progress bar...
		color: '#29d',

		// Whether to include the default NProgress styles...
		includeCSS: true,

		// Whether the NProgress spinner will be shown...
		showSpinner: true,
	},
});

import './bootstrap';
import '../css/app.css';
import '../css/ios-design-system.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
// import VueDeviceDetect from '@basitcodeenv/vue3-device-detect';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';
import VueDeviceDetect from '@basitcodeenv/vue3-device-detect';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import 'primeicons/primeicons.css';
import permissionDirective from './directives/permission';
import smoothScrollDirective from './directives/smoothScroll';
import animateTableRowsDirective from './directives/animateTableRows';
import animationPlugin from './plugins/animationPlugin';

// PrimeVue Components - Global Registration
import Button from 'primevue/button';
import Card from 'primevue/card';
import Panel from 'primevue/panel';
import Dialog from 'primevue/dialog';
import Drawer from 'primevue/drawer';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import InputIcon from 'primevue/inputicon';
import IconField from 'primevue/iconfield';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import Avatar from 'primevue/avatar';
import FileUpload from 'primevue/fileupload';
import Menu from 'primevue/menu';
import ContextMenu from 'primevue/contextmenu';
import Toast from 'primevue/toast';
import Timeline from 'primevue/timeline';
import ProgressSpinner from 'primevue/progressspinner';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Toolbar from 'primevue/toolbar';
import Popover from 'primevue/popover';
import DataView from 'primevue/dataview';
import ConfirmDialog from 'primevue/confirmdialog';
import FloatLabel from 'primevue/floatlabel';

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
		const app = createApp({ render: () => h(App, props) })
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
			.use(ToastService)
			.use(ConfirmationService)
			.use(animationPlugin)
			.component('VueDatePicker', VueDatePicker)
			.directive('can', permissionDirective)
			.directive('smooth-scroll', smoothScrollDirective)
			.directive('animate-table-rows', animateTableRowsDirective);

		// Register PrimeVue components globally
		app.component('Button', Button);
		app.component('Card', Card);
		app.component('Panel', Panel);
		app.component('Dialog', Dialog);
		app.component('Drawer', Drawer);
		app.component('DataTable', DataTable);
		app.component('Column', Column);
		app.component('InputText', InputText);
		app.component('InputNumber', InputNumber);
		app.component('InputIcon', InputIcon);
		app.component('IconField', IconField);
		app.component('Textarea', Textarea);
		app.component('Select', Select);
		app.component('DatePicker', DatePicker);
		app.component('Checkbox', Checkbox);
		app.component('RadioButton', RadioButton);
		app.component('ToggleSwitch', ToggleSwitch);
		app.component('Tag', Tag);
		app.component('Badge', Badge);
		app.component('Chip', Chip);
		app.component('Avatar', Avatar);
		app.component('FileUpload', FileUpload);
		app.component('Menu', Menu);
		app.component('ContextMenu', ContextMenu);
		app.component('Toast', Toast);
		app.component('Timeline', Timeline);
		app.component('ProgressSpinner', ProgressSpinner);
		app.component('Tabs', Tabs);
		app.component('TabList', TabList);
		app.component('Tab', Tab);
		app.component('TabPanels', TabPanels);
		app.component('TabPanel', TabPanel);
		app.component('Toolbar', Toolbar);
		app.component('Popover', Popover);
		app.component('DataView', DataView);
		app.component('ConfirmDialog', ConfirmDialog);
		app.component('FloatLabel', FloatLabel);

		return app.mount(el);
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

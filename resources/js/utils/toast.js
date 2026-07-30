import ToastEventBus from 'primevue/toasteventbus';

const DEFAULT_LIFE = 3000;

const POSITION = {
	TOP_LEFT: 'top-left',
	TOP_CENTER: 'top-center',
	TOP_RIGHT: 'top-right',
	BOTTOM_LEFT: 'bottom-left',
	BOTTOM_CENTER: 'bottom-center',
	BOTTOM_RIGHT: 'bottom-right',
};

function normalizeMessage(detail, options = {}, severity = 'info') {
	// No close button — the whole toast is clickable to dismiss instead.
	if (detail && typeof detail === 'object' && !Array.isArray(detail)) {
		return {
			severity,
			closable: false,
			...detail,
			life: detail.life ?? (typeof options.autoClose === 'number' ? options.autoClose : DEFAULT_LIFE),
		};
	}

	// PrimeVue's toast template always renders the `summary` line (even when
	// empty) and only conditionally renders `detail`. Plain string calls like
	// toast.success('Saved') have just one line of text — put it in `summary`
	// so no empty leading line throws off vertical centering with the icon.
	return {
		severity,
		summary: detail,
		closable: false,
		life: typeof options.autoClose === 'number' ? options.autoClose : DEFAULT_LIFE,
		...options,
	};
}

function emitToast(severity, detail, options) {
	ToastEventBus.emit('add', normalizeMessage(detail, options, severity));
}

const toast = (detail, options) => emitToast('info', detail, options);

toast.POSITION = POSITION;
toast.add = (message) => ToastEventBus.emit('add', normalizeMessage(message, {}, message?.severity || 'info'));
toast.success = (detail, options) => emitToast('success', detail, options);
toast.info = (detail, options) => emitToast('info', detail, options);
toast.warn = (detail, options) => emitToast('warn', detail, options);
toast.warning = (detail, options) => emitToast('warn', detail, options);
toast.error = (detail, options) => emitToast('error', detail, options);

// Bind to the <Toast :onClick="onToastClick" /> prop so clicking anywhere on
// a toast dismisses it, since the per-message close button is hidden.
const onToastClick = ({ message }) => {
	if (message) {
		ToastEventBus.emit('remove', message);
	}
};

export { toast, onToastClick };
export default { toast, onToastClick };
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
	if (detail && typeof detail === 'object' && !Array.isArray(detail)) {
		return {
			severity,
			...detail,
			life: detail.life ?? (typeof options.autoClose === 'number' ? options.autoClose : DEFAULT_LIFE),
		};
	}

	return {
		severity,
		detail,
		life: typeof options.autoClose === 'number' ? options.autoClose : DEFAULT_LIFE,
	};
}

function emitToast(severity, detail, options) {
	ToastEventBus.emit('add', normalizeMessage(detail, options, severity));
}

const toast = (detail, options) => emitToast('info', detail, options);

toast.POSITION = POSITION;
toast.success = (detail, options) => emitToast('success', detail, options);
toast.info = (detail, options) => emitToast('info', detail, options);
toast.warn = (detail, options) => emitToast('warn', detail, options);
toast.warning = (detail, options) => emitToast('warn', detail, options);
toast.error = (detail, options) => emitToast('error', detail, options);

export { toast };
export default { toast };
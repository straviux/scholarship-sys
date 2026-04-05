import { sanitizeHtml } from '@/utils/sanitize';

export default {
	mounted(el, binding) {
		el.innerHTML = sanitizeHtml(binding.value);
	},
	updated(el, binding) {
		if (binding.value !== binding.oldValue) {
			el.innerHTML = sanitizeHtml(binding.value);
		}
	},
};

import { useSmoothScroll } from '@/composables/useSmoothScroll';

/**
 * Vue directive for smooth scrolling to elements
 * Usage: v-smooth-scroll or v-smooth-scroll="{ offset: 60, duration: 0.8 }"
 *
 * Example:
 * <a href="#section-id" v-smooth-scroll>Scroll to section</a>
 * <a href="#target" v-smooth-scroll="{ offset: 80, duration: 1.0 }">Custom scroll</a>
 */
export default {
	mounted(el, binding) {
		const options = typeof binding.value === 'object' ? binding.value : {};
		const offset = options.offset ?? 0;
		const duration = options.duration ?? 0.8;

		el.addEventListener('click', (e) => {
			const href = el.getAttribute('href');
			if (href && href.startsWith('#')) {
				e.preventDefault();

				const { scrollToElement } = useSmoothScroll();
				const targetId = href.substring(1);
				const target = document.getElementById(targetId);

				if (target) {
					scrollToElement(target, offset, duration);
				}
			}
		});
	},
};

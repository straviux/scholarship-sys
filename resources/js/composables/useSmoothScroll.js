import { ref, onMounted, onUnmounted } from 'vue';
import { shouldAnimate } from './useAnimationDefaults';

/**
 * Composable for smooth scrolling functionality
 * Provides methods to scroll to elements or specific positions smoothly
 */
export const useSmoothScroll = () => {
	const isScrolling = ref(false);
	const scrollProgress = ref(0);

	/**
	 * Scroll to a specific element smoothly
	 * @param {Element|string} target - DOM element or selector string
	 * @param {number} offset - Offset from top in pixels
	 * @param {number} duration - Duration in seconds
	 */
	const scrollToElement = (target, offset = 0, duration = 0.8) => {
		if (isScrolling.value) return;

		let element;
		if (typeof target === 'string') {
			element = document.querySelector(target);
		} else {
			element = target;
		}

		if (!element) return;

		const targetPosition = element.getBoundingClientRect().top + window.scrollY - offset;

		if (!shouldAnimate()) {
			window.scrollTo(0, targetPosition);
			return;
		}

		isScrolling.value = true;
		window.scrollTo({ top: targetPosition, behavior: 'smooth' });
		window.setTimeout(() => {
			isScrolling.value = false;
		}, Math.max(50, Math.round(duration * 1000)));
	};

	/**
	 * Scroll to specific position smoothly
	 * @param {number} y - Y position in pixels
	 * @param {number} duration - Duration in seconds
	 */
	const scrollToPosition = (y, duration = 0.8) => {
		if (isScrolling.value) return;

		if (!shouldAnimate()) {
			window.scrollTo(0, y);
			return;
		}

		isScrolling.value = true;
		window.scrollTo({ top: y, behavior: 'smooth' });
		window.setTimeout(() => {
			isScrolling.value = false;
		}, Math.max(50, Math.round(duration * 1000)));
	};

	/**
	 * Scroll to top smoothly
	 * @param {number} duration - Duration in seconds
	 */
	const scrollToTop = (duration = 0.6) => {
		scrollToPosition(0, duration);
	};

	/**
	 * Track scroll progress (for progress bars, etc.)
	 */
	const updateScrollProgress = () => {
		const scrollTop = window.scrollY;
		const docHeight = document.documentElement.scrollHeight - window.innerHeight;
		scrollProgress.value = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
	};

	onMounted(() => {
		window.addEventListener('scroll', updateScrollProgress);
	});

	onUnmounted(() => {
		window.removeEventListener('scroll', updateScrollProgress);
	});

	return {
		isScrolling,
		scrollProgress,
		scrollToElement,
		scrollToPosition,
		scrollToTop,
	};
};

/**
 * Get current scroll position
 */
export const getScrollPosition = () => {
	return window.scrollY || window.pageYOffset;
};

/**
 * Check if element is in viewport
 */
export const isElementInViewport = (element) => {
	if (!element) return false;
	const rect = element.getBoundingClientRect();
	return rect.top <= window.innerHeight && rect.bottom >= 0;
};

/**
 * Check if user has scrolled past a certain position
 */
export const hasScrolledPast = (position) => {
	return getScrollPosition() > position;
};

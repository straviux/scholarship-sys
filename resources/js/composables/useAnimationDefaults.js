import { ref } from 'vue';

// Check for prefers-reduced-motion preference
export const prefersReducedMotion = () => {
	return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
};

// Animation timing presets
export const animationTimings = {
	microInteraction: 0.2, // 200ms for quick feedback
	transition: 0.3, // 300ms for transitions
	entrance: 0.4, // 400ms for entering elements
	exit: 0.25, // 250ms for leaving elements
};

// Animation easing presets
export const easings = {
	default: 'power2.out',
	bounce: 'elastic.out(1, 0.5)',
	spring: 'back.out(1.7)',
	smooth: 'sine.inOut',
};

// Wrapper to skip animations if user prefers reduced motion
// TEMPORARILY DISABLED — all GSAP animations off for debugging
export const shouldAnimate = () => {
	return false;
	// return !prefersReducedMotion();
};

// Get delay based on animation preference
export const getAnimationDuration = (duration) => {
	return shouldAnimate() ? duration : 0;
};

// Monitor changes to prefers-reduced-motion
export const useReducedMotionPreference = () => {
	const prefersReduced = ref(prefersReducedMotion());

	if (typeof window !== 'undefined') {
		const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
		const handleChange = (e) => {
			prefersReduced.value = e.matches;
		};

		mediaQuery.addEventListener('change', handleChange);

		onBeforeUnmount(() => {
			mediaQuery.removeEventListener('change', handleChange);
		});
	}

	return prefersReduced;
};

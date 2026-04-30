// Check for prefers-reduced-motion preference
const prefersReducedMotion = () => {
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
export const shouldAnimate = () => {
	return !prefersReducedMotion();
};

import { animationTimings, easings, shouldAnimate } from '../composables/useAnimationDefaults';
import { quickAnimate } from '../composables/useGSAPAnimation';

/**
 * Predefined animations for common component interactions
 */

// Toggle/Checkbox animations
export const toggleAnimation = {
	pulse: (element) => ({
		scale: 1.15,
		duration: animationTimings.microInteraction,
		yoyo: true,
		repeat: 1,
		ease: easings.spring,
	}),
	flip: (element) => ({
		rotateY: 360,
		duration: animationTimings.microInteraction,
		ease: easings.default,
	}),
};

// Select dropdown animations
export const selectAnimation = {
	open: (element) => ({
		opacity: 1,
		scaleY: 1,
		duration: animationTimings.transition,
		ease: easings.default,
		transformOrigin: '50% 0%',
	}),
	close: (element) => ({
		opacity: 0,
		scaleY: 0.9,
		duration: animationTimings.exit,
		ease: easings.default,
		transformOrigin: '50% 0%',
	}),
	optionStagger: (element) => ({
		opacity: 1,
		y: 0,
		duration: animationTimings.transition,
		stagger: 0.05,
		ease: easings.default,
	}),
};

// Button animations
export const buttonAnimation = {
	click: (element) => ({
		scale: 0.95,
		duration: animationTimings.microInteraction * 0.5,
		yoyo: true,
		repeat: 1,
		ease: 'power1.inOut',
	}),
	hover: (element) => ({
		scale: 1.05,
		duration: animationTimings.microInteraction,
		ease: easings.default,
	}),
	success: (element) => ({
		scale: 1.1,
		duration: animationTimings.microInteraction,
		repeat: 1,
		yoyo: true,
		ease: 'elastic.out(1, 0.5)',
	}),
};

// Modal/Dialog animations
export const modalAnimation = {
	entrance: (element) => ({
		opacity: 0,
		scale: 0.95,
		y: -20,
		duration: animationTimings.entrance,
		ease: easings.spring,
	}),
	exit: (element) => ({
		opacity: 0,
		scale: 0.9,
		y: -20,
		duration: animationTimings.exit,
		ease: easings.default,
	}),
};

// Fade animations
export const fadeAnimation = {
	in: (element) => ({
		opacity: 1,
		duration: animationTimings.transition,
		ease: easings.smooth,
	}),
	out: (element) => ({
		opacity: 0,
		duration: animationTimings.exit,
		ease: easings.smooth,
	}),
};

// Slide animations
export const slideAnimation = {
	inRight: (element) => ({
		x: 20,
		opacity: 0,
		duration: animationTimings.entrance,
		ease: easings.default,
	}),
	inLeft: (element) => ({
		x: -20,
		opacity: 0,
		duration: animationTimings.entrance,
		ease: easings.default,
	}),
	outRight: (element) => ({
		x: 20,
		opacity: 0,
		duration: animationTimings.exit,
		ease: easings.default,
	}),
	outLeft: (element) => ({
		x: -20,
		opacity: 0,
		duration: animationTimings.exit,
		ease: easings.default,
	}),
};

// List item animations
export const listAnimation = {
	itemStagger: (selector) => ({
		opacity: 1,
		y: 0,
		duration: animationTimings.transition,
		stagger: 0.08,
		ease: easings.default,
	}),
	itemEntry: () => ({
		opacity: 0,
		y: 10,
		duration: animationTimings.transition,
		ease: easings.default,
	}),
};

// Shake animation (for errors)
export const shakeAnimation = (element) => ({
	x: 10,
	duration: animationTimings.microInteraction * 0.25,
	repeat: 3,
	yoyo: true,
	ease: 'sine.inOut',
});

// Pulse animation (loading/attention)
export const pulseAnimation = (element) => ({
	opacity: [1, 0.6, 1],
	duration: 1.5,
	repeat: -1,
	ease: 'sine.inOut',
});

// Highlight animation (form validation)
export const highlightAnimation = (element) => ({
	backgroundColor: ['transparent', 'rgba(34, 197, 94, 0.1)', 'transparent'],
	duration: animationTimings.transition,
	ease: easings.smooth,
});

// DataTable Row animations
export const dataTableAnimation = {
	// Stagger rows on table load
	rowStagger: (selector) => ({
		opacity: 1,
		y: 0,
		duration: animationTimings.transition,
		stagger: 0.05, // 50ms between each row
		ease: easings.smooth,
	}),
	// Individual row entry state
	rowEntry: () => ({
		opacity: 0,
		y: 10,
		duration: animationTimings.transition,
		ease: easings.smooth,
	}),
	// Row hover effect
	rowHover: (element) => ({
		backgroundColor: 'rgba(79, 172, 254, 0.05)',
		duration: animationTimings.microInteraction * 0.75,
		ease: easings.default,
	}),
	// Row click/select animation
	rowSelect: (element) => ({
		scale: 1.02,
		duration: animationTimings.microInteraction,
		ease: easings.spring,
	}),
	// Cell highlight
	cellHighlight: (element) => ({
		backgroundColor: ['transparent', 'rgba(59, 130, 246, 0.1)', 'transparent'],
		duration: animationTimings.transition,
		ease: easings.smooth,
	}),
};

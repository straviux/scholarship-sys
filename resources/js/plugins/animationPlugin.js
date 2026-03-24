import { useGSAPAnimation, quickAnimate, quickAnimateFrom } from '../composables/useGSAPAnimation';
import {
	shouldAnimate,
	getAnimationDuration,
	prefersReducedMotion,
	useReducedMotionPreference,
	animationTimings,
	easings,
} from '../composables/useAnimationDefaults';

/**
 * Vue 3 Plugin for global GSAP animation utilities
 * Provides $animate, $animateFrom, and animation utilities via $animationConfig
 *
 * Usage in components:
 * this.$animate(element, vars)
 * this.$animationConfig.timings
 * this.$animationConfig.easings
 */
export default {
	install(app) {
		// Global animation utilities
		app.config.globalProperties.$animate = quickAnimate;
		app.config.globalProperties.$animateFrom = quickAnimateFrom;

		// Animation configuration
		app.config.globalProperties.$animationConfig = {
			timings: animationTimings,
			easings: easings,
			shouldAnimate,
			getAnimationDuration,
			prefersReducedMotion,
			useReducedMotionPreference,
		};

		// Export composables for use in setup API
		app.provide('useGSAPAnimation', useGSAPAnimation);
		app.provide('animations', {
			timings: animationTimings,
			easings: easings,
			shouldAnimate,
		});
	},
};

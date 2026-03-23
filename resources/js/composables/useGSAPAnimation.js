import { onMounted, onBeforeUnmount, ref } from 'vue';
import gsap from 'gsap';
import { shouldAnimate, getAnimationDuration } from './useAnimationDefaults';

/**
 * Main composable for GSAP animations with automatic cleanup
 * Prevents memory leaks when used with Inertia page transitions
 *
 * Usage:
 * const { ctx, animate } = useGSAPAnimation();
 *
 * onMounted(() => {
 *   animate(element, { duration: 0.3, opacity: 1 });
 * });
 */
export const useGSAPAnimation = () => {
	const ctx = ref(null);

	onMounted(() => {
		// Create GSAP context for automatic cleanup
		ctx.value = gsap.context(() => {
			// Context created
		});
	});

	onBeforeUnmount(() => {
		// Cleanup all animations and selectors in this context
		if (ctx.value) {
			ctx.value.revert();
		}
	});

	const animate = (target, vars) => {
		if (!shouldAnimate()) {
			return Promise.resolve();
		}

		return gsap.to(target, vars);
	};

	const animateFrom = (target, vars) => {
		if (!shouldAnimate()) {
			return Promise.resolve();
		}

		return gsap.from(target, vars);
	};

	const stagger = (targets, vars) => {
		if (!shouldAnimate()) {
			return Promise.resolve();
		}

		return gsap.to(targets, vars);
	};

	const timeline = () => {
		if (!shouldAnimate()) {
			return {
				to: () => timeline(),
				from: () => timeline(),
				add: () => timeline(),
			};
		}

		return gsap.timeline();
	};

	return {
		ctx,
		animate,
		animateFrom,
		stagger,
		timeline,
	};
};

/**
 * Quick animation utility for simple tweens
 * Doesn't require lifecycle management
 */
export const quickAnimate = (target, vars) => {
	if (!shouldAnimate()) return Promise.resolve();
	return gsap.to(target, vars);
};

/**
 * Quick animation from current state
 */
export const quickAnimateFrom = (target, vars) => {
	if (!shouldAnimate()) return Promise.resolve();
	return gsap.from(target, vars);
};

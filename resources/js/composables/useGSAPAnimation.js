import { onMounted, onBeforeUnmount, ref } from 'vue';
import { shouldAnimate } from './useAnimationDefaults';

const easeMap = {
	'power1.out': 'cubic-bezier(0.22, 1, 0.36, 1)',
	'power2.out': 'cubic-bezier(0.16, 1, 0.3, 1)',
	'power3.out': 'cubic-bezier(0.16, 1, 0.3, 1)',
	'power1.in': 'cubic-bezier(0.32, 0, 0.67, 0)',
	'power2.in': 'cubic-bezier(0.32, 0, 0.67, 0)',
	'power3.in': 'cubic-bezier(0.32, 0, 0.67, 0)',
	'back.out(1.7)': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
	default: 'ease',
};

function toMs(duration) {
	const d = Number.isFinite(duration) ? duration : 0.3;
	return Math.max(0, d * 1000);
}

function parseClearProps(clearProps) {
	if (!clearProps || typeof clearProps !== 'string') return [];
	return clearProps
		.split(',')
		.map((v) => v.trim())
		.filter(Boolean);
}

function applyClearProps(el, clearProps) {
	for (const prop of clearProps) {
		if (prop === 'transform') el.style.transform = '';
		if (prop === 'opacity') el.style.opacity = '';
	}
}

function buildTransform(vars = {}, fallback = '') {
	const x = vars.x ?? 0;
	const y = vars.y ?? 0;
	const scale = vars.scale ?? 1;
	const hasTransform = vars.x != null || vars.y != null || vars.scale != null;
	if (!hasTransform) return fallback;
	return `translate(${x}px, ${y}px) scale(${scale})`;
}

function runTransition(el, vars = {}, from = false) {
	if (!el || !shouldAnimate()) return Promise.resolve();

	const duration = toMs(vars.duration);
	const easing = easeMap[vars.ease] || easeMap.default;
	const clearProps = parseClearProps(vars.clearProps);

	const originalTransition = el.style.transition;
	const targetTransform = from ? '' : buildTransform(vars, '');
	const fromTransform = from ? buildTransform(vars, '') : '';

	if (from) {
		if (vars.opacity != null) el.style.opacity = String(vars.opacity);
		if (fromTransform) el.style.transform = fromTransform;
	}

	void el.offsetWidth;

	el.style.transition = `transform ${duration}ms ${easing}, opacity ${duration}ms ${easing}`;
	if (from) {
		el.style.opacity = '';
		el.style.transform = targetTransform;
	} else {
		if (vars.opacity != null) el.style.opacity = String(vars.opacity);
		el.style.transform = targetTransform;
	}

	return new Promise((resolve) => {
		window.setTimeout(() => {
			el.style.transition = originalTransition;
			applyClearProps(el, clearProps);
			resolve();
		}, duration);
	});
}

export const useGSAPAnimation = () => {
	const ctx = ref(null);

	onMounted(() => {
		ctx.value = { active: true };
	});

	onBeforeUnmount(() => {
		ctx.value = null;
	});

	const animate = (target, vars) => runTransition(target, vars, false);
	const animateFrom = (target, vars) => runTransition(target, vars, true);

	const stagger = (targets, vars) => {
		if (!shouldAnimate()) return Promise.resolve();
		const items = Array.from(targets || []);
		const staggerMs = toMs(vars?.stagger ?? 0);
		return Promise.all(
			items.map(
				(item, index) =>
					new Promise((resolve) => {
						window.setTimeout(() => {
							runTransition(item, vars, false).then(resolve);
						}, index * staggerMs);
					}),
			),
		);
	};

	const timeline = () => {
		const api = {
			to: () => api,
			from: () => api,
			add: () => api,
		};
		return api;
	};

	return {
		ctx,
		animate,
		animateFrom,
		stagger,
		timeline,
	};
};
export const quickAnimateFrom = (target, vars) => runTransition(target, vars, true);

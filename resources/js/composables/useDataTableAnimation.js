import { ref, onMounted, onUnmounted, watch } from 'vue';
import gsap from 'gsap';
import { dataTableAnimation } from '@/utils/animations';
import { shouldAnimate } from './useAnimationDefaults';

/**
 * Composable for animating DataTable rows
 * Provides methods to animate table rows on load and pagination
 */
export const useDataTableAnimation = (tableRef, options = {}) => {
	const isAnimating = ref(false);
	const animatedRows = ref(new Set());

	const { duration = 0.3, stagger = 0.05, ease = 'power2.out', enabled = true } = options;

	/**
	 * Animate table rows with stagger effect
	 * @param {Element|string} container - DataTable container or selector
	 * @param {number} delay - Initial delay before animation starts
	 */
	const animateRows = async (container = tableRef, delay = 0) => {
		if (!enabled || !shouldAnimate() || isAnimating.value) return;

		const table = typeof container === 'string' ? document.querySelector(container) : container;
		if (!table) return;

		// Get all tbody rows that haven't been animated yet
		const rows = Array.from(table.querySelectorAll('.p-datatable-tbody > tr')).filter((row) => {
			// Skip if already animated in this view
			return !animatedRows.value.has(row);
		});

		if (rows.length === 0) return;

		isAnimating.value = true;

		try {
			// Set initial state for rows
			gsap.set(rows, {
				opacity: 0,
				y: 10,
			});

			// Animate rows with stagger
			await gsap.to(rows, {
				opacity: 1,
				y: 0,
				duration,
				stagger,
				ease,
				delay,
				onComplete: () => {
					// Mark rows as animated
					rows.forEach((row) => animatedRows.value.add(row));
				},
			});
		} finally {
			isAnimating.value = false;
		}
	};

	/**
	 * Clear animation cache when table data changes
	 */
	const resetAnimationCache = () => {
		animatedRows.value.clear();
	};

	/**
	 * Observe DataTable for row changes and animate new rows
	 */
	const observeTableRows = () => {
		if (!enabled || !shouldAnimate() || !tableRef) return;

		const table = typeof tableRef === 'string' ? document.querySelector(tableRef) : tableRef;
		if (!table) return;

		// Create a mutation observer to detect new rows
		const observer = new MutationObserver((mutations) => {
			let hasNewRows = false;

			mutations.forEach((mutation) => {
				if (mutation.type === 'childList') {
					// Check if tbody has new tr elements
					const tbody = table.querySelector('.p-datatable-tbody');
					if (tbody && mutation.target === tbody) {
						hasNewRows = true;
					}
				}
			});

			// Animate new rows after a small delay to allow DOM to settle
			if (hasNewRows) {
				setTimeout(() => {
					animateRows(table, 0);
				}, 50);
			}
		});

		// Observe the tbody for changes
		const tbody = table.querySelector('.p-datatable-tbody');
		if (tbody) {
			observer.observe(tbody, {
				childList: true,
				subtree: false,
			});
		}

		return () => observer.disconnect();
	};

	/**
	 * Animate on initial mount
	 */
	onMounted(() => {
		if (enabled && shouldAnimate()) {
			setTimeout(() => {
				animateRows(tableRef, 0.1);
			}, 100);

			observeTableRows();
		}
	});

	return {
		isAnimating,
		animateRows,
		resetAnimationCache,
		observeTableRows,
	};
};

/**
 * Hook for use in v-for rendered rows
 * Call this from a DirectiveHook or component to animate individual rows
 */
export const useRowAnimation = () => {
	const animateRowOnMount = (element, duration = 0.3, ease = 'power2.out') => {
		if (!shouldAnimate()) return;

		gsap.fromTo(
			element,
			{
				opacity: 0,
				y: 10,
			},
			{
				opacity: 1,
				y: 0,
				duration,
				ease,
			},
		);
	};

	return {
		animateRowOnMount,
	};
};

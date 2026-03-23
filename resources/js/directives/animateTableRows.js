import gsap from 'gsap';
import { shouldAnimate } from '@/composables/useAnimationDefaults';

/**
 * Directive: v-animate-table-rows
 * Automatically animates DataTable rows with stagger effect
 * Prevents re-animation on UI interactions like right-clicks
 *
 * Usage:
 * <DataTable v-animate-table-rows="{ duration: 0.3, stagger: 0.05 }" ... />
 * <DataTable v-animate-table-rows ... /> (uses defaults)
 */
export default {
	mounted(el, binding) {
		if (!shouldAnimate()) return;

		const options = binding.value || {};
		const { duration = 0.3, stagger = 0.05, ease = 'power2.out', delay = 0.1 } = options;

		// Track which rows have been animated to avoid re-animating
		el._animatedRowIds = new Set();
		el._lastRowCount = 0;
		let isAnimating = false;

		// Function to animate rows
		const animateTableRows = () => {
			const rows = el.querySelectorAll('.p-datatable-tbody > tr');
			if (rows.length === 0) return;

			// Find NEW rows that haven't been animated yet
			const newRows = Array.from(rows).filter((row) => {
				const rowId = row.getAttribute('data-p-index') || row.getAttribute('data-p-key');
				return rowId && !el._animatedRowIds.has(rowId);
			});

			if (newRows.length === 0 && el._lastRowCount > 0) {
				// Rows already animated, don't re-animate
				return;
			}

			// Mark all rows as animated
			Array.from(rows).forEach((row) => {
				const rowId = row.getAttribute('data-p-index') || row.getAttribute('data-p-key');
				if (rowId) el._animatedRowIds.add(rowId);
			});

			el._lastRowCount = rows.length;

			if (isAnimating) return;
			isAnimating = true;

			// Set initial state for all rows
			gsap.set(rows, {
				opacity: 0,
				y: 10,
			});

			// Animate rows with stagger
			gsap.to(rows, {
				opacity: 1,
				y: 0,
				duration,
				stagger,
				ease,
				delay,
				onComplete: () => {
					isAnimating = false;
				},
			});
		};

		// Animate on mount
		setTimeout(() => {
			animateTableRows();
		}, 100);

		// Watch for tbody changes (pagination, filtering, sorting)
		// Only animate if the number of rows actually changed
		const observer = new MutationObserver(() => {
			clearTimeout(observer._debounceTimeout);
			observer._debounceTimeout = setTimeout(() => {
				const currentRows = el.querySelectorAll('.p-datatable-tbody > tr').length;
				// Only re-animate if row count changed (pagination/filter/sort)
				if (currentRows !== el._lastRowCount) {
					el._animatedRowIds.clear();
					animateTableRows();
				}
			}, 150);
		});

		// Observe tbody for structural changes only
		const tbody = el.querySelector('.p-datatable-tbody');
		if (tbody) {
			observer.observe(tbody, {
				childList: true, // Only detect row additions/removals
				subtree: false,
			});
		}

		// Cleanup on unmount
		el._destroyTableAnimationObserver = () => {
			observer.disconnect();
			clearTimeout(observer._debounceTimeout);
			el._animatedRowIds.clear();
		};
	},

	unmounted(el) {
		if (el._destroyTableAnimationObserver) {
			el._destroyTableAnimationObserver();
		}
	},
};

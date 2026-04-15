import { ref } from 'vue';
import axios from 'axios';

// Module-level cache: shared across all component instances for the page session
const _cache = {};

/**
 * Fetches and caches system options by category from the database.
 * Returns a reactive ref of { label, value } objects.
 *
 * Usage:
 *   const disbursementTypes = useSystemOptions('disbursement_type');
 *   // works as :options with optionLabel="label" optionValue="value"
 *
 *   // For plain string arrays (PrimeVue Select without optionLabel):
 *   const grantProvisions = computed(() => useSystemOptions('grant_provision').value.map(o => o.value));
 */
export function useSystemOptions(category) {
	if (!_cache[category]) {
		const options = ref([]);
		_cache[category] = options;

		axios
			.get(route('api.system-options.category', category))
			.then((res) => {
				if (Array.isArray(res.data) && res.data.length) {
					options.value = res.data.map((o) => ({ label: o.label, value: o.value }));
				}
			})
			.catch(() => {
				// Silently fail — each consumer should have its own fallback if needed
			});
	}

	return _cache[category];
}

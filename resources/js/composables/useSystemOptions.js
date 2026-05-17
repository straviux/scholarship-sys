import { ref } from 'vue';
import axios from 'axios';

// Module-level cache: shared across all component instances for the page session
const _cache = {};

const formatGrantProvisionAmount = (amount) => {
	if (amount === null || amount === undefined || amount === '') {
		return null;
	}

	const parsedAmount = Number(amount);

	if (!Number.isFinite(parsedAmount)) {
		return null;
	}

	return new Intl.NumberFormat('en-PH', {
		style: 'currency',
		currency: 'PHP',
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	}).format(parsedAmount);
};

const formatOptionLabel = (category, option) => {
	const baseLabel = option.label || option.value;

	if (category !== 'grant_provision') {
		return baseLabel;
	}

	const formattedAmount = formatGrantProvisionAmount(option.amount);

	return formattedAmount ? `${baseLabel} (${formattedAmount})` : baseLabel;
};

const mapOptions = (category, options) =>
	options.map((option) => ({
		...option,
		baseLabel: option.label || option.value,
		label: formatOptionLabel(category, option),
		value: option.value,
	}));

export async function refreshSystemOptions(category) {
	if (!_cache[category]) {
		_cache[category] = ref([]);
	}

	try {
		const response = await axios.get(route('api.system-options.category', category));
		_cache[category].value = Array.isArray(response.data) ? mapOptions(category, response.data) : [];
	} catch {
		// Silently fail — each consumer should have its own fallback if needed
	}

	return _cache[category];
}

/**
 * Fetches and caches system options by category from the database.
 * Returns a reactive ref of option objects, preserving API metadata
 * while still exposing the standard { label, value } shape.
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
		_cache[category] = ref([]);
		void refreshSystemOptions(category);
	}

	return _cache[category];
}

export function getSystemOptionLabel(category, value, fallback = null) {
	const normalizedValue =
		value && typeof value === 'object'
			? value.value ?? value.baseLabel ?? value.label ?? null
			: value;

	if (normalizedValue === null || normalizedValue === undefined || normalizedValue === '') {
		return fallback ?? '';
	}

	const options = useSystemOptions(category);
	const match = options.value.find(
		(option) =>
			option.value === normalizedValue ||
			option.baseLabel === normalizedValue ||
			option.label === normalizedValue,
	);

	return match?.label || normalizedValue;
}

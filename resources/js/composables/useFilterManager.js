import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import moment from 'moment';

/**
 * Reusable filter manager composable for Inertia.js pages with server-side filtering.
 *
 * @param {Object} options
 * @param {string} options.routeName - The Inertia route name for filtering (e.g. 'applicants.index')
 * @param {Object} options.props - Component props (must contain filter/filters and records/profiles_total)
 * @param {Array}  options.filterDefs - Array of filter definitions:
 *   { key: string, type: 'text'|'select'|'date'|'number', default: any, extract?: (val) => string }
 * @param {number} [options.debounceMs=500] - Debounce delay for global search (ms)
 * @param {string} [options.filterPropName] - Name of the filter prop ('filter' or 'filters'), auto-detected if omitted
 * @param {string} [options.totalPropName] - Name of the total records prop, auto-detected if omitted
 * @param {Object} [options.routerOptions] - Extra options passed to router.get() (e.g. { replace: true })
 * @param {Function} [options.beforeSearch] - Hook called with (params) before router.get(), allows mutation
 */
export function useFilterManager({
	routeName,
	props,
	filterDefs = [],
	debounceMs = 800,
	filterPropName,
	totalPropName,
	routerOptions = {},
	beforeSearch,
}) {
	// --- Auto-detect prop names ---
	const filterProp = filterPropName ? props[filterPropName] : props.filter || props.filters || {};

	const totalProp = totalPropName || 'profiles_total';

	// --- Build initial filter values from props + defaults ---
	const filters = ref({});
	const defaults = {};

	for (const def of filterDefs) {
		const propVal = filterProp?.[def.key];
		let initial = propVal !== undefined && propVal !== null ? propVal : def.default ?? null;

		// Type coercion for dates passed as strings
		if (def.type === 'date' && initial && typeof initial === 'string') {
			initial = new Date(initial);
		}
		if (def.type === 'number' && initial !== null) {
			initial = parseInt(initial) || def.default || null;
		}

		filters.value[def.key] = initial;
		defaults[def.key] = def.default ?? null;
	}

	// --- Pagination ---
	const initialRecords =
		parseInt(props.records) || parseInt(filterProp?.records) || getRecordsFromUrl() || 10;
	const records = ref(initialRecords);
	const page = ref(parseInt(filterProp?.page) || 1);
	const rows = computed(() => parseInt(records.value) || 10);
	const first = ref((page.value - 1) * rows.value);
	const totalRecords = computed(() => parseInt(props[totalProp]) || 0);

	// --- Global Search ---
	const globalFilter = ref(filterProp?.global_search || '');
	let debounceTimer = null;

	// --- UI State ---
	const showAllFilters = ref(false);

	// --- Helpers ---
	function getRecordsFromUrl() {
		const urlParams = new URLSearchParams(window.location.search);
		const urlRecords = urlParams.get('records');
		return urlRecords ? parseInt(urlRecords) : null;
	}

	function extractFilterValue(def, val) {
		if (val == null || val === '') return null;

		// Custom extractor takes priority
		if (def.extract) {
			return def.extract(val) || null;
		}

		switch (def.type) {
			case 'date':
				return moment(val).format('YYYY-MM-DD') || null;
			case 'select':
				if (typeof val === 'object') {
					// Common patterns: .shortname, .name, .value
					const extracted = val.shortname || val.name || val.value || '';
					return extracted ? extracted.toString().toLowerCase() : null;
				}
				return val ? val.toString().toLowerCase() : null;
			case 'text':
				return val ? val.toString().toLowerCase() : null;
			case 'number':
				return val != null ? val : null;
			default:
				return val || null;
		}
	}

	// --- Build Params ---
	function buildParams(resetPage = false) {
		const params = {};

		for (const def of filterDefs) {
			const val = filters.value[def.key];
			const paramVal = extractFilterValue(def, val);
			if (paramVal != null && paramVal !== '') {
				params[def.key] = paramVal;
			}
		}

		if (globalFilter.value) {
			params.global_search = globalFilter.value.toLowerCase();
		}

		params.records = records.value;
		params.page = resetPage ? 1 : page.value;

		return params;
	}

	// --- Search ---
	function search(resetPage = true) {
		if (resetPage) page.value = 1;
		const params = buildParams(resetPage);

		// Allow page-specific param mutations (e.g. JPM 3-state, profile_type)
		if (beforeSearch) {
			beforeSearch(params, filters.value);
		}

		router.get(route(routeName), params, {
			preserveState: true,
			preserveScroll: true,
			...routerOptions,
		});
	}

	// --- Clear ---
	function clear() {
		for (const def of filterDefs) {
			filters.value[def.key] = def.default ?? null;
		}
		globalFilter.value = '';
		records.value = 10;
		page.value = 1;

		router.get(
			route(routeName),
			{},
			{
				replace: true,
				preserveScroll: true,
			},
		);
	}

	// --- Pagination Handler ---
	function onPageChange(event) {
		page.value = event.page + 1; // PrimeVue 0-based → backend 1-based
		search(false);
	}

	// --- Watchers ---

	// Global search auto-fires on input with debounce
	watch(globalFilter, () => {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(() => {
			search(true);
		}, debounceMs);
	});

	// Sync first when page changes
	watch(
		page,
		(val) => {
			first.value = (val - 1) * rows.value;
		},
		{ immediate: true },
	);

	// Sync first when rows change
	watch(rows, () => {
		first.value = (page.value - 1) * rows.value;
	});

	return {
		// Filter state
		filters, // reactive object — use as v-model for filter inputs
		globalFilter, // ref — v-model for the quick search bar
		records, // ref — v-model for RecordsSelect
		page, // ref — current page (1-based)

		// DataTable bindings
		rows, // computed — bind to :rows
		first, // ref — bind to :first
		totalRecords, // computed — bind to :totalRecords

		// UI state
		showAllFilters, // ref — toggle advanced filters panel

		// Actions
		search, // () => void — call on "Apply Filter" click
		clear, // () => void — call on "Clear Filters" click
		onPageChange, // (event) => void — bind to DataTable @page

		// Advanced
		buildParams, // (resetPage?) => object — get params without navigating
	};
}

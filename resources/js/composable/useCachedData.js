import { ref } from 'vue';
import axios from 'axios';

// Global cache for API data with TTL
const dataCache = {};
const fetchPromises = {};

/**
 * Composable for fetching and caching data with request deduplication
 * Prevents multiple identical requests from being made simultaneously
 * @param {string} cacheKey - Unique key for caching this data
 * @param {function} fetchFn - Async function that returns the data
 * @param {number} ttl - Time to live in milliseconds (default: 5 minutes)
 * @returns {Object} - { data, loading, error, refetch }
 */
export function useCachedData(cacheKey, fetchFn, ttl = 5 * 60 * 1000) {
	const data = ref(null);
	const loading = ref(false);
	const error = ref(null);

	const fetchData = async (force = false) => {
		// Return cached data if available and not expired
		if (!force && dataCache[cacheKey]) {
			const cached = dataCache[cacheKey];
			if (Date.now() - cached.timestamp < ttl) {
				data.value = cached.data;
				return cached.data;
			}
		}

		// Return existing promise if request is already in flight
		if (fetchPromises[cacheKey]) {
			try {
				const result = await fetchPromises[cacheKey];
				data.value = result;
				return result;
			} catch (err) {
				error.value = err;
				throw err;
			}
		}

		// Initialize new fetch
		loading.value = true;
		error.value = null;

		const promise = fetchFn()
			.then((result) => {
				// Cache the result
				dataCache[cacheKey] = {
					data: result,
					timestamp: Date.now(),
				};
				data.value = result;
				delete fetchPromises[cacheKey];
				return result;
			})
			.catch((err) => {
				error.value = err;
				delete fetchPromises[cacheKey];
				throw err;
			})
			.finally(() => {
				loading.value = false;
			});

		fetchPromises[cacheKey] = promise;
		return promise;
	};

	return {
		data,
		loading,
		error,
		fetchData,
		refetch: () => fetchData(true),
	};
}

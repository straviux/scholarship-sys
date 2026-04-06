// useApi.js
import { ref } from 'vue';
import axios from 'axios';

// Module-level cache shared across all useApi instances
// Only caches parameterless requests (static lists like schools, programs)
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes
const _cache = new Map(); // url → { data, timestamp }

export function useApi(url) {
	const data = ref(null);
	const loading = ref(false);
	const error = ref(null);

	const fetchData = async (params = {}) => {
		const hasParams = Object.keys(params).length > 0;
		const cacheKey = hasParams ? null : url;

		if (cacheKey) {
			const cached = _cache.get(cacheKey);
			if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
				data.value = cached.data;
				return;
			}
		}

		loading.value = true;
		error.value = null;
		try {
			const response = await axios.get(url, { params });
			data.value = response.data;
			if (cacheKey) {
				_cache.set(cacheKey, { data: response.data, timestamp: Date.now() });
			}
		} catch (err) {
			error.value = err;
		} finally {
			loading.value = false;
		}
	};

	return {
		data,
		loading,
		error,
		fetchData,
	};
}

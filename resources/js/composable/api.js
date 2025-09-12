// useApi.js
import { ref } from 'vue';
import axios from 'axios';

export function useApi(url) {
	const data = ref(null);
	const loading = ref(false);
	const error = ref(null);

	const fetchData = async (params = {}) => {
		loading.value = true;
		error.value = null;
		try {
			const response = await axios.get(url, { params });
			// console.log(response.data);
			data.value = response.data;
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

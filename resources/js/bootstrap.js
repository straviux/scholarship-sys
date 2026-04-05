import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

const CSRF_META_SELECTOR = 'meta[name="csrf-token"]';
const SAFE_HTTP_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);
const originalFetch = window.fetch.bind(window);

function getCsrfTokenFromMeta() {
	return document.querySelector(CSRF_META_SELECTOR)?.getAttribute('content') || null;
}

function getXsrfTokenFromCookie() {
	const encodedToken = document.cookie
		.split('; ')
		.find((entry) => entry.startsWith('XSRF-TOKEN='))
		?.split('=')[1];

	if (!encodedToken) {
		return null;
	}

	try {
		return decodeURIComponent(encodedToken);
	} catch {
		return encodedToken;
	}
}

function getBestCsrfToken() {
	return getCsrfTokenFromMeta() || getXsrfTokenFromCookie();
}

function setCsrfToken(token) {
	if (!token) return;

	const csrfMeta = document.querySelector(CSRF_META_SELECTOR);
	if (csrfMeta) {
		csrfMeta.setAttribute('content', token);
	}

	window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
	window.axios.defaults.headers.common['X-XSRF-TOKEN'] = token;
}

let csrfRefreshPromise = null;

async function refreshCsrfToken() {
	if (csrfRefreshPromise) {
		return csrfRefreshPromise;
	}

	// Step 1: Ask Sanctum to regenerate the session CSRF token and set the XSRF-TOKEN cookie.
	// Step 2: Fetch the raw token string from /csrf-token so we can update the meta tag.
	// This is necessary because the meta tag is server-rendered and never auto-updated,
	// so getCsrfTokenFromMeta() would always return the stale original value.
	csrfRefreshPromise = originalFetch('/sanctum/csrf-cookie', {
		method: 'GET',
		credentials: 'include',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
		},
	})
		.then((response) => {
			if (!response.ok) {
				throw new Error(`Failed to refresh CSRF cookie: ${response.status}`);
			}
			return originalFetch('/csrf-token', {
				method: 'GET',
				credentials: 'include',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			});
		})
		.then((response) => {
			if (!response.ok) {
				throw new Error(`Failed to fetch fresh CSRF token: ${response.status}`);
			}
			return response.json();
		})
		.then(({ token }) => {
			setCsrfToken(token);
			return token;
		})
		.finally(() => {
			csrfRefreshPromise = null;
		});

	return csrfRefreshPromise;
}

// Set CSRF token from meta tag
const token = getBestCsrfToken();
if (token) {
	setCsrfToken(token);
} else {
	console.warn('CSRF token not found in meta tag');
}

window.axios.interceptors.response.use(
	(response) => response,
	async (error) => {
		const status = error?.response?.status;
		const originalConfig = error?.config;
		const method = (originalConfig?.method || 'GET').toUpperCase();

		if (
			status === 419 &&
			originalConfig &&
			!originalConfig.__csrfRetried &&
			!SAFE_HTTP_METHODS.has(method)
		) {
			originalConfig.__csrfRetried = true;

			try {
				const refreshedToken = await refreshCsrfToken();
				if (refreshedToken) {
					originalConfig.headers = {
						...(originalConfig.headers || {}),
						'X-CSRF-TOKEN': refreshedToken,
						'X-XSRF-TOKEN': refreshedToken,
					};
				}

				return window.axios(originalConfig);
			} catch (refreshError) {
				return Promise.reject(refreshError);
			}
		}

		return Promise.reject(error);
	},
);

window.fetch = async (input, init = {}) => {
	const inputMethod = input instanceof Request ? input.method : null;
	const method = (init.method || inputMethod || 'GET').toUpperCase();
	const isMutationRequest = !SAFE_HTTP_METHODS.has(method);
	const inputHeaders = input instanceof Request ? input.headers : undefined;
	const currentHeaders = new Headers(inputHeaders || {});

	if (init.headers) {
		new Headers(init.headers).forEach((value, key) => {
			currentHeaders.set(key, value);
		});
	}

	if (!currentHeaders.has('X-Requested-With')) {
		currentHeaders.set('X-Requested-With', 'XMLHttpRequest');
	}

	if (isMutationRequest && !currentHeaders.has('X-CSRF-TOKEN')) {
		const csrfToken = getBestCsrfToken();
		if (csrfToken) {
			currentHeaders.set('X-CSRF-TOKEN', csrfToken);
			currentHeaders.set('X-XSRF-TOKEN', csrfToken);
		}
	}

	const requestInit = {
		...init,
		headers: currentHeaders,
	};

	const response = await originalFetch(input, requestInit);
	if (response.status !== 419 || !isMutationRequest || requestInit.__csrfRetried) {
		return response;
	}

	await refreshCsrfToken();
	const retryHeaders = new Headers(requestInit.headers || {});
	const latestToken = getBestCsrfToken();
	if (latestToken) {
		retryHeaders.set('X-CSRF-TOKEN', latestToken);
		retryHeaders.set('X-XSRF-TOKEN', latestToken);
	}

	return originalFetch(input, {
		...requestInit,
		headers: retryHeaders,
		__csrfRetried: true,
	});
};

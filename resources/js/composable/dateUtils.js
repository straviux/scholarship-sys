/**
 * Date utility composable for consistent date formatting across the application
 * Default timezone: Asia/Manila (Philippine Time)
 */

export const useDateUtils = () => {
	const DEFAULT_TIMEZONE = 'Asia/Manila';
	const DEFAULT_LOCALE = 'en-PH';

	/**
	 * Format date string to localized string with Philippine timezone
	 * @param {string|Date} dateString - The date to format
	 * @param {Object} options - Additional formatting options
	 * @returns {string} Formatted date string
	 */
	const formatDate = (dateString, options = {}) => {
		if (!dateString) return '';

		const defaultOptions = {
			timeZone: DEFAULT_TIMEZONE,
			...options,
		};

		return new Date(dateString).toLocaleString(DEFAULT_LOCALE, defaultOptions);
	};

	/**
	 * Format date to short format (MM/DD/YYYY)
	 * @param {string|Date} dateString - The date to format
	 * @returns {string} Formatted date string
	 */
	const formatDateShort = (dateString) => {
		return formatDate(dateString, {
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
		});
	};

	/**
	 * Format date to long format (e.g., "January 1, 2025 at 10:30 AM")
	 * @param {string|Date} dateString - The date to format
	 * @returns {string} Formatted date string
	 */
	const formatDateLong = (dateString) => {
		return formatDate(dateString, {
			year: 'numeric',
			month: 'long',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
			hour12: true,
		});
	};

	/**
	 * Format time only (e.g., "10:30 AM")
	 * @param {string|Date} dateString - The date to format
	 * @returns {string} Formatted time string
	 */
	const formatTime = (dateString) => {
		return formatDate(dateString, {
			hour: '2-digit',
			minute: '2-digit',
			hour12: true,
		});
	};

	/**
	 * Format date for display in tables or lists (e.g., "Jan 1, 2025 10:30 AM")
	 * @param {string|Date} dateString - The date to format
	 * @returns {string} Formatted date string
	 */
	const formatDateTable = (dateString) => {
		return formatDate(dateString, {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
			hour12: true,
		});
	};

	/**
	 * Get relative time (e.g., "2 hours ago", "3 days ago")
	 * @param {string|Date} dateString - The date to format
	 * @returns {string} Relative time string
	 */
	const formatRelativeTime = (dateString) => {
		if (!dateString) return '';

		const now = new Date();
		const date = new Date(dateString);
		const diffInSeconds = Math.floor((now - date) / 1000);

		if (diffInSeconds < 60) return 'Just now';
		if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
		if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
		if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)} days ago`;

		return formatDateShort(dateString);
	};

	return {
		formatDate,
		formatDateShort,
		formatDateLong,
		formatTime,
		formatDateTable,
		formatRelativeTime,
		DEFAULT_TIMEZONE,
		DEFAULT_LOCALE,
	};
};

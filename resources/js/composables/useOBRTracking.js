// OBR Tracking Composable - For use in VoucherWizard or other components
// File: src/composables/useOBRTracking.js

import { ref, reactive } from 'vue';
import axios from 'axios';
import logger from '@/utils/logger';

export function useOBRTracking() {
	const obrData = reactive({
		items: [],
		loading: false,
		error: '',
		searchQuery: '',
		filters: {
			type: 'GF',
			fiscal_year: new Date().getFullYear(),
			sortField: 'obrDate',
			sortDirection: 'desc',
			page: 0,
			pageSize: 10,
		},
	});

	/**
	 * Search OBR by number
	 * @param {string} obrNo - OBR number to search
	 * @returns {Promise}
	 */
	const searchOBR = async (obrNo) => {
		if (!obrNo || !obrNo.trim()) {
			obrData.error = 'Please enter an OBR number';
			return;
		}

		obrData.loading = true;
		obrData.error = '';

		try {
			logger.info(`Searching for OBR: ${obrNo}`);
			const response = await axios.get(`/api/obr-tracking/search/${obrNo}`);

			if (response.data.success) {
				obrData.items = response.data.data || [];
				logger.info(`Found ${obrData.items.length} OBR records`);
			} else {
				obrData.error = response.data.message || 'Failed to fetch OBR data';
				logger.warn('OBR search failed:', response.data.message);
			}
		} catch (err) {
			obrData.error = `Error searching OBR: ${err.message}`;
			logger.error('OBR search error:', err);
			obrData.items = [];
		} finally {
			obrData.loading = false;
		}
	};

	/**
	 * Fetch OBR with custom filters
	 * @param {Object} filters - Filter parameters
	 * @returns {Promise}
	 */
	const fetchOBRWithFilters = async (filters = {}) => {
		obrData.loading = true;
		obrData.error = '';

		try {
			const params = { ...obrData.filters, ...filters };
			logger.info('Fetching OBR with filters:', params);

			const response = await axios.get('/api/obr-tracking', { params });

			if (response.data.success) {
				obrData.items = response.data.data || [];
				logger.info(`Fetched ${obrData.items.length} OBR records`);
			} else {
				obrData.error = response.data.message || 'Failed to fetch OBR data';
				logger.warn('OBR fetch failed:', response.data.message);
			}
		} catch (err) {
			obrData.error = `Error fetching OBR: ${err.message}`;
			logger.error('OBR fetch error:', err);
			obrData.items = [];
		} finally {
			obrData.loading = false;
		}
	};

	/**
	 * Get OBR details by ID
	 * @param {string|number} id - OBR ID
	 * @returns {Promise<Object>} OBR details
	 */
	const getOBRDetails = async (id) => {
		const obr = obrData.items.find((item) => item.id === id);
		if (obr) {
			return obr;
		}

		try {
			const response = await axios.get(`/api/obr-tracking/${id}`);
			return response.data.data || null;
		} catch (err) {
			logger.error('Error fetching OBR details:', err);
			return null;
		}
	};

	/**
	 * Get detailed OBR tracking information
	 * @param {Object} params - Parameters { fiscal_year, obr_no, dv_no, type }
	 * @returns {Promise<Object>} Tracking info
	 */
	const getOBRTrackingInfo = async (params) => {
		if (!params.fiscal_year || !params.obr_no || !params.dv_no || !params.type) {
			logger.warn('Missing required parameters for OBR tracking info');
			return null;
		}

		try {
			logger.info('Fetching OBR tracking info:', params);
			const response = await axios.get('/api/obr-tracking-info', { params });

			if (response.data.success) {
				logger.info('OBR tracking info fetched successfully');
				return response.data.data || response.data;
			} else {
				logger.warn('OBR tracking info fetch failed');
				return null;
			}
		} catch (err) {
			logger.error('Error fetching OBR tracking info:', err);
			return null;
		}
	};

	/**
	 * Map OBR data to voucher fields
	 * @param {Object} obr - OBR data
	 * @param {Object} voucherData - Voucher form data object (will be mutated)
	 */
	const mapOBRToVoucher = (obr, voucherData) => {
		if (!obr || !voucherData) return;

		try {
			// Map OBR fields to voucher obligations
			voucherData.obligations.payee_name = obr.payeeName || obr.payee || '';
			voucherData.obligations.payee_address = obr.payeeAddress || obr.address || '';
			voucherData.obligations.account_code = obr.accountCode || '';
			voucherData.obligations.responsibility_center = obr.responsibilityCenter || '';
			voucherData.obligations.amount = parseFloat(obr.amount) || 0;
			voucherData.obligations.obr_type = obr.obrType || 'REGULAR';

			// Map disbursement details if available
			if (obr.explanation) {
				voucherData.disbursements.explanation = obr.explanation;
			}

			logger.info('OBR data mapped to voucher successfully');
		} catch (err) {
			logger.error('Error mapping OBR to voucher:', err);
		}
	};

	/**
	 * Clear search and filters
	 */
	const reset = () => {
		obrData.items = [];
		obrData.error = '';
		obrData.searchQuery = '';
		obrData.filters.page = 0;
	};

	/**
	 * Update filter and refetch
	 * @param {string} key - Filter key
	 * @param {*} value - Filter value
	 */
	const updateFilter = (key, value) => {
		obrData.filters[key] = value;
		obrData.filters.page = 0; // Reset to first page
	};

	return {
		obrData,
		searchOBR,
		fetchOBRWithFilters,
		getOBRDetails,
		getOBRTrackingInfo,
		mapOBRToVoucher,
		reset,
		updateFilter,
	};
}

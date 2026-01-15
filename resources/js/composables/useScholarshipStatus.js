import { ref, computed } from 'vue';

export const useScholarshipStatus = () => {
	// Status configuration
	const statusConfig = {
		pending_approval: {
			label: 'Pending Approval',
			severity: 'warning',
			description: 'Awaiting approval',
		},
		active_scholar: {
			label: 'Active Scholar',
			severity: 'success',
			description: 'Approved and enrolled',
		},
		completed: {
			label: 'Completed',
			severity: 'secondary',
			description: 'Scholarship completed',
		},
		declined: {
			label: 'Declined',
			severity: 'danger',
			description: 'Application declined',
		},
		withdrawn: {
			label: 'Withdrawn',
			severity: 'secondary',
			description: 'Withdrawn from scholarship',
		},
		unknown: {
			label: 'Unknown',
			severity: 'secondary',
			description: 'Status unknown',
		},
	};

	/**
	 * Get status configuration by status value
	 */
	const getStatusConfig = (status) => {
		return statusConfig[status] || statusConfig.unknown;
	};

	/**
	 * Get status label
	 */
	const getStatusLabel = (status) => {
		return getStatusConfig(status).label;
	};

	/**
	 * Get status severity (for coloring)
	 */
	const getStatusSeverity = (status) => {
		return getStatusConfig(status).severity;
	};

	/**
	 * Get status description
	 */
	const getStatusDescription = (status) => {
		return getStatusConfig(status).description;
	};

	/**
	 * Fallback: Map legacy approval_status + scholarship_status to unified status
	 */
	const mapLegacyStatus = (approvalStatus, scholarshipStatus) => {
		// Declined takes priority
		if (approvalStatus === 'declined') {
			return 'declined';
		}

		// Pending approval
		if (approvalStatus === 'pending' || approvalStatus === 'conditionally-approved') {
			return 'pending_approval';
		}

		// Approved maps to active_scholar
		if (approvalStatus === 'approved' || approvalStatus === 'auto_approved') {
			if (scholarshipStatus === 3) {
				return 'completed';
			}
			return 'active_scholar';
		}

		return 'unknown';
	};

	/**
	 * Get unified status from record (with fallback to legacy fields)
	 */
	const getUnifiedStatus = (record) => {
		// Try new unified_status first
		if (record.unified_status) {
			return record.unified_status;
		}

		// Fallback to legacy fields
		const fallbackStatus = mapLegacyStatus(record.approval_status, record.scholarship_status);
		return fallbackStatus;
	};

	/**
	 * Get full status info for a record
	 */
	const getRecordStatusInfo = (record) => {
		const status = getUnifiedStatus(record);
		return {
			status,
			label: getStatusLabel(status),
			severity: getStatusSeverity(status),
			description: getStatusDescription(status),
			isLegacy: !record.unified_status,
		};
	};

	/**
	 * All available status options for forms/filters
	 */
	const statusOptions = computed(() => {
		return Object.entries(statusConfig).map(([value, config]) => ({
			value,
			label: config.label,
			description: config.description,
		}));
	});

	/**
	 * Get CSS class for status badge
	 */
	const getStatusClass = (status) => {
		const severityMap = {
			success: 'bg-green-100 text-green-800',
			warning: 'bg-yellow-100 text-yellow-800',
			danger: 'bg-red-100 text-red-800',
			info: 'bg-blue-100 text-blue-800',
			secondary: 'bg-gray-100 text-gray-800',
		};
		const severity = getStatusSeverity(status);
		return severityMap[severity] || severityMap.secondary;
	};

	return {
		statusConfig,
		statusOptions,
		getStatusConfig,
		getStatusLabel,
		getStatusSeverity,
		getStatusDescription,
		getUnifiedStatus,
		getRecordStatusInfo,
		getStatusClass,
		mapLegacyStatus,
	};
};

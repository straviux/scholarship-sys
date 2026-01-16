import { ref, computed } from 'vue';

export const useScholarshipStatus = () => {
	// Status configuration - Updated for unified_status system
	const statusConfig = {
		pending: {
			label: 'Pending',
			severity: 'warning',
			color: '#F59E0B',
			bgColor: '#FEF3C7',
			textColor: '#92400E',
			description: 'Awaiting review',
		},
		approved: {
			label: 'Approved',
			severity: 'info',
			color: '#3B82F6',
			bgColor: '#DBEAFE',
			textColor: '#1E3A8A',
			description: 'Approved, waiting activation',
		},
		denied: {
			label: 'Denied',
			severity: 'danger',
			color: '#EF4444',
			bgColor: '#FEE2E2',
			textColor: '#7F1D1D',
			description: 'Application denied',
		},
		active: {
			label: 'Active',
			severity: 'success',
			color: '#10B981',
			bgColor: '#D1FAE5',
			textColor: '#065F46',
			description: 'Enrolled as scholar',
		},
		completed: {
			label: 'Completed',
			severity: 'secondary',
			color: '#6B7280',
			bgColor: '#F3F4F6',
			textColor: '#1F2937',
			description: 'Scholarship completed',
		},
		unknown: {
			label: 'Unknown',
			severity: 'secondary',
			color: '#9CA3AF',
			bgColor: '#F9FAFB',
			textColor: '#374151',
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
		// Denied takes priority
		if (approvalStatus === 'denied' || approvalStatus === 'declined') {
			return 'denied';
		}

		// Pending approval
		if (approvalStatus === 'pending' || approvalStatus === 'conditionally-approved') {
			return 'pending';
		}

		// Approved maps to approved state
		if (approvalStatus === 'approved' || approvalStatus === 'auto_approved') {
			if (scholarshipStatus === 3) {
				return 'completed';
			}
			return 'active';
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
		const config = statusConfig[status] || statusConfig.unknown;
		const bgColor = config.bgColor;
		const textColor = config.textColor;
		return `px-3 py-1 rounded-full text-sm font-semibold`
			+ ` border border-current` 
			+ ` style="background-color: ${bgColor}; color: ${textColor};"`;
	};

	/**
	 * Get inline style object for status badge
	 */
	const getStatusStyle = (status) => {
		const config = statusConfig[status] || statusConfig.unknown;
		return {
			backgroundColor: config.bgColor,
			color: config.textColor,
			borderColor: config.color,
		};
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
		getStatusStyle,
		mapLegacyStatus,
	};
};

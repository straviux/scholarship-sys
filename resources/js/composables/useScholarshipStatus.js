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
		interviewed: {
			label: 'Interviewed',
			severity: 'info',
			color: '#6366F1',
			bgColor: '#E0E7FF',
			textColor: '#3730A3',
			description: 'Interviewed, awaiting decision',
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
		withdrawn: {
			label: 'Withdrawn',
			severity: 'secondary',
			color: '#8B5CF6',
			bgColor: '#F3E8FF',
			textColor: '#5B21B6',
			description: 'Scholarship withdrawn',
		},
		loa: {
			label: 'LOA',
			severity: 'warning',
			color: '#D97706',
			bgColor: '#FEF08A',
			textColor: '#78350F',
			description: 'Leave of absence',
		},
		suspended: {
			label: 'Suspended',
			severity: 'danger',
			color: '#DC2626',
			bgColor: '#FECACA',
			textColor: '#7F1D1D',
			description: 'Scholarship suspended',
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
		// Valid status values
		const validStatuses = [
			'pending',
			'interviewed',
			'approved',
			'active',
			'completed',
			'denied',
			'withdrawn',
			'loa',
			'suspended',
			'unknown',
		];

		// Try new unified_status first
		if (record.unified_status) {
			// If it's a valid status, return it; otherwise return 'unknown'
			return validStatuses.includes(record.unified_status) ? record.unified_status : 'unknown';
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
		return (
			`px-3 py-1 rounded-full text-sm font-semibold` +
			` border border-current` +
			` style="background-color: ${bgColor}; color: ${textColor};"`
		);
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

/**
 * Shared helpers for report templates
 */

import { getSystemOptionLabel } from '@/composables/useSystemOptions';

export function formatName(item) {
	const lastName = String(item?.last_name || '').trim();
	const givenName = [item?.first_name, item?.middle_name, item?.extension_name].filter(Boolean).join(' ').trim();

	if (lastName && givenName) {
		return `${lastName}, ${givenName}`.toUpperCase();
	}

	if (lastName || givenName) {
		return (lastName || givenName).toUpperCase();
	}

	return item?.full_name
		? item.full_name.toUpperCase()
		: '—';
}

export function formatDate(date) {
	if (!date) return '—';
	const d = new Date(date);
	if (isNaN(d)) return '—';
	return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

export function formatGrantProvision(v) {
	return getSystemOptionLabel('grant_provision', v, '—');
}

export function isJpm(item) {
	return item.is_jpm_member || item.is_father_jpm || item.is_mother_jpm || item.is_guardian_jpm;
}

export function normalizeRequirementLabel(label) {
	const text = String(label || '').trim();

	if (!text) {
		return '';
	}

	const normalized = text.toLowerCase();

	if (/\bsolo\s+parents?\b/.test(normalized)) {
		return 'Solo Parent';
	}

	if (/\bpwd\b/.test(normalized) || /persons?\s+with\s+disabilit(?:y|ies)/.test(normalized)) {
		return 'PWD';
	}

	if (/\bendorsement\b/.test(normalized) || /\bothers?\b/.test(normalized)) {
		return 'Others';
	}

	return '';
}

export function formatSubmittedRequirements(record) {
	const requirements = Array.isArray(record?.submitted_requirements)
		? record.submitted_requirements
		: [];

	const normalizedRequirements = requirements
		.map(normalizeRequirementLabel)
		.filter(Boolean);

	if (String(record?.indigenous_group || '').trim()) {
		normalizedRequirements.push('Indigenous Group');
	}

	return [...new Set(normalizedRequirements)];
}

export function hasPriorityRequirement(record) {
	return formatSubmittedRequirements(record).some(label => ['Solo Parent', 'PWD'].includes(label));
}

export function hasReportRemarks(record) {
	return [
		record?.decline_reason,
		record?.remarks,
	].some(value => String(value || '').trim() !== '');
}

export function getPriorityReportSortBucket(record, highlightJpmMembers = false) {
	if (highlightJpmMembers && isJpm(record)) {
		return 0;
	}

	if (hasPriorityRequirement(record)) {
		return 1;
	}

	if (hasReportRemarks(record)) {
		return 2;
	}

	return 3;
}

export function getReportStatus(item) {
	return item.report_status || item.approval_status || item.unified_status || item.application_status || 'unknown';
}

const EMPTY_GROUP_LABELS = {
	unified_status: 'No Status',
	school: 'No School',
	program: 'No Program',
	course: 'No Course',
	year_level: 'No Year Level',
	municipality: 'No Municipality',
	grant_provision: 'No Grant Provision',
};

function normalizeGroupDisplayValue(value, emptyLabel) {
	if (value === null || value === undefined) {
		return emptyLabel;
	}

	const normalizedValue = typeof value === 'string' ? value.trim() : value;

	if (normalizedValue === '' || normalizedValue === '—') {
		return emptyLabel;
	}

	return value;
}

export function getGroupValue(item, groupByField) {
	const map = {
		unified_status: () => formatStatus(item.approval_status || item.unified_status || item.application_status),
		school: () => item.school_name,
		program: () => item.program_name,
		course: () => item.course_name,
		year_level: () => {
			const yl = item.year_level;
			if (yl === null || yl === undefined || String(yl).trim() === '') return null;
			return /^\d+(?:ST|ND|RD|TH)?$/i.test(String(yl)) ? `${yl} YEAR` : yl;
		},
		municipality: () => item.municipality,
		grant_provision: () => formatGrantProvision(item.grant_provision),
	};

	const rawValue = (map[groupByField] || (() => null))();
	return normalizeGroupDisplayValue(rawValue, EMPTY_GROUP_LABELS[groupByField] || 'No Group');
}

export function formatStatus(status) {
	const map = {
		pending: 'Pending',
		interviewed: 'Interviewed',
		approved: 'Approved',
		denied: 'Denied',
		active: 'Active',
		completed: 'Completed',
		graduated: 'Graduated',
		withdrawn: 'Withdrawn',
		loa: 'Leave of Absence',
		suspended: 'Suspended',
		endorsed: 'Endorsed',
	};
	return map[status] || status || '—';
}

export function getProfileReportLabel(status) {
	const map = {
		null: 'SCHOLARSHIP PROFILES',
		pending: 'PENDING APPLICANTS',
		interviewed: 'INTERVIEWED APPLICANTS',
		approved: 'APPROVED',
		approved_history: 'APPROVED',
		active: 'ACTIVE SCHOLARS',
		denied: 'DENIED',
		denied_history: 'DENIED',
		completed: 'COMPLETED SCHOLARS',
		withdrawn: 'WITHDRAWN SCHOLARS',
		loa: 'LEAVE OF ABSENCE',
		suspended: 'SUSPENDED SCHOLARS',
		endorsed: 'ENDORSED',
	};

	return map[status] || formatStatus(status)?.toUpperCase() || 'SCHOLARSHIP PROFILES';
}

export function getProfileReportTitle(status, reportType = 'list') {
	const label = getProfileReportLabel(status);
	return `${label} ${reportType === 'summary' ? 'SUMMARY REPORT' : 'REPORT'}`;
}

/**
 * Groups records by up to 3 levels
 * Returns: [{ key, label, records, subGroups }]
 */
export function groupRecords(records, groupBy, groupBySecondary, groupByTertiary) {
	if (!groupBy || groupBy === 'none') return null;

	const groups = {};
	for (const rec of records) {
		const key = getGroupValue(rec, groupBy);
		if (!groups[key]) groups[key] = [];
		groups[key].push(rec);
	}

	return Object.entries(groups)
		.sort(([a], [b]) => a.localeCompare(b))
		.map(([key, items]) => ({
			key,
			count: items.length,
			records: !groupBySecondary ? items : null,
			subGroups: groupBySecondary
				? groupRecords(items, groupBySecondary, groupByTertiary, null) || [
						{ key: 'All', count: items.length, records: items, subGroups: null },
				  ]
				: null,
		}));
}

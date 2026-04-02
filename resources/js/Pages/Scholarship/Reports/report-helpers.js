/**
 * Shared helpers for report templates
 */

export function formatName(item) {
	const parts = [item.last_name, item.first_name, item.middle_name].filter(Boolean);
	if (item.extension_name) parts.push(item.extension_name);
	return parts.length
		? parts.join(', ').toUpperCase()
		: item.full_name
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
	return v || '—';
}

export function isJpm(item) {
	return item.is_jpm_member || item.is_father_jpm || item.is_mother_jpm || item.is_guardian_jpm;
}

export function getGroupValue(item, groupByField) {
	const map = {
		unified_status: () => formatStatus(item.approval_status || item.unified_status),
		school: () => item.school_name || '—',
		program: () => item.program_name || '—',
		course: () => item.course_name || '—',
		year_level: () => item.year_level || '—',
		municipality: () => item.municipality || '—',
		grant_provision: () => item.grant_provision || '—',
	};
	return (map[groupByField] || (() => '—'))();
}

export function formatStatus(status) {
	const map = {
		pending: 'Pending',
		interviewed: 'Interviewed',
		approved: 'Approved',
		denied: 'Denied',
		active: 'Active',
		completed: 'Completed',
		withdrawn: 'Withdrawn',
		loa: 'Leave of Absence',
		suspended: 'Suspended',
	};
	return map[status] || status || '—';
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

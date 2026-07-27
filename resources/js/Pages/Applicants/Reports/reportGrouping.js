// Report grouping (shared by the PDF template and the Excel export).
//
// Supports two levels only: a main group and an optional sub-group. There is
// no custom group naming — the group value itself is used as the heading.

const GROUP_VALUE_GETTERS = {
    program: r => r.program_name,
    school: r => r.school_name,
    course: r => r.course_name,
    year_level: r => r.year_level,
    municipality: r => r.municipality,
    grant_provision: r => r.grant_provision,
};

// Selectable group fields (values line up with the prepared record fields).
export const GROUP_BY_OPTIONS = [
    { label: 'No Grouping', value: 'none' },
    { label: 'By Program', value: 'program' },
    { label: 'By School', value: 'school' },
    { label: 'By Course', value: 'course' },
    { label: 'By Year Level', value: 'year_level' },
    { label: 'By Municipality', value: 'municipality' },
    { label: 'By Grant Provision', value: 'grant_provision' },
];

export function groupValueFor(record, field) {
    const getter = GROUP_VALUE_GETTERS[field];
    const raw = getter ? getter(record) : null;
    const value = (raw ?? '').toString().trim();
    return value === '' || value === '—' ? '—' : value;
}

const compareGroupKeys = (a, b) => a.localeCompare(b, undefined, { sensitivity: 'base', numeric: true });

/**
 * Flatten records into an ordered list of render items, interleaving group
 * header markers for up to two levels. Items are shaped as either
 *   { type: 'group', level: 1 | 2, label, count }
 * or
 *   { type: 'record', record }
 * Record rows are re-numbered in printed order (overall_sequence) so the
 * report's # column always follows what the reader sees — grouping and
 * sorting change the display order, so the sequence cannot be baked in
 * upstream. With no grouping, original order is preserved.
 */
export function buildGroupedRenderItems(records, groupBy = 'none', groupBySub = 'none') {
    let displaySequence = 0;
    const recordItem = (record) => ({
        type: 'record',
        record: { ...record, overall_sequence: ++displaySequence },
    });

    const hasMain = Boolean(groupBy) && groupBy !== 'none';
    if (!hasMain) {
        return records.map(record => recordItem(record));
    }

    const hasSub = Boolean(groupBySub) && groupBySub !== 'none' && groupBySub !== groupBy;

    const bucket = (rows, field) => {
        const map = new Map();
        for (const rec of rows) {
            const key = groupValueFor(rec, field);
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(rec);
        }
        return [...map.keys()].sort(compareGroupKeys).map(key => ({ key, rows: map.get(key) }));
    };

    const items = [];
    for (const main of bucket(records, groupBy)) {
        items.push({ type: 'group', level: 1, label: main.key, count: main.rows.length });
        if (hasSub) {
            for (const sub of bucket(main.rows, groupBySub)) {
                items.push({ type: 'group', level: 2, label: sub.key, count: sub.rows.length });
                for (const rec of sub.rows) items.push(recordItem(rec));
            }
        } else {
            for (const rec of main.rows) items.push(recordItem(rec));
        }
    }
    return items;
}

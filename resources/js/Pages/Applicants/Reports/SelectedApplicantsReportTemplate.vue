<script setup>
import { computed } from 'vue';

const props = defineProps({
    records: {
        type: Array,
        default: () => [],
    },
    reportType: {
        type: String,
        default: 'list',
    },
    options: {
        type: Object,
        default: () => ({}),
    },
    generatedAt: {
        type: String,
        default: '',
    },
});

// 'none' = no column, 'values' = remarks with data, 'blank' = empty column to fill in by hand
const remarksMode = computed(() => props.options?.remarksMode ?? 'none');
const includeRemarks = computed(() => remarksMode.value === 'values' || remarksMode.value === 'blank');
const blankRemarks = computed(() => remarksMode.value === 'blank');

// Column definitions with relative weights. Widths are normalized to
// percentages that sum to exactly 100% so, with table-layout:fixed, columns
// stay identical on every printed page.
const listColumns = computed(() => {
    const cols = [
        { key: 'seq', label: '#', align: 'center', weight: 26 },
        { key: 'name', label: 'Name', align: 'left', weight: 160 },
        { key: 'muni', label: 'Municipality & Contact', align: 'left', weight: 110 },
        { key: 'program', label: 'Program', align: 'left', weight: 60 },
        { key: 'school', label: 'School', align: 'left', weight: 60 },
        { key: 'course', label: 'Course', align: 'left', weight: 70 },
        { key: 'level', label: 'Level', align: 'center', weight: 42 },
    ];

    cols.push({ key: 'date', label: 'Date Filed', align: 'center', weight: 64 });

    if (includeRemarks.value) {
        cols.push({ key: 'remarks', label: 'Remarks', align: 'left', weight: 140 });
    }

    const total = cols.reduce((sum, col) => sum + col.weight, 0);
    return cols.map(col => ({ ...col, width: `${(col.weight / total * 100).toFixed(4)}%` }));
});


const defaultReportTitle = computed(() => props.reportType === 'summary'
    ? 'SELECTED APPLICANTS SUMMARY REPORT'
    : 'SELECTED APPLICANTS REPORT');

// Custom title is authored in a rich-text editor (Quill) → HTML. When empty
// or unset, fall back to the default plain-text title.
const customTitleHtml = computed(() => {
    const html = (props.options?.customTitle ?? '').toString().trim();
    // Quill emits "<p><br></p>" for an empty editor — treat that as no title.
    const textOnly = html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
    return textOnly ? html : '';
});
const reportTitleHtml = computed(() => customTitleHtml.value || null);

const summaryCards = computed(() => [
    { label: 'Total Records', value: props.records.length },
    { label: 'Programs', value: new Set(props.records.map(record => record.program_name || '—')).size },
    { label: 'Schools', value: new Set(props.records.map(record => record.school_name || '—')).size },
    { label: 'Municipalities', value: new Set(props.records.map(record => record.municipality || '—')).size },
]);

function buildSummaryRows(key) {
    const counts = new Map();

    for (const record of props.records) {
        const label = record[key] || '—';
        counts.set(label, (counts.get(label) || 0) + 1);
    }

    return [...counts.entries()]
        .map(([label, count]) => ({ label, count }))
        .sort((left, right) => left.label.localeCompare(right.label, undefined, {
            sensitivity: 'base',
            numeric: true,
        }));
}

const summarySections = computed(() => [
    { title: 'Breakdown by Program', rows: buildSummaryRows('program_name') },
    { title: 'Breakdown by School', rows: buildSummaryRows('school_name') },
    { title: 'Breakdown by Course', rows: buildSummaryRows('course_name') },
    { title: 'Breakdown by Municipality', rows: buildSummaryRows('municipality') },
    { title: 'Breakdown by Year Level', rows: buildSummaryRows('year_level') },
].filter(section => section.rows.length > 0));

function formatApplicantName(record) {
    const base = [record.last_name, record.first_name].filter(Boolean).map(value => value.toUpperCase()).join(', ');
    const middle = record.middle_name ? ` ${record.middle_name}` : '';
    const extension = record.extension_name ? ` ${record.extension_name}` : '';
    return `${base}${middle}${extension}`.trim() || '—';
}
</script>

<template>
    <div style="font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 9pt; line-height: 1.4;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 12pt; border-bottom: 1.25pt solid #000; padding-bottom: 10pt; margin-bottom: 10pt;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo" style="width: 64pt; height: 64pt; object-fit: contain;" />
            <div style="flex: 0 1 auto; text-align: center;">
                <div style="font-size: 11pt; font-weight: 700;">Republic of the Philippines</div>
                <div style="font-size: 11pt; font-weight: 700;">Provincial Government of Palawan</div>
                <div style="font-size: 10pt;">YAKAP SA EDUKASYON</div>
                <div style="font-size: 10pt;">Scholarship Program</div>
                <div v-if="reportTitleHtml" style="font-size: 9pt; margin-top: 6pt; font-weight: 700; letter-spacing: 0.6pt;" v-html="reportTitleHtml"></div>
                <div v-else style="font-size: 9pt; margin-top: 6pt; font-weight: 700; letter-spacing: 0.6pt;">{{ defaultReportTitle }}</div>
            </div>
            <img src="/images/yakap-logo.svg" alt="YAKAP Logo" style="width: 64pt; height: 64pt; object-fit: contain;" />
        </div>

        <div v-if="records.length === 0" style="text-align: center; padding: 24pt 0; color: #6b7280; font-style: italic;">
            No applicants selected.
        </div>

        <template v-else-if="reportType === 'summary'">
            <div style="display: flex; flex-wrap: wrap; gap: 8pt; margin-bottom: 12pt;">
                <div v-for="card in summaryCards" :key="card.label" style="flex: 1 1 120pt; min-width: 120pt; border: 0.75pt solid #d1d5db; border-radius: 6pt; padding: 8pt 10pt; background: #f9fafb;">
                    <div style="font-size: 7pt; text-transform: uppercase; letter-spacing: 0.5pt; color: #6b7280;">{{ card.label }}</div>
                    <div style="font-size: 16pt; font-weight: 700; color: #111827; margin-top: 4pt;">{{ card.value }}</div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 10pt;">
                <div v-for="section in summarySections" :key="section.title" style="flex: 1 1 240pt; border: 0.75pt solid #d1d5db; border-radius: 6pt; overflow: hidden;">
                    <div style="padding: 6pt 8pt; background: #f3f4f6; font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4pt;">
                        {{ section.title }}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 5pt 8pt; border-bottom: 0.75pt solid #d1d5db; background: #fff;">Label</th>
                                <th style="text-align: right; padding: 5pt 8pt; border-bottom: 0.75pt solid #d1d5db; background: #fff; width: 64pt;">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in section.rows" :key="`${section.title}-${row.label}`">
                                <td style="padding: 4pt 8pt; border-bottom: 0.5pt solid #e5e7eb;">{{ row.label }}</td>
                                <td style="padding: 4pt 8pt; border-bottom: 0.5pt solid #e5e7eb; text-align: right; font-weight: 700;">{{ row.count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template v-else>
            <!-- Single table. Native browser print repeats the thead on every -->
            <!-- page and keeps table-layout:fixed columns identical across pages. -->
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8pt;">
                <colgroup>
                    <col v-for="col in listColumns" :key="col.key" :style="{ width: col.width }" />
                </colgroup>
                <thead style="display: table-header-group;">
                    <tr>
                        <th v-for="col in listColumns" :key="col.key"
                            :style="{ width: col.width, padding: '5pt 6pt', textAlign: col.align, border: '0.75pt solid #9ca3af', background: '#f3f4f6' }">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="record in records" :key="record.profile_id" style="break-inside: avoid; page-break-inside: avoid;">
                        <td style="padding: 4pt; border: 0.75pt solid #d1d5db; text-align: center; color: #374151;">{{ record.overall_sequence }}</td>
                        <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; vertical-align: middle;">
                            <div style="font-weight: 700;">{{ formatApplicantName(record) }}</div>
                        </td>
                        <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; vertical-align: top;">
                            <div>{{ record.municipality }}</div>
                            <div style="font-size: 7pt; color: #6b7280; margin-top: 2pt;">{{ record.contact_numbers }}</div>
                        </td>
                        <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ record.program_name }}</td>
                        <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ record.school_name }}</td>
                        <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ record.course_name }}</td>
                        <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; text-align: center;">{{ record.year_level }}</td>
                        <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; text-align: center;">{{ record.date_filed_label }}</td>
                        <td v-if="includeRemarks" style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ blankRemarks ? '' : record.remarks }}</td>
                    </tr>
                </tbody>
            </table>
        </template>
    </div>
</template>
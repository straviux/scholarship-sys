<script setup>
import { computed } from 'vue';
import { buildGroupedRenderItems } from './reportGrouping';

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

// Optional report columns / behaviours
const showProjected = computed(() => Boolean(props.options?.showProjected));
const highlightJpm = computed(() => Boolean(props.options?.highlightJpm));
const showGrantProvision = computed(() => Boolean(props.options?.showGrantProvision));

// Grouping — up to two levels (main group + optional sub-group). Group header
// rows are interleaved into the list body; the group value is the heading.
const groupBy = computed(() => props.options?.groupBy || 'none');
const groupBySub = computed(() => props.options?.groupBySub || 'none');
const renderItems = computed(() => buildGroupedRenderItems(props.records, groupBy.value, groupBySub.value));

function groupHeaderStyle(level) {
    const main = level === 1;
    return `padding: 4pt 6pt; border: 0.75pt solid #9ca3af; background: ${main ? '#e5e7eb' : '#f3f4f6'};`
        + ` font-weight: 700; font-size: ${main ? '8.5pt' : '8pt'}; text-transform: uppercase; letter-spacing: 0.3pt;`
        + (main ? '' : ' padding-left: 16pt;');
}

function jpmRowStyle(record) {
    return highlightJpm.value && record?.is_jpm ? 'background:#effdf4;' : '';
}
function jpmNameStyle(record) {
    return highlightJpm.value && record?.is_jpm ? 'color:#166534;font-weight:700;' : 'font-weight:700;';
}

// Column definitions with relative weights. Widths are normalized to
// percentages that sum to exactly 100% so, with table-layout:fixed, columns
// stay identical on every printed page.
const listColumns = computed(() => {
    const cols = [
        { key: 'seq', label: '#', align: 'center', weight: 26 },
        { key: 'name', label: 'Name', align: 'left', weight: 160 },
        { key: 'muni', label: 'Municipality', align: 'left', weight: 65 },
        { key: 'contact', label: 'Contact No.', align: 'left', weight: 55 },
        { key: 'school', label: 'School', align: 'left', weight: 60 },
        { key: 'course', label: 'Course', align: 'left', weight: 70 },
        { key: 'level', label: 'Year', align: 'center', weight: 42 },
    ];

    if (showGrantProvision.value) {
        cols.push({ key: 'grant_provision', label: 'Grant Provision', align: 'left', weight: 90 });
    }

    cols.push({ key: 'date', label: 'Date Filed', align: 'center', weight: 64 });

    if (showProjected.value) {
        cols.push({ key: 'projected_expense', label: 'Projected Expense', align: 'right', weight: 80 });
        cols.push({ key: 'projected_terms', label: 'Proj. Terms', align: 'center', weight: 48 });
        cols.push({ key: 'projected_completion', label: 'Proj. Completion', align: 'center', weight: 60 });
    }

    if (includeRemarks.value) {
        cols.push({ key: 'remarks', label: 'Remarks', align: 'left', weight: 140 });
    }

    const total = cols.reduce((sum, col) => sum + col.weight, 0);
    return cols.map(col => ({ ...col, width: `${(col.weight / total * 100).toFixed(4)}%` }));
});


const defaultReportTitle = computed(() => props.reportType === 'summary'
    ? 'SELECTED APPLICANTS SUMMARY REPORT'
    : 'SELECTED APPLICANTS REPORT');

// Fixed (non-customizable) report signatories.
const SIGNATORIES = {
    preparedBy: 'NUR-AINA S. IBRAHIM',
    preparedByPosition: 'Program Manager',
    preparedByOffice: 'YAKAP sa Edukasyon',
    approvedBy: 'AMY ROA ALVAREZ',
    approvedByPosition: 'Governor',
};
const showSignatories = computed(() => Boolean(props.options?.showSignatories));

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
                <div style="font-size: 10pt;">Puerto Princesa City</div>
                <div v-if="reportTitleHtml" style="font-size: 9pt; margin-top: 12pt; font-weight: 700; letter-spacing: 0.6pt;" v-html="reportTitleHtml"></div>
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
                    <template v-for="(item, idx) in renderItems"
                        :key="item.type === 'record' ? `r-${item.record.profile_id}` : `g-${idx}`">
                        <!-- Group header row -->
                        <tr v-if="item.type === 'group'" style="break-inside: avoid; page-break-inside: avoid;">
                            <td :colspan="listColumns.length" :style="groupHeaderStyle(item.level)">
                                {{ item.label }} <span style="font-weight: 400; opacity: 0.65;">({{ item.count }})</span>
                            </td>
                        </tr>
                        <!-- Record row -->
                        <tr v-else :style="`break-inside: avoid; page-break-inside: avoid;${jpmRowStyle(item.record)}`">
                            <td style="padding: 4pt; border: 0.75pt solid #d1d5db; text-align: center; color: #374151;">{{ item.record.overall_sequence }}</td>
                            <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; vertical-align: middle;">
                                <div :style="jpmNameStyle(item.record)">{{ formatApplicantName(item.record) }}</div>
                            </td>
                            <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; vertical-align: top;">
                                <div>{{ item.record.municipality }}</div>
                            </td>
                            <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; vertical-align: top;">
                                <div>{{ item.record.contact_numbers }}</div>
                            </td>
                            <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ item.record.school_name }}</td>
                            <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ item.record.course_name }}</td>
                            <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; text-align: center;">{{ item.record.year_level }}</td>
                            <td v-if="showGrantProvision" style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ item.record.grant_provision }}</td>
                            <td style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; text-align: center;">{{ item.record.date_filed_label }}</td>
                            <td v-if="showProjected" style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; text-align: right;">{{ item.record.projected_expense }}</td>
                            <td v-if="showProjected" style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; text-align: center;">{{ item.record.projected_terms }}</td>
                            <td v-if="showProjected" style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db; text-align: center;">{{ item.record.projected_completion }}</td>
                            <td v-if="includeRemarks" style="padding: 4pt 6pt; border: 0.75pt solid #d1d5db;">{{ blankRemarks ? '' : item.record.remarks }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>

        <!-- Fixed signatories -->
        <div v-if="showSignatories"
            style="margin-top: 28pt; display: flex; justify-content: space-between; gap: 24pt; font-size: 9pt; break-inside: avoid; page-break-inside: avoid;">
            <div style="flex: 1; max-width: 50%; margin-left: 40pt;">
                <div style="font-weight: 700;">Prepared by:</div>
                <div style="margin-top: 40pt; text-align: center; width: 140pt;">
                    <div style="font-weight: 700; border-bottom: 1px solid #000; padding-bottom: 2pt; text-transform: uppercase;">
                        {{ SIGNATORIES.preparedBy }}
                    </div>
                    <div style="margin-top: 4pt;">{{ SIGNATORIES.preparedByPosition }}</div>
                    <div>{{ SIGNATORIES.preparedByOffice }}</div>
                </div>
            </div>
            <div style="flex: 1; max-width: 50%; margin-left: auto;">
                <div style="font-weight: 700;">Approved by:</div>
                <div style="margin-top: 40pt; text-align: center; width: 140pt;">
                    <div style="font-weight: 700; border-bottom: 1px solid #000; padding-bottom: 2pt; text-transform: uppercase;">
                        {{ SIGNATORIES.approvedBy }}
                    </div>
                    <div style="margin-top: 4pt;">{{ SIGNATORIES.approvedByPosition }}</div>
                </div>
                <div style="margin-top: 28pt; text-align: center; width: 140pt; border-top: 1px solid #000; padding-top: 2pt;">
                    Date
                </div>
            </div>
        </div>
    </div>
</template>
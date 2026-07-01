<script setup>
import { computed } from 'vue';
import { formatDate, formatName, formatStatus, formatSubmittedRequirements, getReportStatus, isJpm } from './report-helpers';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    groupHeaders: { type: Array, default: () => [] },
    showAddress: { type: Boolean, default: true },
    showContactNumber: { type: Boolean, default: false },
    showDateFiled: { type: Boolean, default: true },
    showProgram: { type: Boolean, default: true },
    showSchool: { type: Boolean, default: true },
    showCourse: { type: Boolean, default: true },
    showRemarks: { type: Boolean, default: true },
    showRequirements: { type: Boolean, default: false },
});

const TH = 'border:1px solid #000;padding:3px 2px;font-weight:700;font-size:7px;line-height:1.15;text-transform:uppercase;text-align:center;background:#f0f0f0;word-break:break-word;overflow-wrap:anywhere;vertical-align:middle;';
const TD = 'border:1px solid #000;padding:3px 3px;font-size:7px;line-height:1.2;vertical-align:middle;word-break:break-word;overflow-wrap:anywhere;';
const GROUP_HEADER_CELL_BASE = 'border:1px solid #000;text-align:left;vertical-align:middle;';

const groupHeaderRows = computed(() => (Array.isArray(props.groupHeaders) ? props.groupHeaders : [])
    .filter(Boolean)
    .map((header, index) => ({
        ...header,
        level: Number.isInteger(header?.level) ? header.level : index,
        rowKey: header?.rowKey || `${index}-${header?.key || header?.label || 'group'}`,
    })));

const selectedStatus = computed(() => props.options?.selectedStatus ?? null);
const highlightJpmMembers = computed(() => props.options?.enableJpmHighlighting === true);
const showSequenceNumbers = computed(() => props.options?.showSequenceNumbers !== false);
const includeProjectedExpense = computed(() => props.options?.includeProjectedExpense !== false);
const activeGroupFields = computed(() => new Set([
    props.options?.groupBy,
    props.options?.groupBySecondary,
    props.options?.groupByTertiary,
].filter(value => value && value !== 'none')));
const hiddenColumns = computed(() => ({
    address: !props.showAddress,
    contact_number: !props.showContactNumber,
    date_filed: !props.showDateFiled,
    program: !props.showProgram || !!props.filters?.Program || activeGroupFields.value.has('program'),
    school: !props.showSchool || !!props.filters?.School || activeGroupFields.value.has('school'),
    course: !props.showCourse || !!props.filters?.Course || activeGroupFields.value.has('course'),
    remarks: false, // always show the remarks column
    year_level: !!props.filters?.['Year Level'] || activeGroupFields.value.has('year_level'),
    report_status: activeGroupFields.value.has('unified_status'),
}));

const projectedColumns = computed(() => {
    if (!includeProjectedExpense.value) {
        return [];
    }

    return [
        { key: 'projected_term_count', label: 'Terms', width: '4%' },
        { key: 'projected_total_expense', label: 'Expense', width: '8%' },
        { key: 'projected_completion_year', label: 'Completion', width: '6%' },
    ];
});

const singleColumns = computed(() => {
    const columns = [];

    if (showSequenceNumbers.value) {
        columns.push({ key: 'sequence', label: '#', width: '2%', align: 'center' });
    }

    columns.push({ key: 'name', label: 'Name', width: props.showContactNumber ? '10%' : '12%' });

    if (!hiddenColumns.value.contact_number) {
        columns.push({ key: 'contact_number', label: 'Contact Number', width: '7%', align: 'center' });
    }

    if (!hiddenColumns.value.address) {
        columns.push({ key: 'address_location', label: 'Address', width: '8%' });
    }

    if (!hiddenColumns.value.program) {
        columns.push({ key: 'program_name', label: 'Program', width: '5%', align: 'center' });
    }

    if (!hiddenColumns.value.school) {
        columns.push({ key: 'school_name', label: 'School', width: '9%' });
    }

    if (!hiddenColumns.value.course) {
        columns.push({ key: 'course_name', label: 'Course', width: '10%' });
    }

    if (!hiddenColumns.value.year_level) {
        columns.push({ key: 'year_level', label: 'Year Level', width: '4%', align: 'center' });
    }

    columns.push({ key: 'term_academic_year', label: 'Term / Academic Year', width: '7%', align: 'center' });

    if (!hiddenColumns.value.remarks) {
        columns.push({ key: 'remarks_summary', label: 'Remarks', width: '10%' });
    }

    if (selectedStatus.value === null && !hiddenColumns.value.report_status) {
        columns.push({ key: 'report_status', label: 'Status', width: '6%', align: 'center' });
    }

    return columns;
});

const detailConfig = computed(() => {
    switch (selectedStatus.value) {
        case 'pending':
            return {
                label: 'Application',
                columns: hiddenColumns.value.date_filed ? [] : [
                    { key: 'date_filed', label: 'Date Filed', width: '5%', align: 'center' },
                ],
            };
        case 'interviewed':
            return {
                label: 'Interview',
                columns: [
                    { key: 'interviewed_at', label: 'Date', width: '7%', align: 'center' },
                    { key: 'interviewer_name', label: 'By', width: '8%', align: 'center' },
                    { key: 'endorsed_by', label: 'Endorsed By', width: '8%', align: 'center' },
                ],
            };
        case 'approved':
        case 'approved_history':
            return {
                label: 'Approved',
                columns: [
                    { key: 'date_approved', label: 'Date Approved', width: '7%', align: 'center' },
                ],
            };
        case 'active':
            return {
                label: 'Scholarship',
                columns: [
                    { key: 'date_approved', label: 'Date Approved', width: '7%', align: 'center' },
                ],
            };
        case 'denied':
        case 'denied_history':
            return {
                label: 'Denied',
                columns: [
                    { key: 'date_denied', label: 'Date', width: '7%', align: 'center' },
                ],
            };
        case null:
            return {
                label: 'Status',
                columns: [
                    { key: 'report_date', label: 'Key Date', width: '7%', align: 'center' },
                ],
            };
        default:
            return {
                label: 'Scholarship',
                columns: [
                    { key: 'date_approved', label: 'Recorded Date', width: '7%', align: 'center' },
                ],
            };
    }
});

const leadingSingleColumns = computed(() => singleColumns.value.filter(column => column.key !== 'remarks_summary'));
const trailingSingleColumns = computed(() => singleColumns.value.filter(column => column.key === 'remarks_summary'));
const inlineDetailColumns = computed(() => selectedStatus.value === 'pending' ? detailConfig.value.columns : []);
const groupedDetailColumns = computed(() => selectedStatus.value === 'pending' ? [] : detailConfig.value.columns);
const allColumns = computed(() => [
    ...leadingSingleColumns.value,
    ...projectedColumns.value,
    ...detailConfig.value.columns,
    ...trailingSingleColumns.value,
]);
const hasSubHeaderRow = computed(() => projectedColumns.value.length > 0 || groupedDetailColumns.value.length > 0);

function parseGrantProvision(value) {
    if (!value) {
        return { name: '—', amount: '' };
    }

    const formattedValue = typeof value === 'string' && !value.includes('_')
        ? value
        : value
            .toString()
            .split('_')
            .map(part => (part ? part.charAt(0).toUpperCase() + part.slice(1) : ''))
            .join(' ');

    const normalizedValue = formattedValue.replace(/\s{2,}/g, ' ').trim();
    const amountMatch = normalizedValue.match(/^(.*?)(?:\s*\((?:PHP\s*)?([^()]+)\))$/i);

    if (!amountMatch) {
        return {
            name: normalizedValue.replace(/\bPHP\b/g, '').replace(/\s{2,}/g, ' ').trim(),
            amount: '',
        };
    }

    return {
        name: amountMatch[1].trim(),
        amount: amountMatch[2].replace(/\bPHP\b/g, '').replace(/\s{2,}/g, ' ').trim(),
    };
}

function isTrimesterTerm(term) {
    return typeof term === 'string' && term.toLowerCase().includes('trimester');
}

function fmtGrantAmount(value) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
}

function fmtGrantProvisionName(record) {
    return parseGrantProvision(record?.grant_provision_label || record?.grant_provision).name;
}

function fmtGrantProvisionAmount(record) {
    const rawAmount = parseGrantProvision(record?.grant_provision_label || record?.grant_provision).amount;

    if (!rawAmount) {
        return '';
    }

    const numericAmount = Number(rawAmount.toString().replace(/,/g, ''));

    if (!Number.isFinite(numericAmount)) {
        return rawAmount;
    }

    const adjustedAmount = isTrimesterTerm(record?.term)
        ? (numericAmount * 2) / 3
        : numericAmount;

    return fmtGrantAmount(adjustedAmount);
}

function fmtCurrency(value) {
    if (value === null || value === undefined || value === '') {
        return 'Not configured';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
}

function upper(value) {
    return value ? value.toString().toUpperCase() : '—';
}

function formatAddress(record) {
    const parts = [record?.barangay, record?.municipality]
        .map(value => value?.toString().trim())
        .filter(Boolean);

    return parts.length ? parts.join(', ') : '—';
}

function formatRemarks(record) {
    const showRemarksText = props.showRemarks;
    const showRequirementsText = props.showRequirements;
    const sections = [];

    if (showRemarksText) {
        const textRemarks = [...new Set([
            record?.decline_reason,
            record?.remarks,
        ].map(value => String(value || '').trim()).filter(Boolean))];
        if (textRemarks.length) {
            sections.push(textRemarks.join('\n'));
        }
    }

    if (showRequirementsText) {
        const requirementLabels = formatSubmittedRequirements(record);
        if (requirementLabels.length) {
            sections.push(`Requirements: ${requirementLabels.join(', ')}`);
        }
    }

    return sections.join('\n');
}

function formatSafeHtml(value) {
    return String(value ?? '')
        .replace(/\r\n/g, '\n')
        .replace(/&nbsp;|&#160;|&ensp;|&emsp;|\u00a0/gi, ' ')
        .replace(/<\s*(p|div)(?:\s[^>]*)?>/gi, '')
        .replace(/<\s*\/(p|div)\s*>/gi, '<br>')
        .replace(/<\s*li(?:\s[^>]*)?>/gi, '&bull; ')
        .replace(/<\s*\/li\s*>/gi, '<br>')
        .replace(/<br\s*\/?>/gi, '<br>')
        .replace(/\n/g, '<br>')
        .replace(/(<br>\s*){3,}/gi, '<br><br>')
        .replace(/^(?:\s*<br>)+/i, '')
        .replace(/(?:<br>\s*)+$/i, '')
        .trim();
}

function valueStyle(column) {
    const align = column.align ? `text-align:${column.align};` : '';
    const emphasis = column.key === 'name' ? 'font-weight:600;' : '';
    const nameSize = (column.key !== 'sequence' && column.key !== 'remarks_summary') ? 'font-size:10px;line-height:1.3;' : '';
    const nowrap = ['contact_number', 'date_filed', 'date_approved', 'date_denied', 'interviewed_at', 'report_date'].includes(column.key)
        ? 'white-space:nowrap;'
        : '';
    const uppercase = column.key === 'address_location' ? 'text-transform:uppercase;' : '';
    const multiline = column.key === 'remarks_summary' ? 'white-space:pre-line;' : '';

    return `${TD}${align}${emphasis}${nameSize}${nowrap}${uppercase}${multiline}`;
}

function groupHeaderCellStyle(level = 0) {
    switch (level) {
        case 1:
            return `${GROUP_HEADER_CELL_BASE}padding:3px 6px 3px 16px;font-weight:600;font-size:8px;background:#ececec;`;
        case 2:
            return `${GROUP_HEADER_CELL_BASE}padding:2px 6px 2px 28px;font-weight:600;font-size:8px;background:#f5f5f5;`;
        default:
            return `${GROUP_HEADER_CELL_BASE}padding:4px 6px;font-weight:700;font-size:9px;background:#ddd;`;
    }
}

function groupHeaderMetaStyle(level = 0) {
    const fontWeight = level === 0 ? '600' : '500';
    return `font-size:7px;color:#555;font-weight:${fontWeight};text-transform:none;white-space:nowrap;`;
}

function formatGroupHeaderSummary(header) {
    const count = Number(header?.count);
    const parts = [];

    if (Number.isFinite(count)) {
        parts.push(`${count} record${count !== 1 ? 's' : ''}`);
    }

    if (header?.projectedText) {
        parts.push(`${header.projectedText} projected`);
    }

    return parts.join(' | ');
}

function shouldHighlightRecord(record) {
    return highlightJpmMembers.value && isJpm(record);
}

function cellValue(record, column) {
    switch (column.key) {
        case 'name':
            return formatName(record);
        case 'contact_number':
            return record?.contact_no || 'â€”';
        case 'address_location':
            return formatAddress(record);
        case 'program_name':
        case 'school_name':
        case 'course_name':
        case 'year_level':
        case 'term':
        case 'academic_year':
            return record[column.key] || '—';
        case 'report_status':
            return formatStatus(getReportStatus(record));
        case 'projected_term_count':
            return record.projected_term_count ?? 'Not configured';
        case 'projected_total_expense':
            return fmtCurrency(record.projected_total_expense);
        case 'projected_completion_year':
            return record.projected_completion_year ?? 'Not configured';
        case 'date_filed':
        case 'date_approved':
        case 'date_denied':
        case 'interviewed_at':
        case 'report_date':
            return formatDate(record[column.key]);
        case 'interviewer_name':
        case 'endorsed_by':
            return upper(record[column.key]);
        case 'previous_status':
            return record.previous_status ? formatStatus(record.previous_status) : '—';
        case 'decline_reason':
            return record.decline_reason || record.remarks || '—';
        case 'remarks_summary':
            return formatRemarks(record);
        default:
            return record[column.key] || '—';
    }
}
</script>

<template>
    <table style="width:100%;border-collapse:collapse;font-size:7pt;table-layout:fixed;">
        <colgroup>
            <col v-for="column in allColumns" :key="column.key" :style="`width:${column.width};`" />
        </colgroup>
        <thead>
            <tr v-for="header in groupHeaderRows" :key="header.rowKey">
                <th :colspan="allColumns.length" :style="groupHeaderCellStyle(header.level)">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <span>{{ header.label }}</span>
                        <span v-if="formatGroupHeaderSummary(header)" :style="groupHeaderMetaStyle(header.level)">
                            {{ formatGroupHeaderSummary(header) }}
                        </span>
                    </div>
                </th>
            </tr>
            <tr>
                <th v-for="column in leadingSingleColumns" :key="`top-${column.key}`" :style="TH"
                    :rowspan="hasSubHeaderRow ? 2 : 1">
                    {{ column.label }}
                </th>
                <th v-for="column in inlineDetailColumns" :key="`top-detail-${column.key}`" :style="TH"
                    :rowspan="hasSubHeaderRow ? 2 : 1">
                    {{ column.label }}
                </th>
                <th v-if="projectedColumns.length" :style="TH" :colspan="projectedColumns.length">Projected</th>
                <th v-if="groupedDetailColumns.length" :style="TH" :colspan="groupedDetailColumns.length">{{
                    detailConfig.label }}</th>
                <th v-for="column in trailingSingleColumns" :key="`top-trailing-${column.key}`" :style="TH"
                    :rowspan="hasSubHeaderRow ? 2 : 1">
                    {{ column.label }}
                </th>
            </tr>
            <tr v-if="hasSubHeaderRow">
                <th v-for="column in projectedColumns" :key="`projected-${column.key}`" :style="TH">
                    {{ column.label }}
                </th>
                <th v-for="column in groupedDetailColumns" :key="`detail-${column.key}`" :style="TH">
                    {{ column.label }}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(record, index) in records" :key="record.id || `${record.profile_id}-${index}`"
                :class="{ 'jpm-row': shouldHighlightRecord(record) }">
                <td v-for="column in leadingSingleColumns" :key="`single-${column.key}-${index}`"
                    :style="valueStyle(column)">
                    <template v-if="column.key === 'sequence'">
                        {{ index + 1 }}
                    </template>
                    <template v-else-if="column.key === 'term_academic_year'">
                        <div>{{ record.term || '—' }}</div>
                        <div style="margin-top:2px;font-size:6px;line-height:1.1;">
                            {{ record.academic_year || '—' }}
                        </div>
                    </template>
                    <template v-else-if="column.key === 'remarks_summary'">
                        <span v-safe-html="formatSafeHtml(cellValue(record, column))"></span>
                    </template>
                    <template v-else>
                        <span v-if="column.key !== 'report_status'">{{ cellValue(record, column) }}</span>
                        <strong v-else>{{ cellValue(record, column) }}</strong>
                    </template>
                </td>
                <td v-for="column in projectedColumns" :key="`projected-${column.key}-${index}`"
                    :style="valueStyle(column)">
                    {{ cellValue(record, column) }}
                </td>
                <td v-for="column in detailConfig.columns" :key="`detail-${column.key}-${index}`"
                    :style="valueStyle(column)">
                    <span v-if="column.key !== 'decline_reason'">{{ cellValue(record, column) }}</span>
                    <span v-else v-safe-html="formatSafeHtml(cellValue(record, column))"></span>
                </td>
                <td v-for="column in trailingSingleColumns" :key="`single-trailing-${column.key}-${index}`"
                    :style="valueStyle(column)">
                    <span v-safe-html="formatSafeHtml(cellValue(record, column))"></span>
                </td>
            </tr>
        </tbody>
    </table>
</template>

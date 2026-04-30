<script setup>
import { computed } from 'vue';
import { formatDate, formatName, formatStatus, getReportStatus } from './report-helpers';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
});

const TH = 'border:1px solid #000;padding:3px 2px;font-weight:700;font-size:7px;line-height:1.15;text-transform:uppercase;text-align:center;background:#f0f0f0;word-break:break-word;overflow-wrap:anywhere;vertical-align:middle;';
const TD = 'border:1px solid #000;padding:3px 3px;font-size:7px;line-height:1.2;vertical-align:middle;word-break:break-word;overflow-wrap:anywhere;';

const selectedStatus = computed(() => props.options?.selectedStatus ?? null);
const showSequenceNumbers = computed(() => props.options?.showSequenceNumbers !== false);
const useInlinePendingSequence = computed(() => showSequenceNumbers.value && selectedStatus.value === 'pending');
const includeGrantProvision = computed(() => props.options?.includeGrantProvision !== false);
const includeRemarks = computed(() => props.options?.includeRemarks !== false);
const hiddenColumns = computed(() => ({
    program: !!props.filters?.Program,
    school: !!props.filters?.School,
    course: !!props.filters?.Course,
    year_level: !!props.filters?.['Year Level'],
}));

const projectedColumns = [
    { key: 'projected_term_count', label: 'Terms', width: '4%' },
    { key: 'projected_total_expense', label: 'Expense', width: '8%' },
    { key: 'projected_completion_year', label: 'Completion', width: '6%' },
];

const singleColumns = computed(() => {
    const columns = [];

    if (showSequenceNumbers.value && !useInlinePendingSequence.value) {
        columns.push({ key: 'sequence', label: '#', width: '3%', align: 'center' });
    }

    columns.push({ key: 'name', label: 'Name', width: '14%' });

    if (!hiddenColumns.value.program) {
        columns.push({ key: 'program_name', label: 'Program', width: '6%', align: 'center' });
    }

    if (!hiddenColumns.value.school) {
        columns.push({ key: 'school_name', label: 'School', width: '11%' });
    }

    if (!hiddenColumns.value.course) {
        columns.push({ key: 'course_name', label: 'Course', width: '12%' });
    }

    if (!hiddenColumns.value.year_level) {
        columns.push({ key: 'year_level', label: 'Year', width: '4%', align: 'center' });
    }

    columns.push({ key: 'term_academic_year', label: 'Term / Academic Year', width: '8%', align: 'center' });

    if (includeGrantProvision.value) {
        columns.push({ key: 'grant', label: 'Grant', width: '9%' });
    }

    if (selectedStatus.value === null) {
        columns.push({ key: 'report_status', label: 'Status', width: '7%', align: 'center' });
    }

    return columns;
});

const detailConfig = computed(() => {
    switch (selectedStatus.value) {
        case 'pending':
            return {
                label: 'Application',
                columns: [
                    { key: 'date_filed', label: 'Date Filed', width: '7%', align: 'center' },
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
                    ...(includeRemarks.value ? [{ key: 'decline_reason', label: 'Reason / Remarks', width: '12%' }] : []),
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

const allColumns = computed(() => [
    ...singleColumns.value,
    ...projectedColumns,
    ...detailConfig.value.columns,
]);

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

function valueStyle(column) {
    const align = column.align ? `text-align:${column.align};` : '';
    const emphasis = column.key === 'name' ? 'font-weight:600;' : '';
    const nowrap = ['date_filed', 'date_approved', 'date_denied', 'interviewed_at', 'report_date'].includes(column.key)
        ? 'white-space:nowrap;'
        : '';

    return `${TD}${align}${emphasis}${nowrap}`;
}

function cellValue(record, column) {
    switch (column.key) {
        case 'name':
            return formatName(record);
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
            <tr>
                <th v-for="column in singleColumns" :key="`top-${column.key}`" :style="TH" rowspan="2">
                    {{ column.label }}
                </th>
                <th :style="TH" :colspan="projectedColumns.length">Projected</th>
                <th :style="TH" :colspan="detailConfig.columns.length">{{ detailConfig.label }}</th>
            </tr>
            <tr>
                <th v-for="column in projectedColumns" :key="`projected-${column.key}`" :style="TH">
                    {{ column.label }}
                </th>
                <th v-for="column in detailConfig.columns" :key="`detail-${column.key}`" :style="TH">
                    {{ column.label }}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(record, index) in records" :key="record.id || `${record.profile_id}-${index}`">
                <td v-for="column in singleColumns" :key="`single-${column.key}-${index}`" :style="valueStyle(column)">
                    <template v-if="column.key === 'sequence'">
                        {{ index + 1 }}
                    </template>
                    <template v-else-if="column.key === 'grant'">
                        <div>{{ fmtGrantProvisionName(record) }}</div>
                        <div v-if="fmtGrantProvisionAmount(record)"
                            style="margin-top:2px;font-size:6px;line-height:1.1;">
                            {{ fmtGrantProvisionAmount(record) }}
                        </div>
                    </template>
                    <template v-else-if="column.key === 'term_academic_year'">
                        <div>{{ record.term || '—' }}</div>
                        <div style="margin-top:2px;font-size:6px;line-height:1.1;">
                            {{ record.academic_year || '—' }}
                        </div>
                    </template>
                    <template v-else>
                        <span v-if="column.key !== 'report_status'">{{ cellValue(record, column) }}</span>
                        <strong v-else>{{ cellValue(record, column) }}</strong>
                        <div v-if="column.key === 'name' && useInlinePendingSequence"
                            style="margin-top:2px;font-size:6px;line-height:1.1;font-weight:500;">
                            {{ index + 1 }}
                        </div>
                    </template>
                </td>
                <td v-for="column in projectedColumns" :key="`projected-${column.key}-${index}`"
                    :style="valueStyle(column)">
                    {{ cellValue(record, column) }}
                </td>
                <td v-for="column in detailConfig.columns" :key="`detail-${column.key}-${index}`"
                    :style="valueStyle(column)">
                    <span v-if="column.key !== 'decline_reason'">{{ cellValue(record, column) }}</span>
                    <span v-else style="white-space: pre-line;">{{ cellValue(record, column) }}</span>
                </td>
            </tr>
        </tbody>
    </table>
</template>
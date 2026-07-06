<script setup>
import { computed, ref, watch } from 'vue';
import { formatDate, formatName } from './report-helpers';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    groupHeaders: { type: Array, default: () => [] },
    defaultAmount: { type: [Number, String], default: null },
});

// Editable amounts (keyed by record index)
const amounts = ref({});

// Pre-fill amounts with defaultAmount whenever records or defaultAmount change
watch([() => props.records, () => props.defaultAmount], () => {
    if (props.defaultAmount != null && props.defaultAmount !== '') {
        const newAmounts = { ...amounts.value };
        props.records.forEach((rec, index) => {
            const key = rec.profile_id ?? index;
            if (!(key in newAmounts)) {
                newAmounts[key] = String(props.defaultAmount);
            }
        });
        amounts.value = newAmounts;
    }
}, { immediate: true });

function getAmountKey(record, index) {
    return record.profile_id ?? index;
}

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

const columns = computed(() => [
    { key: 'sequence', label: '#', width: '2%', align: 'center' },
    { key: 'name', label: 'Name', width: '16%' },
    { key: 'contact_number', label: 'Contact No.', width: '8%', align: 'center' },
    { key: 'address_location', label: 'Address', width: '13%' },
    { key: 'school_name', label: 'School', width: '15%' },
    { key: 'course_name', label: 'Course', width: '13%' },
    { key: 'no_of_days', label: 'Required No. of Days', width: '8%', align: 'center' },
    { key: 'no_of_hours', label: 'Required No. of Hours', width: '8%', align: 'center' },
    { key: 'amount', label: 'Amount', width: '8%', align: 'right' },
    { key: 'remarks_summary', label: 'Remarks', width: '11%' },
]);

const totalAmount = computed(() => {
    let total = 0;
    props.records.forEach((rec) => {
        const key = getAmountKey(rec);
        const val = parseFloat(amounts.value[key]) || 0;
        total += val;
    });
    return total;
});

function formatAddress(record) {
    const parts = [record?.barangay, record?.municipality]
        .map(value => value?.toString().trim())
        .filter(Boolean);
    return parts.length ? parts.join(', ') : '—';
}

function cellValue(record, column) {
    switch (column.key) {
        case 'name': return formatName(record);
        case 'contact_number': return record?.contact_no || '';
        case 'address_location': return formatAddress(record);
        case 'school_name': return record?.school_name || record?.school || '—';
        case 'course_name': return record?.course_name || record?.course || '—';
        case 'no_of_days': return record?.no_of_days ?? record?.no_of_days_alternative ?? '';
        case 'no_of_hours': return record?.no_of_hours ?? record?.no_of_hours_alternative ?? '';
        case 'amount': {
            const raw = amounts.value[getAmountKey(record)] || '';
            const num = parseFloat(raw);
            return isNaN(num) ? raw : '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        case 'remarks_summary': return record?.remarks || record?.decline_reason || '';
        default: return record[column.key] || '—';
    }
}

function valueStyle(column) {
    const align = column.align ? `text-align:${column.align};` : '';
    const emphasis = column.key === 'name' ? 'font-weight:600;' : '';
    const nameSize = column.key !== 'sequence' ? 'font-size:10px;line-height:1.3;' : '';
    const nowrap = [].includes(column.key) ? 'white-space:nowrap;' : '';
    const uppercase = column.key === 'address_location' ? 'text-transform:uppercase;' : '';

    return `${TD}${align}${emphasis}${nameSize}${nowrap}${uppercase}`;
}

function groupHeaderCellStyle(level = 0) {
    switch (level) {
        case 1: return `${GROUP_HEADER_CELL_BASE}padding:3px 6px 3px 16px;font-weight:600;font-size:8px;background:#ececec;`;
        case 2: return `${GROUP_HEADER_CELL_BASE}padding:2px 6px 2px 28px;font-weight:600;font-size:8px;background:#f5f5f5;`;
        default: return `${GROUP_HEADER_CELL_BASE}padding:4px 6px;font-weight:700;font-size:9px;background:#ddd;`;
    }
}

function groupHeaderMetaStyle(level = 0) {
    return `font-size:7px;color:#555;font-weight:${level === 0 ? '600' : '500'};text-transform:none;white-space:nowrap;`;
}

function formatGroupHeaderSummary(header) {
    const count = Number(header?.count);
    const parts = [];
    if (Number.isFinite(count)) parts.push(`${count} record${count !== 1 ? 's' : ''}`);
    return parts.join(' | ');
}
</script>

<template>
    <table style="width:100%;border-collapse:collapse;font-size:7pt;table-layout:fixed;">
        <colgroup>
            <col v-for="column in columns" :key="column.key" :style="`width:${column.width};`" />
        </colgroup>
        <thead>
            <tr v-for="header in groupHeaderRows" :key="header.rowKey">
                <th :colspan="columns.length" :style="groupHeaderCellStyle(header.level)">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <span>{{ header.label }}</span>
                        <span v-if="formatGroupHeaderSummary(header)" :style="groupHeaderMetaStyle(header.level)">
                            {{ formatGroupHeaderSummary(header) }}
                        </span>
                    </div>
                </th>
            </tr>
            <tr>
                <th v-for="column in columns" :key="column.key" :style="TH">{{ column.label }}</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(record, index) in records" :key="record.id || `${record.profile_id}-${index}`">
                <td v-for="column in columns" :key="`${column.key}-${index}`" :style="valueStyle(column)">
                    <template v-if="column.key === 'sequence'">{{ index + 1 }}</template>
                    <template v-else>{{ cellValue(record, column) }}</template>
                </td>
            </tr>
            <!-- Total Row -->
            <tr>
                <td :colspan="columns.findIndex(c => c.key === 'amount')" :style="TD + 'font-weight:700;text-align:right;background:#f8f8f8;'">
                    TOTAL
                </td>
                <td :style="TD + 'font-weight:700;text-align:right;background:#f8f8f8;font-size:10px;'">
                    ₱{{ totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </td>
                <td :colspan="columns.length - columns.findIndex(c => c.key === 'amount') - 1" :style="TD + 'background:#f8f8f8;'"></td>
            </tr>
        </tbody>
    </table>
</template>

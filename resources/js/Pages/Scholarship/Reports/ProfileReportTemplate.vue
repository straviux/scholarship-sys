<script setup>
import { computed } from 'vue';
import moment from 'moment';
import { formatName, formatStatus, getReportStatus, groupRecords } from './report-helpers';
import ProfileReportTable from './ProfileReportTable.vue';

const props = defineProps({
    records: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    reportType: { type: String, default: 'list' },
    options: { type: Object, default: () => ({}) },
    generatedAt: { type: String, default: '' },
});

const SUMMARY_HDR = 'background:#f0f0f0;font-weight:700;font-size:9pt;padding:5pt 8pt;text-transform:uppercase;border-bottom:0.5pt solid #ccc;';
const SUMMARY_TH = 'border:0.5pt solid #d9d9d9;padding:4pt 6pt;font-weight:700;font-size:8pt;text-transform:uppercase;background:#f8f8f8;text-align:left;';
const SUMMARY_TD = 'border:0.5pt solid #e5e5e5;padding:4pt 6pt;font-size:8pt;';

const selectedStatus = computed(() => props.options?.selectedStatus ?? null);
const sortedRecords = computed(() => [...props.records].sort((left, right) =>
    formatName(left).localeCompare(formatName(right), undefined, {
        sensitivity: 'base',
        numeric: true,
    })
));
const grouped = computed(() => props.reportType === 'list'
    ? groupRecords(sortedRecords.value, props.options?.groupBy, props.options?.groupBySecondary, props.options?.groupByTertiary)
    : null);
const preparedBy = computed(() => props.options?.preparedBy || '');

const reportLabel = computed(() => {
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
    };

    return map[selectedStatus.value] || formatStatus(selectedStatus.value) || 'SCHOLARSHIP PROFILES';
});

const reportTitle = computed(() => `${reportLabel.value} ${props.reportType === 'summary' ? 'SUMMARY REPORT' : 'REPORT'}`);
const asOfLabel = computed(() => {
    if (!props.generatedAt) {
        return moment().format('MMMM D, YYYY');
    }

    return props.generatedAt.split(' — ')[0] || props.generatedAt;
});

function fmtCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
}

function sumProjectedExpense(records) {
    return records.reduce((sum, record) => sum + Number(record?.projected_total_expense || 0), 0);
}

function flattenGroupRecords(group) {
    if (group.records) {
        return group.records;
    }

    return (group.subGroups || []).flatMap(flattenGroupRecords);
}

function groupProjectedExpense(group) {
    return sumProjectedExpense(flattenGroupRecords(group));
}

const statusSummaryRows = computed(() => {
    const groupedRows = {};

    for (const record of sortedRecords.value) {
        const key = getReportStatus(record);

        if (!groupedRows[key]) {
            groupedRows[key] = { key, label: formatStatus(key), count: 0, projected: 0 };
        }

        groupedRows[key].count += 1;
        groupedRows[key].projected += Number(record?.projected_total_expense || 0);
    }

    return Object.values(groupedRows).sort((left, right) => left.label.localeCompare(right.label));
});

const programSummaryRows = computed(() => {
    const groupedRows = {};

    for (const record of sortedRecords.value) {
        const key = record.program_name || 'N/A';

        if (!groupedRows[key]) {
            groupedRows[key] = { key, label: key, count: 0, projected: 0 };
        }

        groupedRows[key].count += 1;
        groupedRows[key].projected += Number(record?.projected_total_expense || 0);
    }

    return Object.values(groupedRows).sort((left, right) => left.label.localeCompare(right.label));
});

const totalProjectedExpense = computed(() => sumProjectedExpense(sortedRecords.value));
</script>

<template>
    <div class="report-page">
        <div>
            <div
                style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
                <img src="/images/pgp-logo.svg" alt="PGP Logo"
                    style="position:absolute;left:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
                <p style="font-weight:700;font-size:11pt;">Republic of the Philippines</p>
                <p style="font-weight:700;font-size:11pt;">Provincial Government of Palawan</p>
                <p style="font-size:10pt;">Akbay sa Mag-aaral Yaman ng kinabukasan</p>
                <p style="font-size:10pt;">(Programang Pang-Edukasyon para sa Palaweño)</p>
                <p style="font-size:10pt;">Puerto Princesa City, Palawan</p>
            </div>

            <div style="text-align:center;padding:8pt 0 4pt;">
                <p style="font-weight:700;font-size:13pt;">{{ reportTitle }}</p>
                <p style="font-size:9pt;margin-top:3pt;">As of {{ asOfLabel }}</p>
            </div>

            <div v-if="records.length === 0"
                style="text-align:center;padding:24pt;color:#888;font-size:10pt;font-style:italic;">
                No records match the selected filters.
            </div>

            <template v-else-if="reportType === 'list'">
                <template v-if="grouped">
                    <div v-for="group in grouped" :key="group.key" style="margin-bottom:14pt;">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;border-bottom:1pt solid #000;padding:3pt 0;margin-bottom:4pt;">
                            <span style="font-weight:700;font-size:10pt;">{{ group.key }}</span>
                            <span style="font-size:8pt;color:#555;">
                                {{ group.count }} record{{ group.count !== 1 ? 's' : '' }}
                                | {{ fmtCurrency(groupProjectedExpense(group)) }} projected
                            </span>
                        </div>

                        <ProfileReportTable v-if="group.records" :records="group.records" :filters="filters"
                            :options="options" />

                        <template v-for="sub in group.subGroups || []" :key="`${group.key}-${sub.key}`">
                            <div
                                style="display:flex;align-items:center;justify-content:space-between;border-bottom:0.5pt solid #999;padding:3pt 0 3pt 12pt;margin:6pt 0 4pt;">
                                <span style="font-weight:600;font-size:9pt;">{{ sub.key }}</span>
                                <span style="font-size:8pt;color:#555;">
                                    {{ sub.count }} record{{ sub.count !== 1 ? 's' : '' }}
                                    | {{ fmtCurrency(groupProjectedExpense(sub)) }} projected
                                </span>
                            </div>

                            <ProfileReportTable v-if="sub.records" :records="sub.records" :filters="filters"
                                :options="options" />

                            <template v-for="ter in sub.subGroups || []" :key="`${group.key}-${sub.key}-${ter.key}`">
                                <div
                                    style="display:flex;align-items:center;justify-content:space-between;border-bottom:0.5pt solid #ccc;padding:2pt 0 2pt 24pt;margin:6pt 0 4pt;">
                                    <span style="font-weight:600;font-size:8pt;">{{ ter.key }}</span>
                                    <span style="font-size:8pt;color:#555;">
                                        {{ ter.count }} record{{ ter.count !== 1 ? 's' : '' }}
                                        | {{ fmtCurrency(groupProjectedExpense(ter)) }} projected
                                    </span>
                                </div>

                                <ProfileReportTable v-if="ter.records" :records="ter.records" :filters="filters"
                                    :options="options" />
                            </template>
                        </template>
                    </div>
                </template>

                <template v-else>
                    <ProfileReportTable :records="sortedRecords" :filters="filters" :options="options" />
                </template>

                <div style="margin-top:8pt;text-align:right;font-size:8pt;font-weight:700;">
                    Total Records: {{ records.length }} | Total Projected Expense: {{ fmtCurrency(totalProjectedExpense)
                    }}
                </div>
            </template>

            <template v-else>
                <div style="display:flex;gap:16pt;margin-top:10pt;">
                    <div style="flex:1;border:0.5pt solid #ccc;">
                        <div :style="SUMMARY_HDR">By Status</div>
                        <table style="width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;">
                            <thead>
                                <tr>
                                    <th :style="SUMMARY_TH">Status</th>
                                    <th :style="SUMMARY_TH + 'text-align:right;'">Records</th>
                                    <th :style="SUMMARY_TH + 'text-align:right;'">Projected</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in statusSummaryRows" :key="row.key">
                                    <td :style="SUMMARY_TD">{{ row.label }}</td>
                                    <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{ row.count }}</td>
                                    <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{
                                        fmtCurrency(row.projected) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="flex:1;border:0.5pt solid #ccc;">
                        <div :style="SUMMARY_HDR">By Program</div>
                        <table style="width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;">
                            <thead>
                                <tr>
                                    <th :style="SUMMARY_TH">Program</th>
                                    <th :style="SUMMARY_TH + 'text-align:right;'">Records</th>
                                    <th :style="SUMMARY_TH + 'text-align:right;'">Projected</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in programSummaryRows" :key="row.key">
                                    <td :style="SUMMARY_TD">{{ row.label }}</td>
                                    <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{ row.count }}</td>
                                    <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{
                                        fmtCurrency(row.projected) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    style="margin-top:12pt;padding:8pt;border:0.5pt solid #ccc;text-align:center;font-size:11pt;font-weight:700;">
                    Total Records: {{ records.length }} | Total Projected Expense: {{ fmtCurrency(totalProjectedExpense)
                    }}
                </div>
            </template>
        </div>

        <div v-if="preparedBy" style="margin-top:24pt;font-size:8pt;">
            <span>Prepared by: </span>
            <span style="font-weight:700;">{{ preparedBy }}</span>
        </div>

        <div class="report-footer">
            <span>Generated: {{ generatedAt }}</span>
            <span>Total Records: {{ records.length }}</span>
        </div>
    </div>
</template>
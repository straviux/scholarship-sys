<template>
    <div>
        <div
            style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
            <p class="bold t-11">Republic of the Philippines</p>
            <p class="bold t-11">Provincial Government of Palawan</p>
            <p class="t-10">Akbay sa Mag-aaral Yaman ng kinabukasan</p>
            <p class="t-10">(Programang Pang-Edukasyon para sa Palaweño)</p>
            <p class="t-10">Puerto Princesa City, Palawan</p>
        </div>

        <div class="center" style="padding:8pt 0 4pt;">
            <p class="bold" style="font-size:13pt;">
                {{ reportType === 'summary' ? 'INTERVIEW SUMMARY REPORT' : 'INTERVIEWED APPLICANTS REPORT' }}
            </p>
            <p class="t-9" style="margin-top:3pt;">As of {{ today }}</p>
        </div>

        <div v-if="hasFilters"
            style="margin:6pt 0;padding:5pt 8pt;border:0.5pt solid #ccc;border-radius:4pt;font-size:8pt;">
            <span class="bold" style="margin-right:6pt;">Filters:</span>
            <span v-if="filterLabels.recommendation" style="margin-right:10pt;">
                Recommendation: <strong>{{ filterLabels.recommendation }}</strong>
            </span>
            <span v-if="filterLabels.program" style="margin-right:10pt;">
                Program: <strong>{{ filterLabels.program }}</strong>
            </span>
            <span v-if="filterLabels.school" style="margin-right:10pt;">
                School: <strong>{{ filterLabels.school }}</strong>
            </span>
            <span v-if="filterLabels.course" style="margin-right:10pt;">
                Course: <strong>{{ filterLabels.course }}</strong>
            </span>
            <span v-if="filterLabels.date_from || filterLabels.date_to">
                Date: <strong>{{ filterLabels.date_from || '…' }} — {{ filterLabels.date_to || '…' }}</strong>
            </span>
        </div>

        <div v-if="records.length === 0" class="center italic" style="padding:24pt;color:#888;font-size:10pt;">
            No records match the selected filters.
        </div>

        <template v-else-if="reportType === 'list'">
            <template v-if="groupBy !== 'none'">
                <div v-for="(group, groupName) in groupedData" :key="groupName" style="margin-bottom:14pt;">
                    <div
                        style="display:flex;align-items:center;justify-content:space-between;border-bottom:1pt solid #000;padding:3pt 0;margin-bottom:4pt;">
                        <span class="bold" style="font-size:10pt;">{{ groupName }}</span>
                        <span style="font-size:8pt;color:#555;">
                            {{ group.length }} record{{ group.length !== 1 ? 's' : '' }}
                            | {{ fmtCurrency(sumProjectedExpense(group)) }} projected
                        </span>
                    </div>

                    <table style="width:100%;border-collapse:collapse;font-size:7pt;table-layout:fixed;">
                        <colgroup>
                            <col style="width:3%;" />
                            <col style="width:11%;" />
                            <col style="width:5.5%;" />
                            <col style="width:9%;" />
                            <col style="width:11%;" />
                            <col style="width:4%;" />
                            <col style="width:6.5%;" />
                            <col style="width:7%;" />
                            <col style="width:8%;" />
                            <col style="width:4%;" />
                            <col style="width:7.5%;" />
                            <col style="width:5%;" />
                            <col style="width:7.5%;" />
                            <col style="width:6%;" />
                            <col style="width:5%;" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th :style="TH + 'vertical-align:middle;'" style="width:18pt;" rowspan="2">#</th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:11%;" rowspan="2">Name</th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:7%;" rowspan="2">Program</th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:11%;" rowspan="2">School</th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:14%;" rowspan="2">Course</th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:6%;" rowspan="2">Year</th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:8%;" rowspan="2">Semester</th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:9%;" rowspan="2">Academic Year
                                </th>
                                <th :style="TH + 'vertical-align:middle;'" style="width:9%;" rowspan="2">Grant</th>
                                <th :style="TH" colspan="3">Projected</th>
                                <th :style="TH" colspan="3">Interview</th>
                            </tr>
                            <tr>
                                <th :style="TH" style="width:6%;">Terms</th>
                                <th :style="TH" style="width:10%;">Expense</th>
                                <th :style="TH" style="width:7%;">Completion</th>
                                <th :style="TH" style="width:7.5%;">Date</th>
                                <th :style="TH" style="width:6%;">By</th>
                                <th :style="TH" style="width:5%;">Endorsed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(record, index) in group" :key="record.id">
                                <td :style="TD + 'text-align:center;'">{{ index + 1 }}</td>
                                <td :style="TD + 'font-weight:600;'">{{ record.profile.last_name }}, {{
                                    record.profile.first_name }}</td>
                                <td :style="TD">{{ record.program?.shortname || '—' }}</td>
                                <td :style="TD">{{ record.school?.name || record.school?.shortname || '—' }}</td>
                                <td :style="TD">{{ record.course?.name || record.course?.shortname || '—' }}</td>
                                <td :style="TD + 'text-align:center;'">{{ record.year_level || '—' }}</td>
                                <td :style="TD + 'text-align:center;'">{{ record.term || '—' }} </td>
                                <td :style="TD + 'text-align:center;'">{{ record.academic_year || '—' }}</td>
                                <td :style="TD">
                                    <div>{{ fmtGrantProvisionName(record.grant_provision_label ||
                                        record.grant_provision) }}</div>
                                    <div v-if="fmtGrantProvisionAmount(record)"
                                        style="margin-top:2px;font-size:6px;line-height:1.1;">
                                        {{ fmtGrantProvisionAmount(record) }}
                                    </div>
                                </td>
                                <td :style="TD + 'text-align:center;'">{{ fmtProjectedTerms(record) }}</td>
                                <td :style="TD + 'text-align:right;'">{{ fmtProjectedExpense(record) }}</td>
                                <td :style="TD + 'text-align:center;'">{{ fmtCompletionYear(record) }}</td>
                                <td :style="TD + 'text-align:center;white-space:nowrap;width:7.5%;'">{{
                                    fmtDate(record.interviewed_at) }}</td>
                                <td :style="TD + 'text-align:center;text-transform:uppercase;'">{{
                                    record.interviewer?.name || '—' }}</td>
                                <td :style="TD + 'text-align:center;text-transform:uppercase;'">{{ record.endorsed_by ||
                                    '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <template v-else>
                <table style="width:100%;border-collapse:collapse;font-size:7pt;margin-top:6pt;table-layout:fixed;">
                    <colgroup>
                        <col style="width:3%;" />
                        <col style="width:12%;" />
                        <col style="width:4%;" />
                        <col style="width:10%;" />
                        <col style="width:10.5%;" />
                        <col style="width:3.5%;" />
                        <col style="width:7%;" />
                        <col style="width:8.5%;" />
                        <col style="width:4%;" />
                        <col style="width:8%;" />
                        <col style="width:5%;" />
                        <col style="width:6%;" />
                        <col style="width:9%;" />
                        <col style="width:9.5%;" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th :style="TH + 'vertical-align:middle;'" style="width:18pt;" rowspan="2">#</th>
                            <th :style="TH + 'vertical-align:middle;'" style="width:12%;" rowspan="2">Name</th>
                            <th :style="TH + 'vertical-align:middle;'" style="width:4%;" rowspan="2">Program</th>
                            <th :style="TH + 'vertical-align:middle;'" style="width:11%;" rowspan="2">School</th>
                            <th :style="TH + 'vertical-align:middle;'" style="width:11%;" rowspan="2">Course</th>
                            <th :style="TH + 'vertical-align:middle;'" style="width:3%;" rowspan="2">Year</th>
                            <th :style="TH + 'vertical-align:middle;'" style="width:7%;" rowspan="2">Term</th>
                            <th :style="TH + 'vertical-align:middle;'" style="width:7%;" rowspan="2">Grant</th>
                            <th :style="TH" colspan="3" style="width: 16%;">Projected</th>
                            <th :style="TH" colspan="2">Interview</th>
                            <th :style="TH" rowspan="2">Endorsed By</th>
                        </tr>
                        <tr>
                            <th :style="TH">Terms</th>
                            <th :style="TH">Expense</th>
                            <th :style="TH">Completion</th>
                            <th :style="TH" style="width:18pt;">Date</th>
                            <th :style="TH" style="width:46pt;">By</th>

                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(record, index) in records" :key="record.id">
                            <td :style="TD + 'text-align:center;'">{{ index + 1 }}</td>
                            <td :style="TD + 'font-weight:600;'">{{ record.profile.last_name }}, {{
                                record.profile.first_name }}</td>
                            <td :style="TD + 'text-align:center;'">{{ record.program?.shortname || '—' }}</td>
                            <td :style="TD">{{ record.school?.name || record.school?.shortname || '—' }}</td>
                            <td :style="TD">{{ record.course?.name || record.course?.shortname || '—' }}</td>
                            <td :style="TD + 'text-align:center;'">{{ record.year_level || '—' }} </td>
                            <td :style="TD + 'text-align:center;'">{{ record.term || '—' }}
                                <p>{{ record.academic_year || '—' }}</p>
                            </td>
                            <td :style="TD + 'text-align:center;'">
                                <div style="font-size: 8px;">{{ fmtGrantProvisionName(record.grant_provision_label ||
                                    record.grant_provision) }}
                                </div>
                                <div v-if="fmtGrantProvisionAmount(record)" class="mono"
                                    style="margin-top:2px;font-size:7px;line-height:1.1;">
                                    {{ fmtGrantProvisionAmount(record) }}
                                </div>
                            </td>
                            <td :style="TD + 'text-align:center;'">{{ fmtProjectedTerms(record) }}</td>
                            <td :style="TD + 'text-align:center;'">{{ fmtProjectedExpense(record) }}</td>
                            <td :style="TD + 'text-align:center;'">{{ fmtCompletionYear(record) }}</td>
                            <td :style="TD + 'text-align:center;white-space:nowrap;width:18pt;'">{{
                                fmtDate(record.interviewed_at)
                            }}</td>
                            <td :style="TD + 'text-align:center;text-transform:uppercase;'">{{ record.interviewer?.name
                                || '—' }}</td>
                            <td :style="TD + 'text-align:center;text-transform:uppercase;'">{{ record.endorsed_by || '—'
                                }}</td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <div style="margin-top:8pt;text-align:right;font-size:8pt;font-weight:700;">
                Total Records: {{ records.length }} | Total Projected Expense: {{ fmtCurrency(totalProjectedExpense) }}
            </div>
        </template>

        <template v-else>
            <div style="display:flex;gap:16pt;margin-top:10pt;">
                <div style="flex:1;border:0.5pt solid #ccc;">
                    <div :style="SUMMARY_HDR">By Recommendation</div>
                    <table style="width:100%;border-collapse:collapse;font-size:8pt;table-layout:fixed;">
                        <thead>
                            <tr>
                                <th :style="SUMMARY_TH">Recommendation</th>
                                <th :style="SUMMARY_TH + 'text-align:right;'">Interviewed</th>
                                <th :style="SUMMARY_TH + 'text-align:right;'">Projected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in recommendationSummaryRows" :key="row.key">
                                <td :style="SUMMARY_TD"><span :style="recStyle(row.key)">{{ row.label }}</span></td>
                                <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{ row.interviewed }}</td>
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
                                <th :style="SUMMARY_TH + 'text-align:right;'">Interviewed</th>
                                <th :style="SUMMARY_TH + 'text-align:right;'">Projected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in programSummaryRows" :key="row.key">
                                <td :style="SUMMARY_TD">{{ row.label }}</td>
                                <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{ row.interviewed }}</td>
                                <td :style="SUMMARY_TD + 'text-align:right;font-weight:700;'">{{
                                    fmtCurrency(row.projected) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                style="margin-top:12pt;padding:8pt;border:0.5pt solid #ccc;text-align:center;font-size:11pt;font-weight:700;">
                Total Interviewed Applicants: {{ records.length }} | Total Projected Expense: {{
                    fmtCurrency(totalProjectedExpense) }}
            </div>
        </template>

        <div style="margin-top:24pt;font-size:8pt;">
            <span>Prepared by: </span>
            <span class="bold">{{ preparedBy }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import moment from 'moment';

const props = defineProps({
    records: { type: Array, default: () => [] },
    reportType: { type: String, default: 'list' },
    groupBy: { type: String, default: 'none' },
    filterLabels: { type: Object, default: () => ({}) },
    today: { type: String, default: '' },
    preparedBy: { type: String, default: '' },
});

const TH = 'border:1px solid #000;padding:3px 2px;font-weight:700;font-size:7px;line-height:1.15;text-transform:uppercase;text-align:center;background:#f0f0f0;word-break:break-word;overflow-wrap:anywhere;';
const TD = 'border:1px solid #000;padding:3px 3px;font-size:7px;line-height:1.2;vertical-align:middle;word-break:break-word;overflow-wrap:anywhere;';
const SUMMARY_HDR = 'background:#f0f0f0;font-weight:700;font-size:9pt;padding:5pt 8pt;text-transform:uppercase;border-bottom:0.5pt solid #ccc;';
const SUMMARY_TH = 'border:0.5pt solid #d9d9d9;padding:4pt 6pt;font-weight:700;font-size:8pt;text-transform:uppercase;background:#f8f8f8;text-align:left;';
const SUMMARY_TD = 'border:0.5pt solid #e5e5e5;padding:4pt 6pt;font-size:8pt;';

const REC_LABELS = {
    recommended: 'Recommended for Approval',
    further_evaluation: 'For Further Evaluation',
    not_recommended: 'Not Recommended',
};

const REC_COLORS = {
    recommended: '#16a34a',
    further_evaluation: '#d97706',
    not_recommended: '#dc2626',
};

function recLabel(value) {
    return REC_LABELS[value] || value || '—';
}

function recStyle(value) {
    const color = REC_COLORS[value] || '#000';
    return `font-weight:700;color:${color};`;
}

function fmtDate(value) {
    return value ? moment(value).format('MMM DD, YYYY') : '—';
}

function fmtCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
}

function fmtProjectedExpense(record) {
    return record?.projected_total_expense !== null && record?.projected_total_expense !== undefined
        ? fmtCurrency(record.projected_total_expense)
        : 'Not configured';
}

function fmtProjectedTerms(record) {
    const terms = Number(record?.projected_term_count);

    if (!Number.isFinite(terms)) {
        return 'Not configured';
    }

    return `${terms}`;
}

function fmtCompletionYear(record) {
    return record?.projected_completion_year ?? 'Not configured';
}

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

    const normalizedValue = formattedValue
        .replace(/\s{2,}/g, ' ')
        .trim();

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

function fmtGrantProvisionName(value) {
    return parseGrantProvision(value).name;
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

function sumProjectedExpense(records) {
    return records.reduce((sum, record) => sum + Number(record?.projected_total_expense || 0), 0);
}

const hasFilters = computed(() => {
    const filters = props.filterLabels || {};
    return !!(filters.recommendation || filters.program || filters.school || filters.course || filters.date_from || filters.date_to);
});

const groupedData = computed(() => {
    const groups = {};

    for (const record of props.records) {
        let key;

        if (props.groupBy === 'program') key = record.program?.shortname || 'N/A';
        else if (props.groupBy === 'school') key = record.school?.name || record.school?.shortname || 'N/A';
        else if (props.groupBy === 'course') key = record.course?.name || record.course?.shortname || 'N/A';
        else if (props.groupBy === 'recommendation') key = recLabel(record.recommendation);
        else if (props.groupBy === 'interviewer') key = record.interviewer?.name || 'N/A';
        else key = 'All';

        if (!groups[key]) {
            groups[key] = [];
        }

        groups[key].push(record);
    }

    return groups;
});

const totalProjectedExpense = computed(() => sumProjectedExpense(props.records));

const recommendationSummaryRows = computed(() => {
    const grouped = {};
    const order = ['recommended', 'further_evaluation', 'not_recommended', 'unknown'];

    for (const record of props.records) {
        const key = record.recommendation || 'unknown';

        if (!grouped[key]) {
            grouped[key] = { key, label: recLabel(key), interviewed: 0, projected: 0 };
        }

        grouped[key].interviewed += 1;
        grouped[key].projected += Number(record.projected_total_expense || 0);
    }

    return Object.values(grouped).sort((left, right) => {
        const leftIndex = order.indexOf(left.key);
        const rightIndex = order.indexOf(right.key);

        if (leftIndex === -1 && rightIndex === -1) {
            return left.label.localeCompare(right.label);
        }

        if (leftIndex === -1) {
            return 1;
        }

        if (rightIndex === -1) {
            return -1;
        }

        return leftIndex - rightIndex;
    });
});

const programSummaryRows = computed(() => {
    const grouped = {};

    for (const record of props.records) {
        const key = record.program?.shortname || 'N/A';

        if (!grouped[key]) {
            grouped[key] = { key, label: key, interviewed: 0, projected: 0 };
        }

        grouped[key].interviewed += 1;
        grouped[key].projected += Number(record.projected_total_expense || 0);
    }

    return Object.values(grouped).sort((left, right) => left.label.localeCompare(right.label));
});
</script>
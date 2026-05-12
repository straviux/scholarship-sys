<template>
    <div style="padding:10pt;">
        <div
            style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
            <img src="/images/yakap-logo.svg" alt="YAKAP Logo"
                style="position:absolute;right:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
            <p class="bold t-11">Republic of the Philippines</p>
            <p class="bold t-11">Provincial Government of Palawan</p>
            <p class="t-10">Yakap Sa Edukasyon</p>
            <p class="t-10">Scholarship Program</p>
            <p class="t-10">Puerto Princesa City, Palawan</p>
        </div>

        <div class="center" style="padding:8pt 0 4pt;">
            <p class="bold" style="font-size:13pt;">
                {{ reportTitle }}
            </p>
            <p class="t-9" style="margin-top:3pt;">{{ today }}</p>
        </div>

        <div v-if="records.length === 0" class="center italic" style="padding:24pt;color:#888;font-size:10pt;">
            No interviewed applicants match the current selection.
        </div>

        <template v-else-if="reportType === 'list'">
            <template v-if="groupBy !== 'none'">
                <div v-for="(group, groupName) in groupedData" :key="groupName" style="margin-bottom:14pt;">
                    <div
                        style="display:flex;align-items:center;justify-content:space-between;border-bottom:1pt solid #000;padding:3pt 0;margin-bottom:4pt;">
                        <span class="bold" style="font-size:10pt;">{{ groupName }}</span>
                        <span style="font-size:8pt;color:#555;">
                            {{ group.length }} record{{ group.length !== 1 ? 's' : '' }}
                            | {{ fmtCurrency(sumProjectedExpense(group)) }} projected grant
                        </span>
                    </div>

                    <table style="width:100%;border-collapse:collapse;font-size:9pt;table-layout:auto;">
                        <colgroup>
                            <col style="width:3%;" />
                            <col style="width:13%;" />
                            <col style="width:5%;" />
                            <col style="width:10.5%;" />
                            <col style="width:10%;" />
                            <col style="width:4%;" />
                            <col style="width:6%;" />
                            <col style="width:6.5%;" />
                            <col style="width:7.5%;" />
                            <col style="width:3.5%;" />
                            <col style="width:7%;" />
                            <col style="width:4.5%;" />
                            <col v-if="includeInterviewColumns" style="width:7%;" />
                            <col v-if="includeInterviewColumns" style="width:5.5%;" />
                            <col style="width:auto;" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">#</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Name</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Program</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">School</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Course</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Year</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Agreement Start</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Academic Year
                                </th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Grant</th>
                                <th :style="TH" colspan="3">Projected</th>
                                <th v-if="includeInterviewColumns" :style="TH" colspan="2">Interview</th>
                                <th :style="TH + 'vertical-align:middle;width:100%;'" rowspan="2">Remarks</th>
                            </tr>
                            <tr>
                                <th :style="TH">Terms</th>
                                <th :style="TH">Grant</th>
                                <th :style="TH">Completion</th>
                                <th v-if="includeInterviewColumns" :style="TH">Date</th>
                                <th v-if="includeInterviewColumns" :style="TH">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(record, index) in group" :key="record.id">
                                <td :style="TD + 'text-align:center;'">{{ index + 1 }}</td>
                                <td :style="TD + 'font-weight:600;font-size:8pt;'">{{ formatApplicantName(record) }}</td>
                                <td :style="TD + 'font-size:8pt;'">{{ record.program?.shortname || '—' }}</td>
                                <td :style="TD + 'font-size:8pt;'">{{ record.school?.name || record.school?.shortname || '—' }}</td>
                                <td :style="TD + 'font-size:8pt;'">{{ record.course?.name || record.course?.shortname || '—' }}</td>
                                <td :style="TD + 'text-align:center;font-size:8pt;'">{{ record.year_level || '—' }}</td>
                                <td :style="TD + 'text-align:center;font-size:8pt;'">{{ record.term || '—' }} </td>
                                <td :style="TD + 'text-align:center;font-size:8pt;'">{{ record.academic_year || '—' }}</td>
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
                                <td v-if="includeInterviewColumns" :style="TD + 'text-align:center;white-space:nowrap;'">{{
                                    fmtDate(record.interviewed_at) }}</td>
                                <td v-if="includeInterviewColumns" :style="TD + 'text-align:center;text-transform:uppercase;'">{{
                                    record.interviewer?.name || '—' }}</td>
                                <td :style="TD + 'width:100%;'"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <template v-else>
                <table style="width:100%;border-collapse:collapse;font-size:9pt;margin-top:6pt;table-layout:auto;">
                    <colgroup>
                        <col style="width:3%;" />
                        <col style="width:14%;" />
                        <col style="width:4%;" />
                        <col style="width:12%;" />
                        <col style="width:10%;" />
                        <col style="width:3.5%;" />
                        <col style="width:6%;" />
                        <col style="width:7.5%;" />
                        <col style="width:4%;" />
                        <col style="width:7%;" />
                        <col style="width:5%;" />
                        <col style="width:5.5%;" />
                        <col v-if="includeInterviewColumns" style="width:8%;" />
                        <col v-if="includeInterviewColumns" style="width:8.5%;" />
                        <col style="width:auto;" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">#</th>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">Name</th>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">Program</th>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">School</th>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">Course</th>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">Year</th>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">Agreement Start</th>
                            <th :style="TH + 'vertical-align:middle;'" rowspan="2">Grant</th>
                            <th :style="TH" colspan="3">Projected</th>
                            <th v-if="includeInterviewColumns" :style="TH" colspan="2">Interview</th>
                            <th :style="TH + 'vertical-align:middle;width:100%;'" rowspan="2">Remarks</th>
                        </tr>
                        <tr>
                            <th :style="TH">Terms</th>
                            <th :style="TH">Grant</th>
                            <th :style="TH">Completion</th>
                            <th v-if="includeInterviewColumns" :style="TH">Date</th>
                            <th v-if="includeInterviewColumns" :style="TH">By</th>

                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(record, index) in records" :key="record.id">
                            <td :style="TD + 'text-align:center;'">{{ index + 1 }}</td>
                            <td :style="TD + 'font-weight:600;font-size:8pt;'">{{ formatApplicantName(record) }}</td>
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
                                    style="margin-top:2px;font-size:7px;line-height:1.1; letter-spacing:0.35px;">
                                    {{ fmtGrantProvisionAmount(record) }} / term
                                </div>
                            </td>
                            <td :style="TD + 'text-align:center;'">{{ fmtProjectedTerms(record) }}</td>
                            <td :style="TD + 'text-align:center;'">{{ fmtProjectedExpense(record) }}</td>
                            <td :style="TD + 'text-align:center;'">{{ fmtCompletionYear(record) }}</td>
                            <td v-if="includeInterviewColumns" :style="TD + 'text-align:center;white-space:nowrap;'">{{
                                fmtDate(record.interviewed_at)
                            }}</td>
                            <td v-if="includeInterviewColumns" :style="TD + 'text-align:center;text-transform:uppercase;'">{{ record.interviewer?.name
                                || '—' }}</td>
                            <td :style="TD + 'width:100%;'"></td>
                        </tr>
                    </tbody>
                </table>
            </template>

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
                                <th :style="SUMMARY_TH + 'text-align:right;'">Projected Grant</th>
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
                                <th :style="SUMMARY_TH + 'text-align:right;'">Projected Grant</th>
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

        </template>

        <div v-if="budgetAllocation" class="break-before no-break"
            style="padding-top:4pt;page-break-inside:avoid;break-inside:avoid-page;margin-top:40pt;padding-left:20pt;padding-right:20pt;">
            <div style="padding:8pt;font-size:8pt;line-height:1.45;">
                <div class="bold" style="font-size:9pt;text-transform:uppercase;">Budget Allocation for Current Calendar Year</div>

                <table style="width:100%;border-collapse:collapse;margin-top:6pt;font-size:8pt;">
                    <tbody>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;">Program</td>
                            <td colspan="3" style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;">
                                {{ budgetAllocation.program }} · {{ budgetAllocation.rc_name || budgetAllocation.rc_code || 'N/A' }} · {{ budgetAllocation.fiscal_year || 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;">Allocated Fund</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{ fmtCurrency(budgetAllocation.total_allotment) }}</td>
                            <td colspan="2" style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;font-weight:700;">No. of Scholars:</td>
                        </tr>

                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;">Running Balance</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{ fmtCurrency(budgetAllocationRunningBalance) }}</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;text-indent:12pt;">Current no. for this request</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{ totalScholars }}</td>
                        </tr>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;">Total amount for this request</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono bold">{{ fmtCurrency(totalCurrentAcademicYearGrant) }}</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;text-indent:12pt;">Cumulative no. to date</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{ approvedScholarsToDate }}</td>
                        </tr>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;">Remaining balance after approval</td>
                            <td colspan="3" :style="'border:0.5pt solid #d9d9d9;padding:5pt 6pt;font-weight:700;' + (budgetAllocationProjectedBalance < 0 ? 'color:#b91c1c;' : 'color:#166534;')" class="mono">
                                {{ fmtCurrency(budgetAllocationProjectedBalance) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:12pt;font-size:7pt;color:#555;display:flex;justify-content:flex-end;gap:16pt;">
                    <p>___APPROVED</p>
                    <p>___DISAPPROVED</p>
                </div>
            </div>

            <div style="margin-top:50pt;display:flex;justify-content:space-between;font-size:8pt;">
                <div style="flex:1;max-width:60%;margin-left:70pt;">
                    <div style="font-weight:700;">Prepared by:</div>
                    <div style="margin-top:30pt;text-align:center; width: 200px;">
                        <div class="bold"
                            style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedPreparedBy }}</div>
                        <div style="margin-top:4pt;">{{ resolvedPreparedByPosition }}</div>
                        <div>{{ resolvedPreparedByOffice }}</div>
                    </div>
                </div>
                <div style="flex:1;max-width:35%;margin-left:auto;">
                    <div style="font-weight:700;text-align:left;">Approved by:</div>
                    <div style="margin-top:30pt;text-align:center; width: 200px;">
                        <div class="bold"
                            style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedApprovedBy }}</div>
                        <div style="margin-top:4pt;">{{ resolvedApprovedByPosition }}</div>
                    </div>

                    <div style="margin-top:30pt;text-align:center; width: 200px; border-top:1px solid #000;">
                        Date
                    </div>
                </div>
            </div>
        </div>

        <div v-else style="margin-top:14pt;display:flex;justify-content:space-between;font-size:8pt;">
            <div style="flex:1;max-width:60%;margin-left:70pt;">
                <div style="font-weight:700;">Prepared by:</div>
                <div style="margin-top:40pt;text-align:center; width: 200px;">
                    <div class="bold"
                        style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedPreparedBy }}</div>
                    <div style="margin-top:4pt;">{{ resolvedPreparedByPosition }}</div>
                    <div>{{ resolvedPreparedByOffice }}</div>
                </div>
            </div>
            <div style="flex:1;max-width:35%;margin-left:auto;">
                <div style="font-weight:700;text-align:left;">Approved by:</div>
                <div style="margin-top:40pt;text-align:center; width: 200px;">
                    <div class="bold"
                        style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedApprovedBy }}</div>
                    <div style="margin-top:4pt;">{{ resolvedApprovedByPosition }}</div>
                </div>

                <div style="margin-top:40pt;text-align:center; width: 200px; border-top:1px solid #000;">
                    Date
                </div>
            </div>
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
    today: { type: String, default: '' },
    preparedBy: { type: String, default: '' },
    preparedByPosition: { type: String, default: '' },
    preparedByOffice: { type: String, default: '' },
    approvedBy: { type: String, default: '' },
    approvedByPosition: { type: String, default: '' },
    budgetAllocation: { type: Object, default: null },
    reportTitle: { type: String, default: 'INTERVIEWED APPLICANTS REPORT' },
    includeInterviewColumns: { type: Boolean, default: true },
});

const TH = 'border:1px solid #000;padding:3px 2px;font-weight:700;font-size:7px;line-height:1.15;text-transform:uppercase;text-align:center;background:#f0f0f0;word-break:break-word;overflow-wrap:anywhere;';
const TD = 'border:1px solid #000;padding:3px 3px;font-size:7px;line-height:1.2;vertical-align:middle;word-break:break-word;overflow-wrap:anywhere;';
const SUMMARY_HDR = 'background:#f0f0f0;font-weight:700;font-size:9pt;padding:5pt 8pt;text-transform:uppercase;border-bottom:0.5pt solid #ccc;';
const SUMMARY_TH = 'border:0.5pt solid #d9d9d9;padding:4pt 6pt;font-weight:700;font-size:8pt;text-transform:uppercase;background:#f8f8f8;text-align:left;';
const SUMMARY_TD = 'border:0.5pt solid #e5e5e5;padding:4pt 6pt;font-size:8pt;';

const DEFAULT_PREPARED_BY = 'NUR-AINA S. IBRAHIM';
const DEFAULT_PREPARED_BY_POSITION = 'Program Manager';
const DEFAULT_PREPARED_BY_OFFICE = 'YAKAP sa Edukasyon';
const DEFAULT_APPROVED_BY = 'AMY ROA ALVAREZ';
const DEFAULT_APPROVED_BY_POSITION = 'Governor';

const resolvedPreparedBy = computed(() => props.preparedBy?.trim() || DEFAULT_PREPARED_BY);
const resolvedPreparedByPosition = computed(() => props.preparedByPosition?.trim() || DEFAULT_PREPARED_BY_POSITION);
const resolvedPreparedByOffice = computed(() => props.preparedByOffice?.trim() || DEFAULT_PREPARED_BY_OFFICE);
const resolvedApprovedBy = computed(() => props.approvedBy?.trim() || DEFAULT_APPROVED_BY);
const resolvedApprovedByPosition = computed(() => props.approvedByPosition?.trim() || DEFAULT_APPROVED_BY_POSITION);

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

function formatApplicantName(record) {
    const lastName = record?.profile?.last_name || '—';
    const firstName = record?.profile?.first_name || '';
    const middleInitial = record?.profile?.middle_name
        ? `${record.profile.middle_name.trim().charAt(0).toUpperCase()}.`
        : '';

    return [lastName + ',', firstName, middleInitial].filter(Boolean).join(' ').trim();
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
        .replace(/^grant_/i, '')
        .replace(/_/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const amountMatch = normalizedValue.match(/^(.*?)(?:\s*\((?:PHP\s*)?([^()]+)\))$/i);

    if (!amountMatch) {
        return {
            name: normalizedValue || '—',
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
    if (typeof term !== 'string') {
        return false;
    }

    const normalizedTerm = term.toLowerCase();

    return normalizedTerm.includes('trimester')
        || normalizedTerm.includes('3rd semester')
        || normalizedTerm.includes('3rd sem')
        || normalizedTerm.includes('summer')
        || normalizedTerm.includes('midyear');
}

function fmtGrantAmount(value) {
    return new Intl.NumberFormat('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
}

function resolveGrantProvisionAmount(record) {
    const rawAmount = parseGrantProvision(record?.grant_provision_label || record?.grant_provision).amount;

    if (!rawAmount) {
        return null;
    }

    const numericAmount = Number(rawAmount.toString().replace(/,/g, ''));

    if (!Number.isFinite(numericAmount)) {
        return null;
    }

    return isTrimesterTerm(record?.term)
        ? (numericAmount * 2) / 3
        : numericAmount;
}

function fmtGrantProvisionAmount(record) {
    const rawAmount = parseGrantProvision(record?.grant_provision_label || record?.grant_provision).amount;
    const adjustedAmount = resolveGrantProvisionAmount(record);

    if (!Number.isFinite(adjustedAmount)) {
        return rawAmount || '';
    }

    return fmtGrantAmount(adjustedAmount);
}

function currentAcademicYearGrantMultiplier(term) {
    const normalizedTerm = String(term ?? '').trim().toUpperCase();

    if (!normalizedTerm) {
        return 0;
    }

    if (normalizedTerm.includes('1ST TRIMESTER') || normalizedTerm.includes('FIRST TRIMESTER')) {
        return 3;
    }

    if (normalizedTerm.includes('2ND TRIMESTER') || normalizedTerm.includes('SECOND TRIMESTER')) {
        return 2;
    }

    if (normalizedTerm.includes('3RD TRIMESTER') || normalizedTerm.includes('THIRD TRIMESTER')) {
        return 1;
    }

    if (normalizedTerm.includes('1ST SEMESTER') || normalizedTerm.includes('FIRST SEMESTER')) {
        return 2;
    }

    if (normalizedTerm.includes('2ND SEMESTER') || normalizedTerm.includes('SECOND SEMESTER')) {
        return 1;
    }

    if (
        normalizedTerm.includes('3RD SEMESTER')
        || normalizedTerm.includes('3RD SEM')
        || normalizedTerm.includes('SUMMER')
        || normalizedTerm.includes('MIDYEAR')
        || normalizedTerm === 'TRIMESTER'
    ) {
        return 1;
    }

    if (normalizedTerm.includes('TRIMESTER')) {
        return 1;
    }

    return 1;
}

function estimatedCurrentAcademicYearGrant(record) {
    const grantAmount = resolveGrantProvisionAmount(record);

    if (!Number.isFinite(grantAmount)) {
        return 0;
    }

    return grantAmount * currentAcademicYearGrantMultiplier(record?.term);
}

function sumCurrentAcademicYearEstimatedGrant(records) {
    return records.reduce((sum, record) => sum + estimatedCurrentAcademicYearGrant(record), 0);
}

function sumProjectedExpense(records) {
    return records.reduce((sum, record) => sum + Number(record?.projected_total_expense || 0), 0);
}

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

const totalScholars = computed(() => props.records.length);
const approvedScholarsToDate = computed(() => Number(props.budgetAllocation?.approved_scholars_to_date ?? totalScholars.value) || 0);
const totalCurrentAcademicYearGrant = computed(() => sumCurrentAcademicYearEstimatedGrant(props.records));
const budgetAllocationRunningBalance = computed(() => {
    if (!props.budgetAllocation) {
        return 0;
    }

    const allotment = Number(props.budgetAllocation.total_allotment ?? 0);
    const disbursed = Number(props.budgetAllocation.disbursed ?? 0);

    return allotment - disbursed;
});
const budgetAllocationProjectedBalance = computed(() => {
    if (!props.budgetAllocation) {
        return 0;
    }

    return budgetAllocationRunningBalance.value - totalCurrentAcademicYearGrant.value;
});
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
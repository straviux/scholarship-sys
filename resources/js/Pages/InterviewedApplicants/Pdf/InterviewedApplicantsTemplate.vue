<template>
    <div style="padding:4pt 10pt 10pt;">
        <div
            style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:27%;top:50%;transform:translateY(-50%);width:62pt;height:auto;" />
            <img src="/images/yakap-logo.svg" alt="YAKAP Logo"
                style="position:absolute;right:27%;top:50%;transform:translateY(-50%);width:62pt;height:auto;" />
            <p class="bold t-11">Republic of the Philippines</p>
            <p class="bold t-11">Provincial Government of Palawan</p>
            <p class="t-10">Yakap Sa Edukasyon</p>
            <p class="t-10">Scholarship Program</p>
            <p class="t-10">Puerto Princesa City, Palawan</p>
        </div>

        <div class="center" style="padding:4pt 0 2pt;">
            <p class="bold" style="font-size:13pt;">
                {{ reportTitle }}
            </p>
            <p class="t-9" style="margin-top:3pt;">{{ today }}</p>
        </div>

        

        <div v-if="records.length === 0" class="center italic" style="padding:24pt;color:#888;font-size:10pt;">
            No interviewed applicants match the current selection.
        </div>

        <template v-else-if="reportType === 'list'">
            <div style="display:flex;justify-content:space-between;margin-bottom:2pt;">
                <div v-if="records.length > 0 && includeProjectedColumns"
                    style="display:flex;justify-content:flex-start;padding:2pt 0 1pt;font-size:8pt;font-weight:700;">
                    Grant: {{ perScholarGrantLabel }}
                </div>
                <div v-if="listNumber && records.length > 2" style="padding:2pt 0 1pt;text-align:right;">
                    <p class="t-9 bold">Request No. {{ listNumber }}</p>
                </div>
             </div>
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
                            <col
                                :style="includeEndorsedBy ? 'width:18%;' : (includeInterviewColumns ? 'width:10.25%;' : 'width:16%;')" />
                            <col :style="includeEndorsedBy ? 'width:8%;' : 'width:12%;'" />
                            <col style="width:4.5%;" />
                            <col style="width:9.5%;" />
                            <col style="width:9.5%;" />
                            <col style="width:3.5%;" />
                            <col style="width:5.5%;" />
                            <col style="width:6%;" />
                            <col v-if="includeProjectedColumns" style="width:3.5%;" />
                            <col v-if="includeProjectedColumns" style="width:6.5%;" />
                            <col v-if="includeProjectedColumns" style="width:4.5%;" />
                            <col v-if="includeInterviewColumns" style="width:6.5%;" />
                            <col v-if="includeInterviewColumns" style="width:5%;" />
                            <col v-if="includeEndorsedBy" style="width:6.5%;" />
                            <col
                                :style="includeEndorsedBy ? 'width:14%;' : (includeInterviewColumns ? 'width:10.25%;' : 'width:16%;')" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">#</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Name</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Municipality</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Program</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">School</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Course</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Year</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Agreement Start</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Academic Year
                                </th>
                                <th v-if="includeProjectedColumns" :style="TH" colspan="3">Projected</th>
                                <th v-if="includeInterviewColumns" :style="TH" colspan="2">Interview</th>
                                <th v-if="includeEndorsedBy" :style="TH + 'vertical-align:middle;'" rowspan="2">Endorsed
                                    By</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Remarks</th>
                            </tr>
                            <tr>
                                <th v-if="includeProjectedColumns" :style="TH">Terms</th>
                                <th v-if="includeProjectedColumns" :style="TH">Grant</th>
                                <th v-if="includeProjectedColumns" :style2="TH">Completion</th>
                                <th v-if="includeInterviewColumns" :style="TH">Date</th>
                                <th v-if="includeInterviewColumns" :style="TH">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(record, index) in group" :key="`${groupName}-${record.id ?? 'r'}-${index}`">
                                <td :style="TD + 'text-align:center;'">{{ index + 1 }}</td>
                                <td :style="TD + 'font-weight:600;font-size:8pt;'">
                                    <span :style="applicantNameHighlightStyle(record)">{{ formatApplicantName(record)
                                    }}</span>
                                </td>
                                <td :style="TD + 'font-size:8pt;text-transform:uppercase;'">{{
                                    record.profile?.municipality || '' }}</td>
                                <td :style="TD + 'font-size:8pt;'">{{ record.program?.shortname || '' }}</td>
                                <td :style="TD + 'font-size:8pt;'">{{ record.school?.name || record.school?.shortname ||
                                    '' }}</td>
                                <td :style="TD + 'font-size:8pt;'">{{ record.course?.name || record.course?.shortname ||
                                    '' }}</td>
                                <td :style="TD + 'text-align:center;font-size:8pt;'">{{ record.year_level || '' }}</td>
                                <td :style="TD + 'text-align:center;font-size:8pt;'">{{ record.term || '' }} </td>
                                <td :style="TD + 'text-align:center;font-size:8pt;'">{{ record.academic_year || '' }}
                                </td>
                                <td v-if="includeProjectedColumns" :style="TD + 'text-align:center;'">{{
                                    fmtProjectedTerms(record) }}</td>
                                <td v-if="includeProjectedColumns" :style="TD + 'text-align:right;'">{{
                                    fmtProjectedExpense(record) }}</td>
                                <td v-if="includeProjectedColumns" :style="TD + 'text-align:center;'">{{
                                    fmtCompletionYear(record) }}</td>
                                <td v-if="includeInterviewColumns"
                                    :style="TD + 'text-align:center;white-space:nowrap;'">{{
                                        fmtDate(record.interviewed_at) }}</td>
                                <td v-if="includeInterviewColumns"
                                    :style="TD + 'text-align:center;text-transform:uppercase;'">{{
                                        record.interviewer?.name || '' }}</td>
                                <td v-if="includeEndorsedBy" :style="TD + 'text-align:center;font-size:7pt;'">{{
                                    record.endorsed_by || '' }}</td>
                                <td :style="TD + 'font-size:7pt;'">
                                    <div v-safe-html="resolveReportRemarksHtml(record)"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <template v-else>
                <div v-for="(page, pageIndex) in paginatedRecords" :key="`records-page-${pageIndex}`"
                    :class="{ 'break-before': pageIndex > 0 }">
                    <table
                        :style="'width:100%;border-collapse:collapse;font-size:9pt;table-layout:auto;' + (pageIndex === 0 ? 'margin-top:6pt;' : 'margin-top:0;')">
                        <colgroup>
                            <col style="width:3%;" />
                            <col
                                :style="includeEndorsedBy ? 'width:16%;' : (includeInterviewColumns ? 'width:10%;' : 'width:16%;')" />
                            <col style="width:7.5%;" />
                            <col style="width:4.5%;" />
                            <col style="width:10%;" />
                            <col style="width:14%;" />
                            <col style="width:3.5%;" />
                            <col style="width:6%;" />
                            <col v-if="includeProjectedColumns" style="width:3.5%;" />
                            <col v-if="includeProjectedColumns" style="width:6.5%;" />
                            <col v-if="includeProjectedColumns" style="width:5%;" />
                            <col v-if="includeInterviewColumns" style="width:7%;" />
                            <col v-if="includeInterviewColumns" style="width:7%;" />
                            <col v-if="includeEndorsedBy" style="width:6.5%;" />
                            <col
                                :style="includeEndorsedBy ? 'width:12%;' : (includeInterviewColumns ? 'width:10%;' : 'width:16%;')" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">#</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Name</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Municipality</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Program</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">School</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Course</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Year</th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Agreement Start</th>
                                <th v-if="includeProjectedColumns" :style="TH" colspan="3">Projected</th>
                                <th v-if="includeInterviewColumns" :style="TH" colspan="2">Interview</th>
                                <th v-if="includeEndorsedBy" :style="TH + 'vertical-align:middle;'" rowspan="2">Endorsed
                                    By
                                </th>
                                <th :style="TH + 'vertical-align:middle;'" rowspan="2">Remarks</th>
                            </tr>
                            <tr>
                                <th v-if="includeProjectedColumns" :style="TH">Terms</th>
                                <th v-if="includeProjectedColumns" :style="TH">Grant</th>
                                <th v-if="includeProjectedColumns" :style="TH">Completion</th>
                                <th v-if="includeInterviewColumns" :style="TH">Date</th>
                                <th v-if="includeInterviewColumns" :style="TH">By</th>

                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(record, index) in page.records"
                                :key="`${record.id ?? 'r'}-${page.startIndex + index}`">
                                <td :style="TD + 'text-align:center;'">{{ page.startIndex + index + 1 }}</td>
                                <td :style="TD + 'font-weight:600;font-size:8pt;'">
                                    <span :style="applicantNameHighlightStyle(record)">{{ formatApplicantName(record)
                                        }}</span>
                                </td>
                                <td :style="TD + 'text-transform:uppercase;'">{{ record.profile?.municipality || '' }}
                                </td>
                                <td :style="TD + 'text-align:center;'">{{ record.program?.shortname || '' }}</td>
                                <td :style="TD">{{ record.school?.name || record.school?.shortname || '' }}</td>
                                <td :style="TD">{{ record.course?.name || record.course?.shortname || '' }}</td>
                                <td :style="TD + 'text-align:center;'">{{ record.year_level || '' }} </td>
                                <td :style="TD + 'text-align:center;line-height:1;'">
                                    <div>{{ record.term || '' }}</div>
                                    <div>{{ record.academic_year || '' }}</div>
                                </td>
                                <td v-if="includeProjectedColumns" :style="TD + 'text-align:center;'">{{
                                    fmtProjectedTerms(record) }}</td>
                                <td v-if="includeProjectedColumns" :style="TD + 'text-align:right;'">{{
                                    fmtProjectedExpense(record) }}</td>
                                <td v-if="includeProjectedColumns" :style="TD + 'text-align:center;'">{{
                                    fmtCompletionYear(record) }}</td>
                                <td v-if="includeInterviewColumns"
                                    :style="TD + 'text-align:center;white-space:nowrap;'">{{
                                        fmtDate(record.interviewed_at)
                                    }}</td>
                                <td v-if="includeInterviewColumns"
                                    :style="TD + 'text-align:center;text-transform:uppercase;'">{{
                                        record.interviewer?.name
                                        || '' }}</td>
                                <td v-if="includeEndorsedBy" :style="TD + 'text-align:center;font-size:7pt;'">{{
                                    record.endorsed_by || '' }}</td>
                                <td :style="TD + 'font-size:7pt;'">
                                    <div v-safe-html="resolveReportRemarksHtml(record)"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Reviewed by signatory per page -->
                    <div v-if="page.records.length > 2 && resolvedReviewedBy" class="no-break" style="margin-right:20pt;margin-top:40pt;font-size:8pt;display:flex;justify-content:flex-end;page-break-inside:avoid;break-inside:avoid-page;">
                        <div style="width:220px;display:flex;align-items:flex-start;gap:2pt;">
                            <div style="display: flex;width:100%;">Reviewed by:</div>
                            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;">
                                <span class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedReviewedBy }}</span>
                                <div style="margin-top:2pt;">Governor</div>
                            </div>
                        </div>
                    </div>
                </div>
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

        <div v-if="budgetAllocation" :class="{ 'break-before': budgetAllocationOnNextPage }" class="no-break"
            style="padding-top:4pt;page-break-inside:avoid;break-inside:avoid-page;margin-top:20pt;padding-left:20pt;padding-right:20pt;">
            <div style="padding:8pt;font-size:8pt;line-height:1.45;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12pt;">
                    <div class="bold" style="font-size:9pt;text-transform:uppercase;">{{ budgetAllocationHeading }}
                    </div>
                    <p v-if="listNumber" class="t-9 bold" style="white-space:nowrap;flex-shrink:0;">Request No. {{
                        listNumber }}
                    </p>
                </div>

                <table style="width:100%;border-collapse:collapse;margin-top:4pt;font-size:8pt;">
                    <tbody>
                        <tr>
                            <td
                                style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;">
                                Program</td>
                            <td colspan="3" style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;">
                                {{ resolvedBudgetProgram }} · {{ budgetAllocation.rc_name || budgetAllocation.rc_code ||
                                    'N/A'
                                }} · {{ budgetAllocation.fiscal_year ? `CY ${budgetAllocation.fiscal_year}` : 'CY N/A'
                                }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;">
                                Allocated
                                Fund</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{
                                fmtCurrency(budgetAllocation.total_allotment) }}</td>
                            <td colspan="2" style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;font-weight:700;">No. of
                                Scholars:
                            </td>
                        </tr>

                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;">
                                Running
                                Balance</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{
                                fmtCurrency(budgetAllocationRunningBalance) }}</td>
                            <td
                                style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;text-indent:12pt;">
                                Current no. for this request</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{ totalScholars }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;">
                                Total amount for this request</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono bold">{{
                                fmtCurrency(totalCurrentAcademicYearGrant) }}</td>
                            <td
                                style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;text-indent:12pt;">
                                Cumulative Approved No. {{ approvedScholarsScopeSuffix }}
                            </td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{
                                approvedScholarsToDate }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;">
                                Remaining
                                balance after approval</td>
                            <td colspan="3"
                                :style="'border:0.5pt solid #d9d9d9;padding:5pt 6pt;font-weight:700;' + (budgetAllocationProjectedBalance < 0 ? 'color:#b91c1c;' : 'color:#166534;')"
                                class="mono">
                                {{ fmtCurrency(budgetAllocationProjectedBalance) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:6pt;font-size:7pt;color:#555;display:flex;justify-content:flex-end;gap:16pt;">
                    <p>___APPROVED</p>
                    <p>___DISAPPROVED</p>
                </div>
            </div>

            <div style="margin-top:2pt;font-size:8pt;">
                <div style="display:flex;justify-content:space-between;">
                    <div style="flex:1;max-width:60%;margin-left:70pt;">
                        <div style="font-weight:700;">Prepared by:</div>
                        <div style="margin-top:40pt;text-align:center; width: 200px;">
                            <div class="bold"
                                style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                                {{
                                    resolvedPreparedBy }}</div>
                            <div style="margin-top:4pt;">{{ resolvedPreparedByPosition }}</div>
                            <div>{{ resolvedPreparedByOffice }}</div>
                        </div>
                    </div>
                    <div style="flex:1;max-width:35%;margin-left:auto;">
                        <div style="font-weight:700;text-align:left;">Approved by:</div>
                        <div style="margin-top:40pt;text-align:center; width: 200px;">
                            <div class="bold"
                                style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                                {{
                                    resolvedApprovedBy }}</div>
                            <div style="margin-top:4pt;">{{ resolvedApprovedByPosition }}</div>
                        </div>

                        <div style="margin-top:30pt;text-align:center; width: 200px; border-top:1px solid #000;">
                            Date
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="showSignatories"
            style="margin-top:14pt;display:flex;justify-content:space-between;font-size:8pt;">
            <div style="flex:1;max-width:60%;margin-left:70pt;">
                <div style="font-weight:700;">Prepared by:</div>
                <div style="margin-top:40pt;text-align:center; width: 200px;">
                    <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                        {{
                            resolvedPreparedBy }}</div>
                    <div style="margin-top:4pt;">{{ resolvedPreparedByPosition }}</div>
                    <div>{{ resolvedPreparedByOffice }}</div>
                </div>
            </div>
            <div style="flex:1;max-width:35%;margin-left:auto;">
                <div style="font-weight:700;text-align:left;">Approved by:</div>
                <div style="margin-top:40pt;text-align:center; width: 200px;">
                    <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">
                        {{
                            resolvedApprovedBy }}</div>
                    <div style="margin-top:4pt;">{{ resolvedApprovedByPosition }}</div>
                </div>

                <div style="margin-top:32pt;text-align:center; width: 200px; border-top:1px solid #000;">
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
    budgetProgram: { type: String, default: '' },
    budgetAllocation: { type: Object, default: null },
    reportTitle: { type: String, default: 'INTERVIEWED APPLICANTS REPORT' },
    listNumber: { type: String, default: '' },
    includeInterviewColumns: { type: Boolean, default: true },
    includeEndorsedBy: { type: Boolean, default: false },
    includeProjectedColumns: { type: Boolean, default: true },
    highlightJpmMembers: { type: Boolean, default: false },
    showRemarks: { type: Boolean, default: false },
    reviewedBy: { type: String, default: '' },
});

const TH = 'border:1px solid #000;padding:2px 2px;font-weight:700;font-size:7px;line-height:1.05;text-transform:uppercase;text-align:center;background:#f0f0f0;word-break:break-word;overflow-wrap:anywhere;';
const TD = 'border:1px solid #000;padding:3px 2px;font-size:8pt;line-height:1.1;vertical-align:middle;word-break:break-word;overflow-wrap:anywhere;';
const SUMMARY_HDR = 'background:#f0f0f0;font-weight:700;font-size:9pt;padding:5pt 8pt;text-transform:uppercase;border-bottom:0.5pt solid #ccc;';
const SUMMARY_TH = 'border:0.5pt solid #d9d9d9;padding:4pt 6pt;font-weight:700;font-size:8pt;text-transform:uppercase;background:#f8f8f8;text-align:left;';
const SUMMARY_TD = 'border:0.5pt solid #e5e5e5;padding:4pt 6pt;font-size:8pt;';
const FIRST_PAGE_ROW_LIMIT = 10;
const CONTINUATION_PAGE_ROW_LIMIT = 15;

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
const resolvedReviewedBy = computed(() => showSignatories.value ? (props.reviewedBy?.trim() || DEFAULT_APPROVED_BY) : '');
const showSignatories = computed(() => Boolean(props.preparedBy?.trim()));
const resolvedBudgetProgram = computed(() => props.budgetProgram?.trim() || props.budgetAllocation?.program || 'N/A');
const explicitBudgetProgramLabel = computed(() => props.budgetProgram?.trim() || '');
const budgetProgramFilterId = computed(() => {
    const programId = props.budgetAllocation?.program_id;

    return programId === null || programId === undefined || programId === ''
        ? ''
        : String(programId);
});

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
    return value ? moment(value).format('MMM DD, YYYY') : '';
}

function formatApplicantName(record) {
    const lastName = record?.profile?.last_name || '';
    const firstName = record?.profile?.first_name || '';
    const middleInitial = record?.profile?.middle_name
        ? `${record.profile.middle_name.trim().charAt(0).toUpperCase()}.`
        : '';

    return [lastName + ',', firstName, middleInitial].filter(Boolean).join(' ').trim();
}

function hasJpmMember(record) {
    return Boolean(
        record?.profile?.is_jpm_member
        || record?.profile?.is_father_jpm
        || record?.profile?.is_mother_jpm
        || record?.profile?.is_guardian_jpm,
    );
}

function applicantNameHighlightStyle(record) {
    if (!props.highlightJpmMembers || !hasJpmMember(record)) {
        return '';
    }

    return 'color:#166534;font-weight:700;';
}

function scholarMatchesBudgetProgram(scholar) {
    const programId = budgetProgramFilterId.value;
    const programLabel = explicitBudgetProgramLabel.value.trim().toLowerCase();

    if (!programId && !programLabel) {
        return true;
    }

    if (programId && String(scholar?.program_id ?? '') === programId) {
        return true;
    }

    if (!programLabel) {
        return false;
    }

    return [
        scholar?.program,
        scholar?.program_name,
        scholar?.program_shortname,
    ]
        .filter(Boolean)
        .some((programValue) => String(programValue).trim().toLowerCase() === programLabel);
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
        : '';
}

function resolveReportRemarksHtml(record) {
    if (!props.showRemarks) {
        return '';
    }

    return String(record?.interview_remarks || record?.remarks || '').replace(/\n/g, '<br>');
}

function resolveGrantByProgram(record) {
    const program = String(
        record?.program?.shortname || record?.program?.name || record?.program || '',
    ).toUpperCase();

    return program.includes('MED') ? 70000 : 10000;
}

const perScholarGrantLabel = computed(() => {
    const records = props.records || [];

    if (records.length === 0) {
        return '';
    }

    const hasMed = records.some((record) => resolveGrantByProgram(record) === 70000);
    const hasOthers = records.some((record) => resolveGrantByProgram(record) === 10000);

    if (hasMed && hasOthers) {
        return `MED: ${fmtCurrency(70000)} | OTHERS: ${fmtCurrency(10000)}`;
    }

    return hasMed ? fmtCurrency(70000) : fmtCurrency(10000);
});

function fmtProjectedTerms(record) {
    const terms = Number(record?.projected_term_count);

    if (!Number.isFinite(terms)) {
        return '';
    }

    return `${terms}`;
}

function fmtCompletionYear(record) {
    return record?.projected_completion_year ?? '';
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

function paginateRecordsForPdf(records, firstPageSize = FIRST_PAGE_ROW_LIMIT, nextPageSize = CONTINUATION_PAGE_ROW_LIMIT) {
    const pages = [];
    const normalizedRecords = Array.isArray(records) ? records : [];
    let startIndex = 0;
    let pageSize = Math.max(1, Number(firstPageSize) || 1);

    while (startIndex < normalizedRecords.length) {
        pages.push({
            startIndex,
            records: normalizedRecords.slice(startIndex, startIndex + pageSize),
        });

        startIndex += pageSize;
        pageSize = Math.max(1, Number(nextPageSize) || 1);
    }

    return pages;
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
const paginatedRecords = computed(() => paginateRecordsForPdf(props.records));
const lastPageHasMoreThanThreshold = computed(() => {
    const pages = paginatedRecords.value;
    if (pages.length === 0) return true;
    const lastPage = pages[pages.length - 1];
    return (lastPage.records || []).length > 4;
});

const firstPageHasReviewedBy = computed(() => {
    const pages = paginatedRecords.value;
    if (pages.length === 0) return false;
    return (pages[0].records || []).length > 2;
});

const budgetAllocationOnNextPage = computed(() => lastPageHasMoreThanThreshold.value || firstPageHasReviewedBy.value);

const totalScholars = computed(() => props.records.length);
const approvedScholarsCalendarYearLabel = computed(() => {
    const candidates = [
        props.budgetAllocation?.calendar_year,
        props.budgetAllocation?.fiscal_year,
        props.budgetAllocation?.date_start,
        props.budgetAllocation?.date_end,
    ];

    for (const candidate of candidates) {
        if (candidate === null || candidate === undefined || candidate === '') {
            continue;
        }

        const match = String(candidate).match(/\b(\d{4})\b/);

        if (match) {
            return match[1];
        }
    }

    return null;
});
const budgetAllocationHeading = computed(() => {
    if (approvedScholarsCalendarYearLabel.value) {
        return `Budget Allocation for Calendar Year ${approvedScholarsCalendarYearLabel.value}`;
    }

    return 'Budget Allocation for Current Calendar Year';
});
const approvedScholarsScopedToProgram = computed(() => {
    const scholars = Array.isArray(props.budgetAllocation?.approved_scholars)
        ? props.budgetAllocation.approved_scholars
        : [];

    return scholars.filter(scholarMatchesBudgetProgram);
});
const approvedScholarsProgramScopeLabel = computed(() => {
    if (explicitBudgetProgramLabel.value) {
        return explicitBudgetProgramLabel.value;
    }

    if (props.budgetAllocation?.program_id) {
        return props.budgetAllocation?.program || '';
    }

    return '';
});
const approvedScholarsScopeSuffix = computed(() => {
    const parts = [];

    if (approvedScholarsCalendarYearLabel.value) {
        parts.push(`CY ${approvedScholarsCalendarYearLabel.value}`);
    }

    // if (approvedScholarsProgramScopeLabel.value) {
    //     parts.push(approvedScholarsProgramScopeLabel.value);
    // }

    return parts.length ? ` (${parts.join(' · ')})` : '';
});
const approvedScholarsToDate = computed(() => {
    if (approvedScholarsScopedToProgram.value.length) {
        return approvedScholarsScopedToProgram.value.length;
    }

    return Number(props.budgetAllocation?.approved_scholars_to_date ?? totalScholars.value) || 0;
});
const totalCurrentAcademicYearGrant = computed(() => sumCurrentAcademicYearEstimatedGrant(props.records));
const approvedScholarsCurrentAcademicYearGrantTotal = computed(() => {
    return Number(props.budgetAllocation?.approved_scholars_current_ay_estimated_total ?? 0) || 0;
});
const budgetAllocationRunningBalance = computed(() => {
    if (!props.budgetAllocation) {
        return 0;
    }

    const allotment = Number(props.budgetAllocation.total_allotment ?? 0);
    const disbursed = Number(props.budgetAllocation.disbursed ?? 0);

    return allotment - disbursed - approvedScholarsCurrentAcademicYearGrantTotal.value;
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
<template>
    <div style="padding:4pt 10pt 10pt;">
        <!-- ── Letterhead ─────────────────────────────────────────── -->
        <div
            style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:27%;top:50%;transform:translateY(-50%);width:62pt;height:auto;" />
            <img src="/images/yakap-logo.svg" alt="YAKAP Logo"
                style="position:absolute;right:27%;top:50%;transform:translateY(-50%);width:62pt;height:auto;" />
            <p class="t-10">Republic of the Philippines</p>
            <p class="t-10">Provincial Government of Palawan</p>
            <p class="bold t-11">YAKAP SA EDUKASYON</p>
            <p class="bold t-11">Scholarship Program</p>
            <p class="t-10">Puerto Princesa City, Palawan</p>
        </div>

        <!-- ── Title + hoisted header lines ───────────────────────── -->
        <div class="center" style="padding:4pt 0 8pt;">
            <div style="font-size:13pt;text-transform: uppercase;" v-safe-html="resolvedReportTitle"></div>
            <p v-if="schoolUniform"  style="font-size:11pt;margin-top:2pt;font-weight:bold;text-transform:uppercase;">
                {{ uniformSchoolLabel }}
            </p>
            <p v-if="uniformAcademicYear || uniformTerm" class="bold" style="font-size:11pt;text-transform: uppercase;margin-top:1pt;">
                For Academic Year {{ uniformAcademicYear }} - {{ uniformTerm }}
            </p>
            
        </div>
        <div v-if="programUniform || (listNumber && records.length > 2)" style="margin-bottom:2pt;display:flex;justify-content:space-between;">
                <div class="bold t-9" >Program: {{ uniformProgramLabel }}<template v-if="perScholarGrantLabel"> | Grant: {{ perScholarGrantLabel }}</template></div>
                <div v-if="listNumber && records.length > 2" style="text-align:right;" class="t-9 bold">
                    Request No. {{ listNumber }}
                </div>
        </div>
        
            
        

        <!-- ── Empty state ────────────────────────────────────────── -->
        <div v-if="records.length === 0" class="center italic" style="padding:24pt;color:#888;font-size:10pt;">
            No recommended applicants in this list.
        </div>

        <!-- ── Course-grouped tables (paginated) ──────────────────── -->
        <template v-else>
            <template v-for="(page, pageIndex) in groupedPaginatedRecords" :key="`rl-page-${pageIndex}`">
                <div :class="{ 'break-before': pageIndex > 0 }">
                    <div v-for="(group, groupName) in page.groups" :key="groupName" style="margin-bottom:14pt;">
                        <div class="bold"
                            style="padding:3pt 0;margin-bottom:2pt;font-size:10pt;text-transform:uppercase;">
                            {{ groupName }}
                        </div>

                        <table style="width:100%;border-collapse:collapse;font-size:9pt;table-layout:auto;">
                            <colgroup>
                                <col style="width:3%;" />
                                <col style="width:20%;" />
                                <col style="width:12%;" />
                                <col style="width:6%;" />
                                <col v-if="showSchoolColumn" style="width:14%;" />
                                <col v-if="showCourseColumn" style="width:14%;" />
                                <col v-if="showProgramColumn" style="width:8%;" />
                                <col style="width:5%;" />
                                <col style="width:9%;" />
                                <col style="width:7%;" />
                                <col style="width:16%;" />
                            </colgroup>
                            <thead>
                                <tr>
                                    <th :style="TH + 'vertical-align:middle;'" rowspan="2">#</th>
                                    <th :style="TH + 'vertical-align:middle;'" rowspan="2">Name</th>
                                    <th :style="TH + 'vertical-align:middle;'" rowspan="2">Municipality</th>
                                    <th :style="TH + 'vertical-align:middle;'" rowspan="2">Year Level</th>
                                    <th v-if="showSchoolColumn" :style="TH + 'vertical-align:middle;'" rowspan="2">School</th>
                                    <th v-if="showCourseColumn" :style="TH + 'vertical-align:middle;'" rowspan="2">Course</th>
                                    <th v-if="showProgramColumn" :style="TH + 'vertical-align:middle;'" rowspan="2">Program</th>
                                    <th :style="TH" colspan="3">Projected</th>
                                    <th :style="TH + 'vertical-align:middle;'" rowspan="2">Remarks</th>
                                </tr>
                                <tr>
                                    <th :style="TH">Terms</th>
                                    <th :style="TH">Grant</th>
                                    <th :style="TH">Completion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(record, index) in group" :key="`${groupName}-${record.id ?? 'r'}-${index}`">
                                    <td :style="TD + 'text-align:center;'">{{ index + 1 }}</td>
                                    <td :style="TD + 'font-weight:600;font-size:8pt;'">
                                        <span :style="applicantNameHighlightStyle(record)">{{ formatApplicantName(record) }}</span>
                                    </td>
                                    <td :style="TD + 'font-size:8pt;text-transform:uppercase;'">{{ record.profile?.municipality || '' }}</td>
                                    <td :style="TD + 'text-align:center;font-size:8pt;'">{{ record.year_level || '' }}</td>
                                    <td v-if="showSchoolColumn" :style="TD + 'font-size:8pt;'">{{ record.school?.name || record.school?.shortname || '' }}</td>
                                    <td v-if="showCourseColumn" :style="TD + 'font-size:8pt;'">{{ record.course?.name || record.course?.shortname || '' }}</td>
                                    <td v-if="showProgramColumn" :style="TD + 'text-align:center;font-size:8pt;'">{{ record.program?.shortname || '' }}</td>
                                    <td :style="TD + 'text-align:center;'">{{ fmtProjectedTerms(record) }}</td>
                                    <td :style="TD + 'text-align:right;'">{{ fmtProjectedExpense(record) }}</td>
                                    <td :style="TD + 'text-align:center;'">{{ fmtCompletionYear(record) }}</td>
                                    <td :style="TD + 'font-size:7pt;'">
                                        <div v-safe-html="resolveReportRemarksHtml(record)"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </template>

        <!-- ── Budget allocation + signatories (last page) ────────── -->
        <div v-if="budgetAllocation" class="no-break" :class="{ 'break-before': forceTrailingBlockToNextPage }"
            style="padding-top:4pt;page-break-inside:avoid;break-inside:avoid-page;margin-top:20pt;padding-left:20pt;padding-right:20pt;">
            <div style="padding:8pt;font-size:8pt;line-height:1.45;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12pt;">
                    <div class="bold" style="font-size:9pt;text-transform:uppercase;">{{ budgetAllocationHeading }}</div>
                    <p v-if="listNumber" class="t-9 bold" style="white-space:nowrap;flex-shrink:0;">Request No. {{ listNumber }}</p>
                </div>

                <table style="width:100%;border-collapse:collapse;margin-top:4pt;font-size:8pt;">
                    <tbody>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;">Program</td>
                            <td colspan="3" style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;">
                                {{ resolvedBudgetProgram }} · {{ budgetAllocation.rc_name || budgetAllocation.rc_code || 'N/A' }} · {{ budgetAllocation.fiscal_year ? `CY ${budgetAllocation.fiscal_year}` : 'CY N/A' }}
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
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;width:24%;text-indent:12pt;">Cumulative Approved No. {{ approvedScholarsScopeSuffix }}</td>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;" class="mono">{{ approvedScholarsToDate }}</td>
                        </tr>
                        <tr>
                            <td style="border:0.5pt solid #d9d9d9;padding:5pt 6pt;background:#f8f8f8;font-weight:700;">Remaining balance after approval</td>
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

            <!-- Table instead of flex: Paged.js's page-break-inside:avoid is
                 unreliable on flex containers, so a fragmentation-prone flex
                 row here risked splitting mid-signature across pages. -->
            <table style="width:100%;border-collapse:collapse;margin-top:2pt;font-size:8pt;">
                <tr>
                    <td style="width:60%;padding-left:70pt;vertical-align:top;">
                        <div style="font-weight:700;">Prepared by:</div>
                        <div style="margin-top:40pt;text-align:center; width: 200px;">
                            <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedPreparedBy }}</div>
                            <div style="margin-top:4pt;">{{ resolvedPreparedByPosition }}</div>
                            <div>{{ resolvedPreparedByOffice }}</div>
                        </div>
                    </td>
                    <td style="width:5%;"></td>
                    <td style="width:35%;vertical-align:top;">
                        <div style="font-weight:700;text-align:left;">Approved by:</div>
                        <div style="margin-top:40pt;text-align:center; width: 200px;">
                            <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedApprovedBy }}</div>
                            <div style="margin-top:4pt;">{{ resolvedApprovedByPosition }}</div>
                        </div>
                        <div style="margin-top:30pt;text-align:center; width: 200px; border-top:1px solid #000;">Date</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Table instead of flex — see note above; also given its own
             no-break so it can't be split from its signature block either. -->
        <table v-else class="no-break" :class="{ 'break-before': forceTrailingBlockToNextPage }"
            style="width:100%;border-collapse:collapse;margin-top:14pt;font-size:8pt;page-break-inside:avoid;break-inside:avoid-page;">
            <tr>
                <td style="width:60%;padding-left:70pt;vertical-align:top;">
                    <div style="font-weight:700;">Prepared by:</div>
                    <div style="margin-top:40pt;text-align:center; width: 200px;">
                        <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedPreparedBy }}</div>
                        <div style="margin-top:4pt;">{{ resolvedPreparedByPosition }}</div>
                        <div>{{ resolvedPreparedByOffice }}</div>
                    </div>
                </td>
                <td style="width:5%;"></td>
                <td style="width:35%;vertical-align:top;">
                    <div style="font-weight:700;text-align:left;">Approved by:</div>
                    <div style="margin-top:40pt;text-align:center; width: 200px;">
                        <div class="bold" style="border-bottom:1px solid #000;padding-bottom:2pt;text-transform:uppercase;">{{ resolvedApprovedBy }}</div>
                        <div style="margin-top:4pt;">{{ resolvedApprovedByPosition }}</div>
                    </div>
                    <div style="margin-top:32pt;text-align:center; width: 200px; border-top:1px solid #000;">Date</div>
                </td>
            </tr>
        </table>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    records: { type: Array, default: () => [] },
    preparedBy: { type: String, default: '' },
    preparedByPosition: { type: String, default: '' },
    preparedByOffice: { type: String, default: '' },
    approvedBy: { type: String, default: '' },
    approvedByPosition: { type: String, default: '' },
    budgetProgram: { type: String, default: '' },
    budgetAllocation: { type: Object, default: null },
    highlightJpmMembers: { type: Boolean, default: false },
    showRemarks: { type: Boolean, default: false },
    reportTitle: { type: String, default: '<p><strong>Request for Scholarship Approval</strong></p>' },
    listNumber: { type: String, default: '' },
    groupBy: { type: String, default: 'course' },
    paperSize: { type: String, default: 'A4' },
    orientation: { type: String, default: 'landscape' },
});

const TH = 'border:1px solid #000;padding:2px 2px;font-weight:700;font-size:7px;line-height:1.05;text-transform:uppercase;text-align:center;background:#f0f0f0;word-break:break-word;overflow-wrap:anywhere;';
const TD = 'border:1px solid #000;padding:3px 2px;font-size:8pt;line-height:1.1;vertical-align:middle;word-break:break-word;overflow-wrap:anywhere;';

// ── Page-height-aware row budget ────────────────────────────────────
// Row splitting used to be a flat guess (22 rows), which didn't account for
// the actual paper size/orientation — on smaller pages Paged.js would insert
// its own real page break mid-bucket, orphaning the "Reviewed by" block that
// sits right after the table. Instead, derive how many rows actually fit in
// the printable area from the real page dimensions and margins, matching
// @page in pdf-styles.js (interviewedApplicantsPdfFooterCss: 4mm/5mm/12mm/5mm).
const MM_TO_PT = 2.83464567;
const PAGE_MARGIN_TOP_PT = 4 * MM_TO_PT;
const PAGE_MARGIN_BOTTOM_PT = 12 * MM_TO_PT;

// Physical page size in points (portrait orientation; swapped below for landscape)
const PAGE_SIZE_PT = {
    A4: { w: 595, h: 842 },
    Letter: { w: 612, h: 792 },
    Legal: { w: 612, h: 936 },
};

// Fixed block heights, estimated from the inline styles rendered above (pt)
const LETTERHEAD_HEIGHT_PT = 145; // letterhead + title block — page 1 only
const TABLE_HEADER_HEIGHT_PT = 18; // two-row <thead>
const GROUP_HEADER_HEIGHT_PT = 21; // per-group heading + its bottom margin
const DATA_ROW_HEIGHT_PT = 15; // per <tr> (padding + line-height at 8pt font)
const MIN_ROWS_PER_PAGE = 5;

const pageContentHeightPt = computed(() => {
    const size = PAGE_SIZE_PT[props.paperSize] || PAGE_SIZE_PT.A4;
    // Page is defined portrait (w < h); landscape prints the longer edge as height
    const height = props.orientation === 'landscape' ? size.w : size.h;
    return height - PAGE_MARGIN_TOP_PT - PAGE_MARGIN_BOTTOM_PT;
});

// Rows that fit on the first page (letterhead + title eat into the budget)
const FIRST_PAGE_ROW_LIMIT = computed(() => {
    const available = pageContentHeightPt.value - LETTERHEAD_HEIGHT_PT
        - TABLE_HEADER_HEIGHT_PT - GROUP_HEADER_HEIGHT_PT;
    return Math.max(MIN_ROWS_PER_PAGE, Math.floor(available / DATA_ROW_HEIGHT_PT));
});

// Rows that fit on subsequent pages (no letterhead)
const NEXT_PAGE_ROW_LIMIT = computed(() => {
    const available = pageContentHeightPt.value - TABLE_HEADER_HEIGHT_PT - GROUP_HEADER_HEIGHT_PT;
    return Math.max(MIN_ROWS_PER_PAGE, Math.floor(available / DATA_ROW_HEIGHT_PT));
});

const DEFAULT_PREPARED_BY = 'NUR-AINA S. IBRAHIM';
const DEFAULT_PREPARED_BY_POSITION = 'Program Manager';
const DEFAULT_PREPARED_BY_OFFICE = 'YAKAP sa Edukasyon';
const DEFAULT_APPROVED_BY = 'AMY ROA ALVAREZ';
const DEFAULT_APPROVED_BY_POSITION = 'Governor';

const resolvedReportTitle = computed(() => props.reportTitle?.trim() || '<p><strong>Request for Scholarship Approval</strong></p>');
const resolvedPreparedBy = computed(() => props.preparedBy?.trim() || DEFAULT_PREPARED_BY);
const resolvedPreparedByPosition = computed(() => props.preparedByPosition?.trim() || DEFAULT_PREPARED_BY_POSITION);
const resolvedPreparedByOffice = computed(() => props.preparedByOffice?.trim() || DEFAULT_PREPARED_BY_OFFICE);
const resolvedApprovedBy = computed(() => props.approvedBy?.trim() || DEFAULT_APPROVED_BY);
const resolvedApprovedByPosition = computed(() => props.approvedByPosition?.trim() || DEFAULT_APPROVED_BY_POSITION);
const resolvedBudgetProgram = computed(() => props.budgetProgram?.trim() || props.budgetAllocation?.program || 'N/A');
const explicitBudgetProgramLabel = computed(() => props.budgetProgram?.trim() || '');
const budgetProgramFilterId = computed(() => {
    const programId = props.budgetAllocation?.program_id;
    return programId === null || programId === undefined || programId === '' ? '' : String(programId);
});

// ── Uniformity of hoistable attributes ──────────────────────────────
function schoolKey(record) {
    return String(record?.school?.id ?? record?.school?.name ?? record?.school?.shortname ?? '').trim().toLowerCase();
}
function programKey(record) {
    return String(record?.program?.id ?? record?.program?.name ?? record?.program?.shortname ?? '').trim().toLowerCase();
}
function courseKey(record) {
    return String(record?.course?.id ?? record?.course?.name ?? record?.course?.shortname ?? '').trim().toLowerCase();
}
function uniqueCount(getter) {
    const set = new Set((props.records || []).map(getter));
    return set.size;
}

const schoolUniform = computed(() => props.records.length > 0 && uniqueCount(schoolKey) === 1);
const programUniform = computed(() => props.records.length > 0 && uniqueCount(programKey) === 1);
const courseUniform = computed(() => props.records.length > 0 && uniqueCount(courseKey) === 1);

// School is already the section heading when grouped by school, so the data
// column would just repeat it — show Course instead in that case. Any other
// grouping (course/program/etc.) keeps the School column as before.
const showSchoolColumn = computed(() => props.groupBy !== 'school' && !schoolUniform.value);
const showCourseColumn = computed(() => props.groupBy === 'school' && !courseUniform.value);
const showProgramColumn = computed(() => !programUniform.value);

const firstRecord = computed(() => props.records[0] || null);
const uniformSchoolLabel = computed(() => firstRecord.value?.school?.name || firstRecord.value?.school?.shortname || '');
const uniformProgramLabel = computed(() => firstRecord.value?.program?.name || firstRecord.value?.program?.shortname || '');
const uniformAcademicYear = computed(() => firstRecord.value?.academic_year || '');
const uniformTerm = computed(() => firstRecord.value?.term || '');

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
    return [scholar?.program, scholar?.program_name, scholar?.program_shortname]
        .filter(Boolean)
        .some((programValue) => String(programValue).trim().toLowerCase() === programLabel);
}

function sortRecords(records) {
    return [...records].sort((a, b) => {
        const courseA = (a.course?.name || a.course?.shortname || '').toLowerCase();
        const courseB = (b.course?.name || b.course?.shortname || '').toLowerCase();
        if (courseA !== courseB) return courseA.localeCompare(courseB);

        const yearA = parseInt(a.year_level) || 0;
        const yearB = parseInt(b.year_level) || 0;
        if (yearA !== yearB) return yearA - yearB;

        const nameA = (a.profile?.last_name || '').toLowerCase();
        const nameB = (b.profile?.last_name || '').toLowerCase();
        if (nameA !== nameB) return nameA.localeCompare(nameB);

        const firstA = (a.profile?.first_name || '').toLowerCase();
        const firstB = (b.profile?.first_name || '').toLowerCase();
        return firstA.localeCompare(firstB);
    });
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
    return Number.isFinite(terms) ? `${terms}` : '';
}

function fmtCompletionYear(record) {
    return record?.projected_completion_year ?? '';
}

// "Total amount for this request" is the sum of each scholar's one-term
// (one-semester) grant — record.grant_amount, projected server-side by
// ScholarshipExpenseProjectionService (grant_amount_unit is always
// 'per_term') — not a multi-term/academic-year projection.
function sumCurrentAcademicYearEstimatedGrant(records) {
    return records.reduce((sum, record) => {
        const grantAmount = Number(record?.grant_amount);
        return sum + (Number.isFinite(grantAmount) ? grantAmount : 0);
    }, 0);
}

// ── Grouping + pagination ───────────────────────────────────────────
const groupedData = computed(() => {
    const groups = {};
    for (const record of props.records) {
        let key;
        if (props.groupBy === 'program') key = record.program?.shortname || 'N/A';
        else if (props.groupBy === 'school') key = record.school?.name || record.school?.shortname || 'N/A';
        else if (props.groupBy === 'course') key = record.course?.name || record.course?.shortname || 'N/A';
        else if (props.groupBy === 'interviewer') key = record.interviewer?.name || 'N/A';
        else key = 'All';

        if (!groups[key]) {
            groups[key] = [];
        }
        groups[key].push(record);
    }
    for (const key of Object.keys(groups)) {
        groups[key] = sortRecords(groups[key]);
    }
    return groups;
});

const groupedPaginatedRecords = computed(() => {
    const pages = [];
    let currentPage = { groups: {} };
    let currentPageRows = 0;

    const sortedGroups = Object.entries(groupedData.value).sort(([a], [b]) => a.localeCompare(b));

    for (const [groupName, groupRecords] of sortedGroups) {
        const rowLimit = pages.length === 0 ? FIRST_PAGE_ROW_LIMIT.value : NEXT_PAGE_ROW_LIMIT.value;
        if (currentPageRows + groupRecords.length > rowLimit && currentPageRows > 0) {
            pages.push(currentPage);
            currentPage = { groups: {} };
            currentPageRows = 0;
        }
        currentPage.groups[groupName] = groupRecords;
        currentPageRows += groupRecords.length;
    }

    if (Object.keys(currentPage.groups).length > 0) {
        pages.push(currentPage);
    }

    return pages;
});

// Row count on the last page. The trailing budget-allocation/signatories
// block is only left to flow directly under the table when that page is
// nearly empty (a single row) — anything more and it's forced onto its own
// fresh page so it never has to compete for room with a fuller table.
const lastPageRowCount = computed(() => {
    const lastPage = groupedPaginatedRecords.value[groupedPaginatedRecords.value.length - 1];
    if (!lastPage) return 0;
    return Object.values(lastPage.groups).reduce((sum, group) => sum + group.length, 0);
});
const forceTrailingBlockToNextPage = computed(() => lastPageRowCount.value > 1);

// ── Budget allocation ───────────────────────────────────────────────
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
const approvedScholarsScopeSuffix = computed(() => {
    const parts = [];
    if (approvedScholarsCalendarYearLabel.value) {
        parts.push(`CY ${approvedScholarsCalendarYearLabel.value}`);
    }
    return parts.length ? ` (${parts.join(' · ')})` : '';
});
const approvedScholarsToDate = computed(() => {
    if (approvedScholarsScopedToProgram.value.length) {
        return approvedScholarsScopedToProgram.value.length;
    }
    return Number(props.budgetAllocation?.approved_scholars_to_date ?? 0) || 0;
});
const totalCurrentAcademicYearGrant = computed(() => sumCurrentAcademicYearEstimatedGrant(props.records));
// total_allotment/disbursed are frozen in the budget_allocation snapshot at
// request creation (see RecommendationListService::normalizeBudgetAllocation)
// and never re-pulled from a live allocation lookup once saved, so this
// stays fixed as recorded instead of recomputing on every view/print.
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
</script>

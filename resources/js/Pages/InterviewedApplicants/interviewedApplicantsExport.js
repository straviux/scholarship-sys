import moment from 'moment';
import * as XLSX from 'xlsx';

import { renderVueTemplate } from '@/composables/usePdfPrint';
import InterviewedApplicantsTemplate from './Pdf/InterviewedApplicantsTemplate.vue';
import RecommendationListTemplate from './Pdf/RecommendationListTemplate.vue';
import { buildInterviewedApplicantsPdfDoc } from './Pdf/pdf-styles';

const DEFAULT_PREPARED_BY = 'NUR-AINA S. IBRAHIM';
const DEFAULT_PREPARED_BY_POSITION = 'Program Manager';
const DEFAULT_PREPARED_BY_OFFICE = 'YAKAP sa Edukasyon';
const DEFAULT_APPROVED_BY = 'AMY ROA ALVAREZ';
const DEFAULT_APPROVED_BY_POSITION = 'Governor';

function compareApplicantsByName(left, right) {
    const leftLastName = left?.profile?.last_name || '';
    const rightLastName = right?.profile?.last_name || '';
    const lastNameComparison = leftLastName.localeCompare(rightLastName, undefined, { sensitivity: 'base' });

    if (lastNameComparison !== 0) {
        return lastNameComparison;
    }

    const leftFirstName = left?.profile?.first_name || '';
    const rightFirstName = right?.profile?.first_name || '';
    return leftFirstName.localeCompare(rightFirstName, undefined, { sensitivity: 'base' });
}

function normalizeRecords(records = []) {
    return [...records].sort(compareApplicantsByName);
}

function formatApplicantName(record) {
    const lastName = record?.profile?.last_name || '—';
    const firstName = record?.profile?.first_name || '';
    return `${lastName}, ${firstName}`.trim();
}

function formatGrantProvision(value) {
    if (!value) {
        return '—';
    }

    if (typeof value === 'string' && !value.includes('_')) {
        return value;
    }

    return value
        .toString()
        .split('_')
        .filter(Boolean)
        .map(part => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function formatProjectedTerms(value) {
    const terms = Number(value);
    return Number.isFinite(terms) ? `${terms}` : 'Not configured';
}

function formatProjectedExpense(value) {
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

function formatDate(value) {
    return value ? moment(value).format('MMM DD, YYYY') : '—';
}

export function printInterviewedApplicantsSelection({ records = [], preparedBy = DEFAULT_PREPARED_BY } = {}) {
    const normalizedRecords = normalizeRecords(records);
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        return false;
    }

        const bodyHtml = renderVueTemplate(InterviewedApplicantsTemplate, {
        records: normalizedRecords,
        reportType: 'list',
        groupBy: 'none',
        today: moment().format('MMMM D, YYYY'),
        preparedBy: '',
        preparedByPosition: '',
        preparedByOffice: '',
        approvedBy: '',
        approvedByPosition: '',
        includeProjectedColumns: false,
        includeInterviewColumns: true,
        includeEndorsedBy: false,
        showRemarks: false,
        reportTitle: 'Selected Interviewed Applicants Report',
    });

    const title = 'Selected Interviewed Applicants Report';

    printWindow.document.write(buildInterviewedApplicantsPdfDoc(bodyHtml, title, 'a4-landscape', true));
    printWindow.document.close();

    return true;
}

export function printRecommendationList({ recommendationList = null } = {}) {
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        return false;
    }

    const normalizedRecords = normalizeRecords(recommendationList?.records || []);
    const requestDateLabel = recommendationList?.request_date
        ? moment(recommendationList.request_date).format('MMMM D, YYYY')
        : recommendationList?.created_at
            ? moment(recommendationList.created_at).format('MMMM D, YYYY')
            : moment().format('MMMM D, YYYY');
    const bodyHtml = renderVueTemplate(RecommendationListTemplate, {
        records: normalizedRecords,
        today: requestDateLabel,
        preparedBy: recommendationList?.prepared_by || DEFAULT_PREPARED_BY,
        preparedByPosition: recommendationList?.prepared_by_position || DEFAULT_PREPARED_BY_POSITION,
        preparedByOffice: recommendationList?.prepared_by_office || DEFAULT_PREPARED_BY_OFFICE,
        approvedBy: recommendationList?.approved_by || DEFAULT_APPROVED_BY,
        approvedByPosition: recommendationList?.approved_by_position || DEFAULT_APPROVED_BY_POSITION,
        budgetProgram: recommendationList?.budget_program || '',
        budgetAllocation: recommendationList?.budget_allocation || null,
        highlightJpmMembers: Boolean(recommendationList?.highlight_jpm_members),
        includeEndorsedBy: Boolean(recommendationList?.include_endorsed_by),
        showRemarks: Boolean(recommendationList?.show_remarks),
        reportTitle: recommendationList?.report_title || 'RECOMMENDATION LIST FOR APPROVAL',
        listNumber: recommendationList?.list_number || '',
    });

    const title = recommendationList?.list_number
        ? `Recommendation List ${recommendationList.list_number}`
        : 'Recommendation List';

    printWindow.document.write(buildInterviewedApplicantsPdfDoc(bodyHtml, title, 'a4-landscape', true));
    printWindow.document.close();

    return true;
}

export function exportInterviewedApplicantsExcel({ records = [] } = {}) {
    const normalizedRecords = normalizeRecords(records);
    const generatedAt = moment().format('MMMM DD, YYYY h:mm A');
    const workbook = XLSX.utils.book_new();

    const rows = [
        ['Interviewed Applicants Report'],
        [`Generated: ${generatedAt}`],
        [`Total Records: ${normalizedRecords.length}`],
        [],
                [
            '#',
            'Name',
            'Program',
            'School',
            'Course',
            'Year Level',
            'Term',
            'Academic Year',
            'Grant Provision',
            'Recommendation',
            'Interview Date',
            'Interviewed By',
        ],
        ...normalizedRecords.map((record, index) => [
            index + 1,
            formatApplicantName(record),
            record?.program?.shortname || '—',
            record?.school?.name || record?.school?.shortname || '—',
            record?.course?.name || record?.course?.shortname || '—',
            record?.year_level || '—',
            record?.term || '—',
            record?.academic_year || '—',
            formatGrantProvision(record?.grant_provision_label || record?.grant_provision),
            record?.recommendation || '—',
            formatDate(record?.interviewed_at),
            record?.interviewer?.name || '—',
        ]),
    ];

    const worksheet = XLSX.utils.aoa_to_sheet(rows);
    worksheet['!cols'] = [
        { wch: 6 },
        { wch: 28 },
        { wch: 12 },
        { wch: 20 },
        { wch: 24 },
        { wch: 10 },
        { wch: 12 },
        { wch: 14 },
        { wch: 22 },
        { wch: 22 },
        { wch: 16 },
        { wch: 22 },
    ];

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Interviewed Applicants');
    XLSX.writeFile(workbook, `interviewed_applicants_${moment().format('YYYY-MM-DD_HH-mm-ss')}.xlsx`);
}
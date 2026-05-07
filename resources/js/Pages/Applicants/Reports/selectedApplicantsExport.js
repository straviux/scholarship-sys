import moment from 'moment';
import * as XLSX from 'xlsx';

import { renderVueTemplate } from '@/composables/usePdfPrint';
import { stripHtml } from '@/utils/sanitize';
import { getReportCss, getReportPaperConfig } from '@/Pages/Scholarship/Reports/report-styles';

import SelectedApplicantsReportTemplate from './SelectedApplicantsReportTemplate.vue';

const pagedjsPolyfillUrl = '/vendor/pagedjs/paged.polyfill.min.js';

function getFirstGrant(row) {
    if (Array.isArray(row?.scholarship_grant)) {
        return row.scholarship_grant[0] ?? null;
    }

    if (Array.isArray(row?.scholarshipGrant)) {
        return row.scholarshipGrant[0] ?? null;
    }

    return row?.scholarship_grant ?? row?.scholarshipGrant ?? null;
}

function displayName(entity) {
    if (!entity) {
        return '—';
    }

    return entity.shortname || entity.name || '—';
}

function formatGrantProvisionLabel(value) {
    if (!value) {
        return '—';
    }

    const text = value.toString();

    if (!text.includes('_')) {
        return text;
    }

    return text
        .split('_')
        .filter(Boolean)
        .map(part => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function formatDateLabel(value) {
    if (!value) {
        return '—';
    }

    const parsed = moment(value);
    return parsed.isValid() ? parsed.format('MMM DD, YYYY') : '—';
}

function formatApplicantName(record) {
    const base = [record.last_name, record.first_name]
        .filter(Boolean)
        .map(value => value.toUpperCase())
        .join(', ');
    const middle = record.middle_name ? ` ${record.middle_name}` : '';
    const extension = record.extension_name ? ` ${record.extension_name}` : '';

    return `${base}${middle}${extension}`.trim() || '—';
}

function compareValues(left, right) {
    return String(left || '').localeCompare(String(right || ''), undefined, {
        sensitivity: 'base',
        numeric: true,
    });
}

function buildContactNumbers(row) {
    const contacts = [...new Set([
        row?.contact_no,
        row?.contact_no_2,
    ].map(value => value?.toString().trim()).filter(Boolean))];

    return contacts.length > 0 ? contacts.join(' / ') : '—';
}

function buildSummaryRows(records, key) {
    const counts = new Map();

    for (const record of records) {
        const label = record[key] || '—';
        counts.set(label, (counts.get(label) || 0) + 1);
    }

    return [...counts.entries()]
        .map(([label, count]) => [label, count])
        .sort((left, right) => compareValues(left[0], right[0]));
}

function getReportTitle(reportType = 'list') {
    return reportType === 'summary'
        ? 'Selected Applicants Summary Report'
        : 'Selected Applicants Report';
}

function buildReportDocument(bodyHtml, title, pageConfig) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <style>
    body { visibility: hidden; margin: 0; padding: 0; }
    ${getReportCss(pageConfig)}
  </style>
  <script src="${pagedjsPolyfillUrl}"><\/script>
  <script>
    (function () {
      function finalizeRender() {
        document.body.style.visibility = 'visible';
        if (document.body.getAttribute('data-auto-print') === '1') {
          window.print();
        }
      }

      if (window.PagedPolyfill && typeof window.PagedPolyfill.on === 'function') {
        window.PagedPolyfill.on('rendered', finalizeRender);
      } else {
        window.addEventListener('load', function () {
          setTimeout(finalizeRender, 100);
        });
      }
    })();
  <\/script>
</head>
<body>${bodyHtml}</body>
</html>`;
}

export function prepareSelectedApplicantsRecords(selectedRows = []) {
    const normalized = selectedRows.map((row) => {
        const grant = getFirstGrant(row);

        return {
            profile_id: row.profile_id,
            last_name: row.last_name || '',
            first_name: row.first_name || '',
            middle_name: row.middle_name || '',
            extension_name: row.extension_name || '',
            municipality: row.municipality || '—',
            contact_numbers: buildContactNumbers(row),
            program_name: displayName(grant?.program),
            school_name: displayName(grant?.school),
            course_name: displayName(grant?.course),
            year_level: grant?.year_level || '—',
            grant_provision_label: formatGrantProvisionLabel(grant?.grant_provision),
            remarks: stripHtml(row.remarks || '').trim() || '—',
            date_filed: grant?.date_filed || row.date_filed || null,
            date_filed_label: formatDateLabel(grant?.date_filed || row.date_filed || null),
            created_at: row.created_at || null,
        };
    }).sort((left, right) => {
        const leftDate = left.date_filed ? moment(left.date_filed).valueOf() : Number.MAX_SAFE_INTEGER;
        const rightDate = right.date_filed ? moment(right.date_filed).valueOf() : Number.MAX_SAFE_INTEGER;

        if (leftDate !== rightDate) {
            return leftDate - rightDate;
        }

        const leftCreated = left.created_at ? moment(left.created_at).valueOf() : Number.MAX_SAFE_INTEGER;
        const rightCreated = right.created_at ? moment(right.created_at).valueOf() : Number.MAX_SAFE_INTEGER;

        if (leftCreated !== rightCreated) {
            return leftCreated - rightCreated;
        }

        return compareValues(formatApplicantName(left), formatApplicantName(right));
    });

    const programSequences = new Map();
    const schoolSequences = new Map();
    const courseSequences = new Map();

    return normalized.map((record, index) => {
        const programKey = record.program_name || '—';
        const schoolKey = record.school_name || '—';
        const courseKey = record.course_name || '—';

        const programSequence = (programSequences.get(programKey) || 0) + 1;
        const schoolSequence = (schoolSequences.get(schoolKey) || 0) + 1;
        const courseSequence = (courseSequences.get(courseKey) || 0) + 1;

        programSequences.set(programKey, programSequence);
        schoolSequences.set(schoolKey, schoolSequence);
        courseSequences.set(courseKey, courseSequence);

        return {
            ...record,
            overall_sequence: index + 1,
            program_sequence: programSequence,
            school_sequence: schoolSequence,
            course_sequence: courseSequence,
        };
    });
}

export function printSelectedApplicantsReport({
    selectedRows = [],
    reportType = 'list',
    paperSize = 'A4',
    orientation = 'landscape',
    includeRemarks = false,
    includeGrantProvision = true,
}) {
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        return false;
    }

    const records = prepareSelectedApplicantsRecords(selectedRows);
    const generatedAt = moment().format('MMMM DD, YYYY — h:mm A');
    const pageConfig = getReportPaperConfig(paperSize, orientation);
    const bodyHtml = renderVueTemplate(SelectedApplicantsReportTemplate, {
        records,
        reportType,
        options: {
            includeRemarks,
            includeGrantProvision,
        },
        generatedAt,
    });

    const htmlDocument = buildReportDocument(bodyHtml, getReportTitle(reportType), pageConfig)
        .replace('<body>', '<body data-auto-print="1">');

    printWindow.document.write(htmlDocument);
    printWindow.document.close();

    return true;
}

export function exportSelectedApplicantsExcel({
    selectedRows = [],
    reportType = 'list',
    includeRemarks = false,
    includeGrantProvision = true,
}) {
    const records = prepareSelectedApplicantsRecords(selectedRows);
    const generatedAt = moment().format('MMMM DD, YYYY h:mm A');
    const workbook = XLSX.utils.book_new();

    let sheetData = [];
    let columnWidths = [];

    if (reportType === 'summary') {
        sheetData = [
            ['Selected Applicants Summary Report'],
            [`Generated: ${generatedAt}`],
            [`Total Records: ${records.length}`],
            [],
            ['Breakdown by Program'],
            ['Program', 'Count'],
            ...buildSummaryRows(records, 'program_name'),
            [],
            ['Breakdown by School'],
            ['School', 'Count'],
            ...buildSummaryRows(records, 'school_name'),
            [],
            ['Breakdown by Course'],
            ['Course', 'Count'],
            ...buildSummaryRows(records, 'course_name'),
            [],
            ['Breakdown by Municipality'],
            ['Municipality', 'Count'],
            ...buildSummaryRows(records, 'municipality'),
            [],
            ['Breakdown by Year Level'],
            ['Year Level', 'Count'],
            ...buildSummaryRows(records, 'year_level'),
        ];

        columnWidths = [{ wch: 36 }, { wch: 12 }];
    } else {
        const headings = ['#', 'Name', 'Municipality', 'Contact', 'Program', 'School', 'Course', 'Year Level'];

        if (includeGrantProvision) {
            headings.push('Grant Provision');
        }

        if (includeRemarks) {
            headings.push('Remarks');
        }

        headings.push('Date Filed');

        sheetData = [
            ['Selected Applicants Report'],
            [`Generated: ${generatedAt}`],
            [`Total Records: ${records.length}`],
            [],
            headings,
            ...records.map((record) => {
                const row = [
                    record.overall_sequence,
                    formatApplicantName(record),
                    record.municipality,
                    record.contact_numbers,
                    record.program_name,
                    record.school_name,
                    record.course_name,
                    record.year_level,
                ];

                if (includeGrantProvision) {
                    row.push(record.grant_provision_label);
                }

                if (includeRemarks) {
                    row.push(record.remarks);
                }

                row.push(record.date_filed_label);

                return row;
            }),
        ];

        columnWidths = [
            { wch: 6 },
            { wch: 28 },
            { wch: 18 },
            { wch: 18 },
            { wch: 12 },
            { wch: 12 },
            { wch: 20 },
            { wch: 10 },
            ...(includeGrantProvision ? [{ wch: 22 }] : []),
            ...(includeRemarks ? [{ wch: 36 }] : []),
            { wch: 14 },
        ];
    }

    const worksheet = XLSX.utils.aoa_to_sheet(sheetData);
    worksheet['!cols'] = columnWidths;

    XLSX.utils.book_append_sheet(workbook, worksheet, reportType === 'summary' ? 'Summary' : 'Applicants');

    const filename = `selected_applicants_${reportType}_${moment().format('YYYY-MM-DD_HH-mm-ss')}.xlsx`;
    XLSX.writeFile(workbook, filename);
}
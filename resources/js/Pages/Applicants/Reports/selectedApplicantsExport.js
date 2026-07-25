import moment from 'moment';
import ExcelJS from 'exceljs';

import { renderVueTemplate } from '@/composables/usePdfPrint';
import { stripHtml } from '@/utils/sanitize';
import { getReportCss, getReportPaperConfig } from '@/Pages/Scholarship/Reports/report-styles';

import SelectedApplicantsReportTemplate from './SelectedApplicantsReportTemplate.vue';

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

// All report data is displayed in UPPERCASE for consistency (source records
// often mix casing, e.g. lowercase addresses). The em-dash placeholder is
// unaffected.
function upper(value) {
    return (value ?? '').toString().toUpperCase();
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
    // Native browser print — no paged.js. The browser paginates the single
    // table, repeating the thead and preserving table-layout:fixed columns
    // consistently on every page.
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <style>
    ${getReportCss(pageConfig)}
  </style>
  <script>
    window.addEventListener('load', function () {
      if (document.body.getAttribute('data-auto-print') === '1') {
        setTimeout(function () { window.print(); }, 150);
      }
    });
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
            last_name: upper(row.last_name),
            first_name: upper(row.first_name),
            middle_name: upper(row.middle_name),
            extension_name: upper(row.extension_name),
            municipality: upper(row.municipality) || '—',
            contact_numbers: buildContactNumbers(row),
            program_name: upper(displayName(grant?.program)),
            school_name: upper(displayName(grant?.school)),
            course_name: upper(displayName(grant?.course)),
            year_level: upper(grant?.year_level) || '—',
            remarks: upper(stripHtml(row.remarks || '').trim()) || '—',
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
    remarksMode = 'none',
    customTitle = '',
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
            remarksMode,
            customTitle,
            orientation,
            paperSize,
        },
        generatedAt,
    });

    const htmlDocument = buildReportDocument(bodyHtml, getReportTitle(reportType), pageConfig)
        .replace('<body>', '<body data-auto-print="1">');

    printWindow.document.write(htmlDocument);
    printWindow.document.close();

    return true;
}

// ── Excel export (ExcelJS) ──────────────────────────────────────────
// Mirrors the PDF template (SelectedApplicantsReportTemplate.vue): a title
// block flanked by the PGP and YAKAP logos, a shaded/bordered header row and
// bordered data cells. ExcelJS is used (instead of xlsx-js-style) because it
// can embed the logo images.
const ARGB = {
    headerFill: 'FFF3F4F6',
    sectionFill: 'FFF3F4F6',
    borderThin: 'FFD1D5DB',
    borderHeader: 'FF9CA3AF',
    borderLight: 'FFE5E7EB',
};

const thinBorder = (argb) => ({ style: 'thin', color: { argb } });
const allBorders = (argb) => ({
    top: thinBorder(argb),
    left: thinBorder(argb),
    bottom: thinBorder(argb),
    right: thinBorder(argb),
});

/**
 * Strip the rich-text (Quill) custom title down to a single plain-text line
 * for the spreadsheet. Returns '' when the editor was left empty.
 */
function htmlToPlainTitle(html) {
    return (html ?? '')
        .toString()
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/\s+/g, ' ')
        .trim();
}

async function fetchImageDataUrl(url) {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`Failed to load ${url} (${response.status})`);
    }

    const blob = await response.blob();
    return await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });
}

/**
 * Load the two report logos and register them on the workbook. Returns the
 * image ids, or null if they could not be fetched (the export still succeeds,
 * just without logos).
 */
async function loadReportLogos(workbook) {
    try {
        const [pgp, yakap] = await Promise.all([
            fetchImageDataUrl('/images/pgp-logo.png'),
            fetchImageDataUrl('/images/yakap-logo.png'),
        ]);

        return {
            pgpId: workbook.addImage({ base64: pgp, extension: 'png' }),
            yakapId: workbook.addImage({ base64: yakap, extension: 'png' }),
        };
    } catch (error) {
        console.warn('Report logos unavailable for Excel export:', error);
        return null;
    }
}

/**
 * Write the shared title block (org header + report title) into the worksheet,
 * flanked by the PGP/YAKAP logos. Returns the 1-based row index where the
 * following content (header row) should begin.
 */
function writeTitleBlock(worksheet, reportTitle, totalColumns, logos) {
    const lines = [
        { text: 'Republic of the Philippines', size: 11, bold: true },
        { text: 'Provincial Government of Palawan', size: 11, bold: true },
        { text: 'YAKAP SA EDUKASYON', size: 10, bold: false },
        { text: 'Scholarship Program', size: 10, bold: false },
        { text: reportTitle, size: 9, bold: true },
    ];

    lines.forEach((line, index) => {
        const rowIndex = index + 1;
        worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
        const cell = worksheet.getCell(rowIndex, 1);
        cell.value = line.text;
        cell.font = { name: 'Arial', size: line.size, bold: line.bold };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        worksheet.getRow(rowIndex).height = 18;
    });

    if (logos) {
        // Larger logos pulled inward so they sit almost beside the centered
        // title text (roughly a quarter in from each edge).
        const inset = Math.max(totalColumns * 0.25, 1);
        worksheet.addImage(logos.pgpId, {
            tl: { col: inset, row: 0.1 },
            ext: { width: 80, height: 80 },
            editAs: 'oneCell',
        });
        worksheet.addImage(logos.yakapId, {
            tl: { col: Math.max(totalColumns - inset - 1, 0.1), row: 0.1 },
            ext: { width: 80, height: 80 },
            editAs: 'oneCell',
        });
    }

    // One blank spacer row follows the title block before the content.
    return lines.length + 2;
}

function saveWorkbookBuffer(buffer, filename) {
    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

export async function exportSelectedApplicantsExcel({
    selectedRows = [],
    reportType = 'list',
    remarksMode = 'none',
    customTitle = '',
}) {
    const records = prepareSelectedApplicantsRecords(selectedRows);
    const workbook = new ExcelJS.Workbook();
    const logos = await loadReportLogos(workbook);
    const titleText = htmlToPlainTitle(customTitle);

    if (reportType === 'summary') {
        buildSummarySheet(workbook, records, titleText || 'SELECTED APPLICANTS SUMMARY REPORT', logos);
    } else {
        buildListSheet(workbook, records, { remarksMode }, titleText || 'SELECTED APPLICANTS REPORT', logos);
    }

    const buffer = await workbook.xlsx.writeBuffer();
    const filename = `selected_applicants_${reportType}_${moment().format('YYYY-MM-DD_HH-mm-ss')}.xlsx`;
    saveWorkbookBuffer(buffer, filename);
}

/**
 * List report — columns mirror the PDF template's list table exactly:
 * #, Name, Municipality & Contact, Program, School, Course, Level,
 * Date Filed, [Remarks].
 *
 * remarksMode: 'none' hides the column, 'values' shows the remark text,
 * 'blank' adds an empty Remarks column to fill in by hand.
 */
function buildListSheet(workbook, records, { remarksMode = 'none' }, reportTitle, logos) {
    const includeRemarks = remarksMode === 'values' || remarksMode === 'blank';
    const blankRemarks = remarksMode === 'blank';

    const worksheet = workbook.addWorksheet('Applicants');

    const columns = [
        { header: '#', width: 5, align: 'center' },
        { header: 'Name', width: 30, align: 'left', bold: true },
        { header: 'Municipality & Contact', width: 24, align: 'left' },
        { header: 'Program', width: 14, align: 'left' },
        { header: 'School', width: 14, align: 'left' },
        { header: 'Course', width: 18, align: 'left' },
        { header: 'Level', width: 8, align: 'center' },
        { header: 'Date Filed', width: 14, align: 'center' },
    ];
    if (includeRemarks) columns.push({ header: 'Remarks', width: 30, align: 'left' });

    const totalColumns = columns.length;
    worksheet.columns = columns.map(col => ({ width: col.width }));

    const headerRowIndex = writeTitleBlock(worksheet, reportTitle, totalColumns, logos);

    const headerRow = worksheet.getRow(headerRowIndex);
    columns.forEach((col, index) => {
        const cell = headerRow.getCell(index + 1);
        cell.value = col.header;
        cell.font = { name: 'Arial', size: 8, bold: true };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ARGB.headerFill } };
        cell.border = allBorders(ARGB.borderHeader);
    });
    headerRow.height = 22;

    let rowIndex = headerRowIndex + 1;
    for (const record of records) {
        const values = [
            record.overall_sequence,
            formatApplicantName(record),
            `${record.municipality}\n${record.contact_numbers}`,
            record.program_name,
            record.school_name,
            record.course_name,
            record.year_level,
            record.date_filed_label,
        ];
        if (includeRemarks) values.push(blankRemarks ? '' : record.remarks);

        const row = worksheet.getRow(rowIndex);
        values.forEach((value, index) => {
            const cell = row.getCell(index + 1);
            cell.value = value;
            cell.font = { name: 'Arial', size: 8, bold: columns[index].bold === true };
            cell.alignment = {
                horizontal: columns[index].align === 'center' ? 'center' : 'left',
                vertical: 'top',
                wrapText: true,
            };
            cell.border = allBorders(ARGB.borderThin);
        });
        rowIndex += 1;
    }
}

/**
 * Summary report — mirrors the PDF summary: an overview block followed by
 * breakdown tables (label + count) for program / school / course /
 * municipality / year level.
 */
function buildSummarySheet(workbook, records, reportTitle, logos) {
    const worksheet = workbook.addWorksheet('Summary');
    const totalColumns = 2;
    worksheet.columns = [{ width: 40 }, { width: 14 }];

    let rowIndex = writeTitleBlock(worksheet, reportTitle, totalColumns, logos);

    const sectionHeader = (label) => {
        worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
        const cell = worksheet.getCell(rowIndex, 1);
        cell.value = label;
        cell.font = { name: 'Arial', size: 8, bold: true };
        cell.alignment = { horizontal: 'left', vertical: 'middle' };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ARGB.sectionFill } };
        worksheet.getCell(rowIndex, 1).border = allBorders(ARGB.borderThin);
        worksheet.getCell(rowIndex, 2).border = allBorders(ARGB.borderThin);
        rowIndex += 1;
    };

    const kvRow = (label, value) => {
        const labelCell = worksheet.getCell(rowIndex, 1);
        labelCell.value = label;
        labelCell.font = { name: 'Arial', size: 8 };
        labelCell.alignment = { vertical: 'middle' };
        labelCell.border = { bottom: thinBorder(ARGB.borderLight) };

        const valueCell = worksheet.getCell(rowIndex, 2);
        valueCell.value = value;
        valueCell.font = { name: 'Arial', size: 8, bold: true };
        valueCell.alignment = { horizontal: 'right', vertical: 'middle' };
        valueCell.border = { bottom: thinBorder(ARGB.borderLight) };
        rowIndex += 1;
    };

    sectionHeader('OVERVIEW');
    const overview = [
        ['Total Records', records.length],
        ['Programs', new Set(records.map(r => r.program_name || '—')).size],
        ['Schools', new Set(records.map(r => r.school_name || '—')).size],
        ['Courses', new Set(records.map(r => r.course_name || '—')).size],
        ['Municipalities', new Set(records.map(r => r.municipality || '—')).size],
    ];
    for (const [label, value] of overview) kvRow(label, value);
    rowIndex += 1;

    const sections = [
        ['Breakdown by Program', 'program_name'],
        ['Breakdown by School', 'school_name'],
        ['Breakdown by Course', 'course_name'],
        ['Breakdown by Municipality', 'municipality'],
        ['Breakdown by Year Level', 'year_level'],
    ];

    for (const [title, key] of sections) {
        const rows = buildSummaryRows(records, key);
        if (rows.length === 0) continue;

        sectionHeader(title.toUpperCase());

        const labelHead = worksheet.getCell(rowIndex, 1);
        labelHead.value = 'Label';
        labelHead.font = { name: 'Arial', size: 8, bold: true };
        labelHead.border = { bottom: thinBorder(ARGB.borderThin) };
        const countHead = worksheet.getCell(rowIndex, 2);
        countHead.value = 'Count';
        countHead.font = { name: 'Arial', size: 8, bold: true };
        countHead.alignment = { horizontal: 'right' };
        countHead.border = { bottom: thinBorder(ARGB.borderThin) };
        rowIndex += 1;

        for (const [label, count] of rows) kvRow(label, count);
        rowIndex += 1;
    }
}

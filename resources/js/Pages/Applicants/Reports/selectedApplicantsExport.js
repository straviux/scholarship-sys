import moment from 'moment';
import ExcelJS from 'exceljs';

import { renderVueTemplate } from '@/composables/usePdfPrint';
import { stripHtml } from '@/utils/sanitize';
import { getReportCss, getReportPaperConfig } from '@/Pages/Scholarship/Reports/report-styles';
import { getSystemOptionLabel } from '@/composables/useSystemOptions';
import { buildGroupedRenderItems } from './reportGrouping';

import SelectedApplicantsReportTemplate from './SelectedApplicantsReportTemplate.vue';

function isJpmMember(row) {
    return Boolean(
        row?.is_jpm_member
        || row?.is_father_jpm
        || row?.is_mother_jpm
        || row?.is_guardian_jpm,
    );
}

function projectedExpenseLabel(grant, row) {
    const formatted = grant?.projected_total_expense_formatted ?? row?.projected_total_expense_formatted;
    if (formatted) {
        return `₱${formatted}`;
    }

    const raw = grant?.projected_total_expense ?? row?.projected_total_expense;
    return (raw === null || raw === undefined || raw === '')
        ? '—'
        : `₱${Number(raw).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

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

export function prepareSelectedApplicantsRecords(selectedRows = [], { jpmFirst = false, sortBy = 'date_filed' } = {}) {
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
            grant_provision: upper(getSystemOptionLabel('grant_provision', grant?.grant_provision)) || '—',
            remarks: upper(stripHtml(row.remarks || '').trim()) || '—',
            is_jpm: isJpmMember(row),
            projected_expense: projectedExpenseLabel(grant, row),
            projected_terms: (grant?.projected_term_count ?? grant?.projected_remaining_terms
                ?? row?.projected_term_count ?? '—'),
            projected_completion: (grant?.projected_completion_year ?? row?.projected_completion_year ?? '—'),
            date_filed: grant?.date_filed || row.date_filed || null,
            date_filed_label: formatDateLabel(grant?.date_filed || row.date_filed || null),
            created_at: row.created_at || null,
        };
    });

    // Sort comparators: date filed (oldest first, then created, then name) and
    // alphabetical (last name, first name — falling back to date filed).
    const dateFiledComparator = (left, right) => {
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
    };

    const nameComparator = (left, right) =>
        compareValues(formatApplicantName(left), formatApplicantName(right));

    const sorted = [...normalized].sort(sortBy === 'name'
        ? (left, right) => nameComparator(left, right) || dateFiledComparator(left, right)
        : dateFiledComparator);

    // When highlighting JPM members, list them first (stable within each group).
    const ordered = jpmFirst
        ? [...sorted.filter(record => record.is_jpm), ...sorted.filter(record => !record.is_jpm)]
        : sorted;

    const programSequences = new Map();
    const schoolSequences = new Map();
    const courseSequences = new Map();

    return ordered.map((record, index) => {
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
    showSignatories = false,
    showProjected = false,
    highlightJpm = false,
    showGrantProvision = false,
    groupBy = 'none',
    groupBySub = 'none',
    sortBy = 'date_filed',
}) {
    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        return false;
    }

    const records = prepareSelectedApplicantsRecords(selectedRows, { jpmFirst: highlightJpm, sortBy });
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
            showSignatories,
            showProjected,
            highlightJpm,
            showGrantProvision,
            groupBy,
            groupBySub,
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

// Fixed (non-customizable) report signatories.
const REPORT_SIGNATORIES = {
    preparedBy: 'NUR-AINA S. IBRAHIM',
    preparedByPosition: 'Program Manager',
    preparedByOffice: 'YAKAP sa Edukasyon',
    approvedBy: 'AMY ROA ALVAREZ',
    approvedByPosition: 'Governor',
};

/**
 * Write the fixed "Prepared by / Approved by" signatory block at the bottom of a
 * worksheet. `startRow` is the 1-based row to begin at; the left column holds the
 * Prepared-by signatory and the right side (last column) holds Approved-by.
 */
function writeSignatoryBlock(worksheet, startRow, totalColumns) {
    const rightCol = Math.max(totalColumns, 2);
    let row = startRow + 2; // spacer rows above the block

    const labelRow = worksheet.getRow(row);
    labelRow.getCell(1).value = 'Prepared by:';
    labelRow.getCell(1).font = { name: 'Arial', size: 9, bold: true };
    labelRow.getCell(rightCol).value = 'Approved by:';
    labelRow.getCell(rightCol).font = { name: 'Arial', size: 9, bold: true };
    labelRow.getCell(rightCol).alignment = { horizontal: 'right' };
    row += 3; // space for the signature line

    const nameRow = worksheet.getRow(row);
    nameRow.getCell(1).value = REPORT_SIGNATORIES.preparedBy;
    nameRow.getCell(1).font = { name: 'Arial', size: 9, bold: true };
    nameRow.getCell(1).border = { top: thinBorder('FF000000') };
    nameRow.getCell(rightCol).value = REPORT_SIGNATORIES.approvedBy;
    nameRow.getCell(rightCol).font = { name: 'Arial', size: 9, bold: true };
    nameRow.getCell(rightCol).alignment = { horizontal: 'right' };
    nameRow.getCell(rightCol).border = { top: thinBorder('FF000000') };
    row += 1;

    const posRow = worksheet.getRow(row);
    posRow.getCell(1).value = REPORT_SIGNATORIES.preparedByPosition;
    posRow.getCell(1).font = { name: 'Arial', size: 8 };
    posRow.getCell(rightCol).value = REPORT_SIGNATORIES.approvedByPosition;
    posRow.getCell(rightCol).font = { name: 'Arial', size: 8 };
    posRow.getCell(rightCol).alignment = { horizontal: 'right' };
    row += 1;

    const officeRow = worksheet.getRow(row);
    officeRow.getCell(1).value = REPORT_SIGNATORIES.preparedByOffice;
    officeRow.getCell(1).font = { name: 'Arial', size: 8 };
    row += 3; // space for the date line under Approved by

    const dateRow = worksheet.getRow(row);
    dateRow.getCell(rightCol).value = 'Date';
    dateRow.getCell(rightCol).font = { name: 'Arial', size: 8 };
    dateRow.getCell(rightCol).alignment = { horizontal: 'right' };
    dateRow.getCell(rightCol).border = { top: thinBorder('FF000000') };
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
    showSignatories = false,
    showProjected = false,
    highlightJpm = false,
    showGrantProvision = false,
    groupBy = 'none',
    groupBySub = 'none',
    sortBy = 'date_filed',
}) {
    const records = prepareSelectedApplicantsRecords(selectedRows, { jpmFirst: highlightJpm, sortBy });
    const workbook = new ExcelJS.Workbook();
    const logos = await loadReportLogos(workbook);
    const titleText = htmlToPlainTitle(customTitle);

    if (reportType === 'summary') {
        buildSummarySheet(workbook, records, titleText || 'SELECTED APPLICANTS SUMMARY REPORT', logos, showSignatories);
    } else {
        buildListSheet(workbook, records, { remarksMode, showProjected, highlightJpm, showGrantProvision, groupBy, groupBySub }, titleText || 'SELECTED APPLICANTS REPORT', logos, showSignatories);
    }

    const buffer = await workbook.xlsx.writeBuffer();
    const filename = `selected_applicants_${reportType}_${moment().format('YYYY-MM-DD_HH-mm-ss')}.xlsx`;
    saveWorkbookBuffer(buffer, filename);
}

/**
 * List report — columns mirror the PDF template's list table exactly:
 * #, Name, Municipality, Contact No., Program, School, Course, Year,
 * Date Filed, [Remarks].
 *
 * remarksMode: 'none' hides the column, 'values' shows the remark text,
 * 'blank' adds an empty Remarks column to fill in by hand.
 */
function buildListSheet(workbook, records, { remarksMode = 'none', showProjected = false, highlightJpm = false, showGrantProvision = false, groupBy = 'none', groupBySub = 'none' }, reportTitle, logos, showSignatories = false) {
    const includeRemarks = remarksMode === 'values' || remarksMode === 'blank';
    const blankRemarks = remarksMode === 'blank';

    const worksheet = workbook.addWorksheet('Applicants');

    // Each column carries a value(record) accessor so optional columns can be
    // inserted without positional bookkeeping.
    const columns = [
        { header: '#', width: 5, align: 'center', value: r => r.overall_sequence },
        { header: 'Name', width: 30, align: 'left', bold: true, value: r => formatApplicantName(r) },
        { header: 'Municipality', width: 16, align: 'left', value: r => r.municipality },
        { header: 'Contact No.', width: 16, align: 'left', value: r => r.contact_numbers },
        { header: 'School', width: 14, align: 'left', value: r => r.school_name },
        { header: 'Course', width: 18, align: 'left', value: r => r.course_name },
        { header: 'Year', width: 8, align: 'center', value: r => r.year_level },
    ];
    if (showGrantProvision) columns.push({ header: 'Grant Provision', width: 22, align: 'left', value: r => r.grant_provision });
    columns.push({ header: 'Date Filed', width: 14, align: 'center', value: r => r.date_filed_label });
    if (showProjected) {
        columns.push({ header: 'Projected Expense', width: 16, align: 'right', value: r => r.projected_expense });
        columns.push({ header: 'Proj. Terms', width: 10, align: 'center', value: r => r.projected_terms });
        columns.push({ header: 'Proj. Completion', width: 14, align: 'center', value: r => r.projected_completion });
    }
    if (includeRemarks) columns.push({ header: 'Remarks', width: 30, align: 'left', value: r => (blankRemarks ? '' : r.remarks) });

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
    const renderItems = buildGroupedRenderItems(records, groupBy, groupBySub);
    for (const item of renderItems) {
        if (item.type === 'group') {
            const isMain = item.level === 1;
            worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
            const row = worksheet.getRow(rowIndex);
            const cell = row.getCell(1);
            cell.value = `${item.label} (${item.count})`;
            cell.font = { name: 'Arial', size: isMain ? 9 : 8, bold: true, color: { argb: isMain ? 'FF1F2937' : 'FF374151' } };
            cell.alignment = { horizontal: 'left', vertical: 'middle', indent: isMain ? 0 : 1 };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: isMain ? 'FFE5E7EB' : 'FFF3F4F6' } };
            for (let c = 1; c <= totalColumns; c++) {
                row.getCell(c).border = allBorders(ARGB.borderHeader);
            }
            row.height = isMain ? 18 : 16;
            rowIndex += 1;
            continue;
        }

        const record = item.record;
        const highlight = highlightJpm && record.is_jpm;
        const row = worksheet.getRow(rowIndex);
        columns.forEach((col, index) => {
            const cell = row.getCell(index + 1);
            cell.value = col.value(record);
            cell.font = { name: 'Arial', size: 8, bold: col.bold === true || highlight, color: highlight ? { argb: 'FF166534' } : undefined };
            cell.alignment = {
                horizontal: col.align === 'center' ? 'center' : (col.align === 'right' ? 'right' : 'left'),
                vertical: 'top',
                wrapText: true,
            };
            cell.border = allBorders(ARGB.borderThin);
            if (highlight) {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFEFFDF4' } };
            }
        });
        rowIndex += 1;
    }

    if (showSignatories) {
        writeSignatoryBlock(worksheet, rowIndex, totalColumns);
    }
}

/**
 * Summary report — mirrors the PDF summary: an overview block followed by
 * breakdown tables (label + count) for program / school / course /
 * municipality / year level.
 */
function buildSummarySheet(workbook, records, reportTitle, logos, showSignatories = false) {
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

    if (showSignatories) {
        writeSignatoryBlock(worksheet, rowIndex, totalColumns);
    }
}

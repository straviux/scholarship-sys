import moment from 'moment';
import ExcelJS from 'exceljs';

import { renderVueTemplate } from '@/composables/usePdfPrint';
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

function formatProjectedTerms(value) {
    const terms = Number(value);
    return Number.isFinite(terms) ? `${terms}` : 'Not configured';
}

export function buildRecommendationListHtml({ recommendationList = null, paperSize = null, orientation = null, includeInterviewColumns = null, includeProjectedColumns = null } = {}) {
    const normalizedRecords = normalizeRecords(recommendationList?.records || []);
    const requestDateLabel = recommendationList?.request_date
        ? moment(recommendationList.request_date).format('MMMM D, YYYY')
        : recommendationList?.created_at
            ? moment(recommendationList.created_at).format('MMMM D, YYYY')
            : moment().format('MMMM D, YYYY');

    // Prefer function args; fall back to recommendationList data; then defaults
    const finalPaperSize = paperSize || recommendationList?.paper_size || 'A4';
    const finalOrientation = orientation || recommendationList?.orientation || 'landscape';

    const bodyHtml = renderVueTemplate(RecommendationListTemplate, {
        records: normalizedRecords,
        groupBy: recommendationList?.group_by || 'course',
        paperSize: finalPaperSize,
        orientation: finalOrientation,
        today: requestDateLabel,
        preparedBy: recommendationList?.prepared_by || DEFAULT_PREPARED_BY,
        preparedByPosition: recommendationList?.prepared_by_position || DEFAULT_PREPARED_BY_POSITION,
        preparedByOffice: recommendationList?.prepared_by_office || DEFAULT_PREPARED_BY_OFFICE,
        approvedBy: recommendationList?.approved_by || DEFAULT_APPROVED_BY,
        approvedByPosition: recommendationList?.approved_by_position || DEFAULT_APPROVED_BY_POSITION,
        budgetProgram: recommendationList?.budget_program || '',
        budgetAllocation: recommendationList?.budget_allocation || null,
        highlightJpmMembers: Boolean(recommendationList?.highlight_jpm_members),
        showRemarks: Boolean(recommendationList?.show_remarks),
        reportTitle: recommendationList?.report_title || 'Request for Scholarship Approval',
        listNumber: recommendationList?.list_number || '',
    });

    const title = recommendationList?.list_number
        ? `Recommendation List ${recommendationList.list_number}`
        : 'Recommendation List';

    const paperMap = { landscape: { A4: 'a4-landscape', Letter: 'letter-landscape', Legal: 'landscape' }, portrait: { A4: 'a4', Letter: 'letter', Legal: 'long' } };
    const ps = paperMap[finalOrientation]?.[finalPaperSize] || 'a4-landscape';
    return buildInterviewedApplicantsPdfDoc(bodyHtml, title, ps);
}

// ── Recommendation List Excel export (ExcelJS) ─────────────────────────
// Mirrors the PDF layout of InterviewedApplicantsTemplate.vue as rendered by
// buildRecommendationListHtml(): letterhead with logos, title block, grant /
// request-no. line, grouped tables with a two-row header, budget-allocation
// block and signatories. ExcelJS is used (instead of xlsx) because it
// supports merged cells, borders and embedded images. The computation
// helpers below are ported from InterviewedApplicantsTemplate.vue — keep
// them in sync with the template.

const RL_THIN = { style: 'thin', color: { argb: 'FF000000' } };
const RL_MEDIUM = { style: 'medium', color: { argb: 'FF000000' } };
const RL_ALL_BORDERS = { top: RL_THIN, left: RL_THIN, bottom: RL_THIN, right: RL_THIN };
const RL_HEADER_FILL = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0F0F0' } };
const RL_LABEL_FILL = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8F8F8' } };
const RL_FONT = 'Arial';

function rlFmtCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);
}

function rlFormatApplicantName(record) {
    const lastName = record?.profile?.last_name || '';
    const firstName = record?.profile?.first_name || '';
    const middleInitial = record?.profile?.middle_name
        ? `${record.profile.middle_name.trim().charAt(0).toUpperCase()}.`
        : '';

    return [lastName + ',', firstName, middleInitial].filter(Boolean).join(' ').trim();
}

// Uniformity checks — mirrors RecommendationListTemplate.vue exactly: a
// column (School/Program/Academic Year) is hidden and hoisted into the
// header text instead whenever every record shares the same value.
function rlSchoolKey(record) {
    return String(record?.school?.id ?? record?.school?.name ?? record?.school?.shortname ?? '').trim().toLowerCase();
}
function rlProgramKey(record) {
    return String(record?.program?.id ?? record?.program?.name ?? record?.program?.shortname ?? '').trim().toLowerCase();
}
function rlAyTermKey(record) {
    return `${String(record?.academic_year ?? '').trim().toLowerCase()}||${String(record?.term ?? '').trim().toLowerCase()}`;
}
function rlUniqueCount(records, getter) {
    return new Set(records.map(getter)).size;
}

function rlHasJpmMember(record) {
    return Boolean(
        record?.profile?.is_jpm_member
        || record?.profile?.is_father_jpm
        || record?.profile?.is_mother_jpm
        || record?.profile?.is_guardian_jpm,
    );
}

function rlSortRecords(records) {
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

function rlParseGrantProvision(value) {
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
        return { name: normalizedValue || '—', amount: '' };
    }

    return {
        name: amountMatch[1].trim(),
        amount: amountMatch[2].replace(/\bPHP\b/g, '').replace(/\s{2,}/g, ' ').trim(),
    };
}

function rlIsTrimesterTerm(term) {
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

function rlResolveGrantAmount(record) {
    const rawAmount = rlParseGrantProvision(record?.grant_provision_label || record?.grant_provision).amount;

    if (!rawAmount) {
        return null;
    }

    const numericAmount = Number(rawAmount.toString().replace(/,/g, ''));

    if (!Number.isFinite(numericAmount)) {
        return null;
    }

    return rlIsTrimesterTerm(record?.term)
        ? (numericAmount * 2) / 3
        : numericAmount;
}

function rlCurrentAyGrantMultiplier(term) {
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

    return 1;
}

function rlEstimatedCurrentAyGrant(record) {
    const grantAmount = rlResolveGrantAmount(record);

    if (!Number.isFinite(grantAmount)) {
        return 0;
    }

    return grantAmount * rlCurrentAyGrantMultiplier(record?.term);
}

function rlCalendarYearLabel(allocation) {
    const candidates = [
        allocation?.calendar_year,
        allocation?.fiscal_year,
        allocation?.date_start,
        allocation?.date_end,
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
}

function rlResolveGrantByProgram(record) {
    const program = String(
        record?.program?.shortname || record?.program?.name || record?.program || '',
    ).toUpperCase();

    return program.includes('MED') ? 70000 : 10000;
}

function rlPerScholarGrantLabel(records) {
    if (!records.length) {
        return '';
    }

    const hasMed = records.some((record) => rlResolveGrantByProgram(record) === 70000);
    const hasOthers = records.some((record) => rlResolveGrantByProgram(record) === 10000);

    if (hasMed && hasOthers) {
        return `MED: ${rlFmtCurrency(70000)} | OTHERS: ${rlFmtCurrency(10000)}`;
    }

    return hasMed ? rlFmtCurrency(70000) : rlFmtCurrency(10000);
}

function rlScholarMatchesBudgetProgram(scholar, allocation, budgetProgram) {
    const programId = allocation?.program_id === null || allocation?.program_id === undefined || allocation?.program_id === ''
        ? ''
        : String(allocation.program_id);
    const programLabel = String(budgetProgram || '').trim().toLowerCase();

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

async function rlFetchImageDataUrl(url) {
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

async function rlLoadReportLogos(workbook) {
    try {
        const [pgp, yakap] = await Promise.all([
            rlFetchImageDataUrl('/images/pgp-logo.png'),
            rlFetchImageDataUrl('/images/yakap-logo.png'),
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

function rlSaveWorkbookBuffer(buffer, filename) {
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

export async function exportRecommendationListExcel({ recommendationList = null } = {}) {
    const records = recommendationList?.records || [];
    const groupBy = recommendationList?.group_by || 'none';
    const showRemarks = Boolean(recommendationList?.show_remarks);
    const highlightJpm = Boolean(recommendationList?.highlight_jpm_members);
    const budgetAllocation = recommendationList?.budget_allocation || null;
    const budgetProgram = recommendationList?.budget_program?.trim() || budgetAllocation?.program || 'N/A';
    const listNumber = recommendationList?.list_number || '';
    const reportTitle = recommendationList?.report_title || 'Request for Scholarship Approval';
    const today = recommendationList?.request_date
        ? moment(recommendationList.request_date).format('MMMM D, YYYY')
        : recommendationList?.created_at
            ? moment(recommendationList.created_at).format('MMMM D, YYYY')
            : moment().format('MMMM D, YYYY');

    // Uniformity — a column is hidden (and hoisted into the header text
    // instead) whenever every record shares the same value, exactly like
    // RecommendationListTemplate.vue's showSchoolColumn/showProgramColumn/
    // showAcademicYearColumn.
    const schoolUniform = records.length > 0 && rlUniqueCount(records, rlSchoolKey) === 1;
    const programUniform = records.length > 0 && rlUniqueCount(records, rlProgramKey) === 1;
    const ayTermUniform = records.length > 0 && rlUniqueCount(records, rlAyTermKey) === 1;
    const showSchoolColumn = !schoolUniform;
    const showProgramColumn = !programUniform;
    const showAcademicYearColumn = !ayTermUniform;

    const firstRecord = records[0] || null;
    const uniformSchoolLabel = firstRecord?.school?.name || firstRecord?.school?.shortname || '';
    const uniformProgramLabel = firstRecord?.program?.name || firstRecord?.program?.shortname || '';
    const uniformAcademicYear = firstRecord?.academic_year || '';
    const uniformTerm = firstRecord?.term || '';

    // Column layout mirrors the PDF colgroup exactly — no Course, no
    // Interview Date/By, no Endorsed By: none of those exist in the PDF.
    const columns = [
        { key: 'num', header: '#', width: 4, align: 'center', rowspan2: true },
        { key: 'name', header: 'Name', width: 26, align: 'left', rowspan2: true },
        { key: 'municipality', header: 'Municipality', width: 14, align: 'left', rowspan2: true },
        { key: 'year', header: 'Year Level', width: 8, align: 'center', rowspan2: true },
    ];
    if (showSchoolColumn) {
        columns.push({ key: 'school', header: 'School', width: 20, align: 'left', rowspan2: true });
    }
    if (showProgramColumn) {
        columns.push({ key: 'program', header: 'Program', width: 10, align: 'center', rowspan2: true });
    }
    if (showAcademicYearColumn) {
        columns.push({ key: 'ay', header: 'Academic Year', width: 14, align: 'center', rowspan2: true });
    }
    columns.push(
        { key: 'projTerms', group: 'Projected', header: 'Terms', width: 8, align: 'center' },
        { key: 'projGrant', group: 'Projected', header: 'Grant', width: 14, align: 'right' },
        { key: 'projCompletion', group: 'Projected', header: 'Completion', width: 11, align: 'center' },
        { key: 'remarks', header: 'Remarks', width: 22, align: 'left', rowspan2: true },
    );

    const totalColumns = columns.length;

    const valueGetters = {
        num: (record, index) => index + 1,
        name: (record) => rlFormatApplicantName(record),
        municipality: (record) => String(record?.profile?.municipality || '').toUpperCase(),
        year: (record) => record?.year_level || '',
        school: (record) => record?.school?.name || record?.school?.shortname || '',
        program: (record) => record?.program?.shortname || '',
        // PDF stacks Term above Academic Year in one cell — mirror with a line break.
        ay: (record) => [record?.term || '', record?.academic_year || ''].filter(Boolean).join('\n'),
        projTerms: (record) => {
            const terms = Number(record?.projected_term_count);
            return Number.isFinite(terms) ? terms : '';
        },
        projGrant: (record) => record?.projected_total_expense !== null && record?.projected_total_expense !== undefined
            ? rlFmtCurrency(record.projected_total_expense)
            : '',
        projCompletion: (record) => record?.projected_completion_year ?? '',
        remarks: (record) => (showRemarks ? String(record?.interview_remarks || record?.remarks || '') : ''),
    };

    const workbook = new ExcelJS.Workbook();
    const logos = await rlLoadReportLogos(workbook);
    const worksheet = workbook.addWorksheet('Recommendation List');
    worksheet.columns = columns.map((col) => ({ width: col.width }));

    // ── Letterhead ────────────────────────────────────────────────────
    const letterheadLines = [
        { text: 'Republic of the Philippines', size: 11, bold: true },
        { text: 'Provincial Government of Palawan', size: 11, bold: true },
        { text: 'Yakap Sa Edukasyon', size: 10, bold: false },
        { text: 'Scholarship Program', size: 10, bold: false },
        { text: 'Puerto Princesa City, Palawan', size: 10, bold: false },
    ];

    letterheadLines.forEach((line, index) => {
        const rowIndex = index + 1;
        worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
        const cell = worksheet.getCell(rowIndex, 1);
        cell.value = line.text;
        cell.font = { name: RL_FONT, size: line.size, bold: line.bold };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        worksheet.getRow(rowIndex).height = 15;
    });

    if (logos) {
        const inset = Math.max(totalColumns * 0.25, 1);
        worksheet.addImage(logos.pgpId, {
            tl: { col: inset, row: 0.1 },
            ext: { width: 76, height: 76 },
            editAs: 'oneCell',
        });
        worksheet.addImage(logos.yakapId, {
            tl: { col: Math.max(totalColumns - inset - 1, 0.1), row: 0.1 },
            ext: { width: 76, height: 76 },
            editAs: 'oneCell',
        });
    }

    // ── Report title + date ───────────────────────────────────────────
    const titleRowIndex = letterheadLines.length + 2;
    worksheet.mergeCells(titleRowIndex, 1, titleRowIndex, totalColumns);
    const titleCell = worksheet.getCell(titleRowIndex, 1);
    titleCell.value = reportTitle.toUpperCase();
    titleCell.font = { name: RL_FONT, size: 13, bold: true };
    titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
    worksheet.getRow(titleRowIndex).height = 20;

    let rowIndex = titleRowIndex + 1;

    // Uniform school name — hoisted here instead of its own column.
    if (schoolUniform) {
        worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
        const schoolCell = worksheet.getCell(rowIndex, 1);
        schoolCell.value = uniformSchoolLabel.toUpperCase();
        schoolCell.font = { name: RL_FONT, size: 11, bold: true };
        schoolCell.alignment = { horizontal: 'center', vertical: 'middle' };
        rowIndex += 1;
    }

    // Uniform term/academic year — hoisted here instead of its own column.
    if (ayTermUniform) {
        worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
        const ayCell = worksheet.getCell(rowIndex, 1);
        ayCell.value = `For Academic Year ${uniformAcademicYear} ${uniformTerm}`.trim();
        ayCell.font = { name: RL_FONT, size: 9 };
        ayCell.alignment = { horizontal: 'center', vertical: 'middle' };
        rowIndex += 1;
    }

    worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
    const dateCell = worksheet.getCell(rowIndex, 1);
    dateCell.value = today;
    dateCell.font = { name: RL_FONT, size: 9 };
    dateCell.alignment = { horizontal: 'center', vertical: 'middle' };
    rowIndex += 2;

    // Program + Grant (only shown when program is uniform, matching the PDF)
    // and Request No. (shown once the list has more than 2 records) — same row.
    const perScholarGrantLabel = records.length > 0 ? rlPerScholarGrantLabel(records) : '';
    if (programUniform || (listNumber && records.length > 2)) {
        const programCell = worksheet.getCell(rowIndex, 1);
        programCell.value = `Program: ${uniformProgramLabel}${perScholarGrantLabel ? ` | Grant: ${perScholarGrantLabel}` : ''}`;
        programCell.font = { name: RL_FONT, size: 9, bold: true };

        if (listNumber && records.length > 2) {
            worksheet.mergeCells(rowIndex, totalColumns - 2, rowIndex, totalColumns);
            const requestCell = worksheet.getCell(rowIndex, totalColumns - 2);
            requestCell.value = `Request No. ${listNumber}`;
            requestCell.font = { name: RL_FONT, size: 9, bold: true };
            requestCell.alignment = { horizontal: 'right' };
        }
        rowIndex += 1;
    }

    // ── Two-row table header writer ───────────────────────────────────
    const writeTableHeader = (startRow) => {
        const headerRow1 = worksheet.getRow(startRow);
        const headerRow2 = worksheet.getRow(startRow + 1);

        for (let c = 1; c <= totalColumns; c++) {
            [headerRow1.getCell(c), headerRow2.getCell(c)].forEach((cell) => {
                cell.font = { name: RL_FONT, size: 7, bold: true };
                cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                cell.fill = RL_HEADER_FILL;
                cell.border = RL_ALL_BORDERS;
            });
        }

        let colIndex = 1;
        let groupStart = null;
        let groupLabel = '';

        const flushGroup = (endCol) => {
            if (groupStart !== null) {
                worksheet.mergeCells(startRow, groupStart, startRow, endCol);
                headerRow1.getCell(groupStart).value = groupLabel;
                groupStart = null;
            }
        };

        columns.forEach((col) => {
            if (col.group) {
                if (groupStart === null || groupLabel !== col.group) {
                    flushGroup(colIndex - 1);
                    groupStart = colIndex;
                    groupLabel = col.group;
                }
                headerRow2.getCell(colIndex).value = col.header;
            } else {
                flushGroup(colIndex - 1);
                worksheet.mergeCells(startRow, colIndex, startRow + 1, colIndex);
                headerRow1.getCell(colIndex).value = col.header;
            }
            colIndex += 1;
        });
        flushGroup(colIndex - 1);

        headerRow1.height = 14;
        headerRow2.height = 14;
    };

    // ── Data row writer ───────────────────────────────────────────────
    const writeDataRow = (targetRow, record, index) => {
        const row = worksheet.getRow(targetRow);
        const jpmHighlight = highlightJpm && rlHasJpmMember(record);

        columns.forEach((col, colIdx) => {
            const cell = row.getCell(colIdx + 1);
            cell.value = valueGetters[col.key](record, index);
            const isName = col.key === 'name';
            cell.font = {
                name: RL_FONT,
                size: 8,
                bold: (isName && !jpmHighlight) || jpmHighlight,
                color: jpmHighlight ? { argb: 'FF166534' } : undefined,
            };
            cell.alignment = {
                horizontal: col.align,
                vertical: 'middle',
                wrapText: true,
            };
            cell.border = RL_ALL_BORDERS;
        });
    };

    // ── Body: grouped (PDF default for recommendation lists) or flat ──
    const writeGroupHeader = (targetRow, groupName, groupRecords) => {
        const nameSpan = Math.max(1, Math.min(4, totalColumns - 1));
        worksheet.mergeCells(targetRow, 1, targetRow, nameSpan);
        const nameCell = worksheet.getCell(targetRow, 1);
        nameCell.value = groupName;
        nameCell.font = { name: RL_FONT, size: 10, bold: true };
        nameCell.alignment = { horizontal: 'left', vertical: 'middle' };

        worksheet.mergeCells(targetRow, nameSpan + 1, targetRow, totalColumns);
        const metaCell = worksheet.getCell(targetRow, nameSpan + 1);
        const projectedTotal = groupRecords.reduce((sum, record) => sum + Number(record?.projected_total_expense || 0), 0);
        metaCell.value = `${groupRecords.length} record${groupRecords.length !== 1 ? 's' : ''} | ${rlFmtCurrency(projectedTotal)} projected grant`;
        metaCell.font = { name: RL_FONT, size: 8, color: { argb: 'FF555555' } };
        metaCell.alignment = { horizontal: 'right', vertical: 'middle' };

        for (let c = 1; c <= totalColumns; c++) {
            worksheet.getCell(targetRow, c).border = { bottom: RL_THIN };
        }
        worksheet.getRow(targetRow).height = 16;
    };

    if (groupBy !== 'none') {
        const groupKeyFor = (record) => {
            if (groupBy === 'program') return record.program?.shortname || 'N/A';
            if (groupBy === 'school') return record.school?.name || record.school?.shortname || 'N/A';
            if (groupBy === 'course') return record.course?.name || record.course?.shortname || 'N/A';
            if (groupBy === 'recommendation') {
                return {
                    recommended: 'Recommended for Approval',
                    further_evaluation: 'For Further Evaluation',
                    not_recommended: 'Not Recommended',
                }[record.recommendation] || record.recommendation || '—';
            }
            if (groupBy === 'interviewer') return record.interviewer?.name || 'N/A';
            return 'All';
        };

        const groups = {};
        for (const record of records) {
            const key = groupKeyFor(record);
            if (!groups[key]) groups[key] = [];
            groups[key].push(record);
        }

        const sortedGroups = Object.entries(groups).sort(([a], [b]) => a.localeCompare(b));

        sortedGroups.forEach(([groupName, groupRecords], groupIndex) => {
            const sorted = rlSortRecords(groupRecords);
            if (groupIndex > 0) {
                rowIndex += 1; // spacing between groups (PDF margin-bottom:14pt)
            }
            writeGroupHeader(rowIndex, groupName, sorted);
            rowIndex += 1;
            writeTableHeader(rowIndex);
            rowIndex += 2;
            sorted.forEach((record, index) => {
                writeDataRow(rowIndex, record, index);
                rowIndex += 1;
            });
        });
    } else {
        writeTableHeader(rowIndex);
        rowIndex += 2;
        normalizeRecords(records).forEach((record, index) => {
            writeDataRow(rowIndex, record, index);
            rowIndex += 1;
        });
    }

    // ── Budget allocation block ───────────────────────────────────────
    if (budgetAllocation) {
        const calendarYear = rlCalendarYearLabel(budgetAllocation);
        const heading = calendarYear
            ? `Budget Allocation for Calendar Year ${calendarYear}`
            : 'Budget Allocation for Current Calendar Year';
        const totalScholars = records.length;
        const approvedScholars = Array.isArray(budgetAllocation.approved_scholars)
            ? budgetAllocation.approved_scholars.filter((scholar) => rlScholarMatchesBudgetProgram(scholar, budgetAllocation, recommendationList?.budget_program))
            : [];
        const approvedScholarsToDate = approvedScholars.length
            ? approvedScholars.length
            : Number(budgetAllocation.approved_scholars_to_date ?? 0) || 0;
        const approvedScholarsCurrentAyTotal = Number(budgetAllocation.approved_scholars_current_ay_estimated_total ?? 0) || 0;
        const runningBalance = Number(budgetAllocation.total_allotment ?? 0)
            - Number(budgetAllocation.disbursed ?? 0)
            - approvedScholarsCurrentAyTotal;
        const totalCurrentAyGrant = records.reduce((sum, record) => sum + rlEstimatedCurrentAyGrant(record), 0);
        const projectedBalance = runningBalance - totalCurrentAyGrant;
        const scopeSuffix = calendarYear ? ` (CY ${calendarYear})` : '';

        rowIndex += 2;
        const headingNameSpan = Math.min(9, totalColumns - 3);
        worksheet.mergeCells(rowIndex, 1, rowIndex, headingNameSpan);
        const headingCell = worksheet.getCell(rowIndex, 1);
        headingCell.value = heading.toUpperCase();
        headingCell.font = { name: RL_FONT, size: 9, bold: true };
        headingCell.alignment = { horizontal: 'left', vertical: 'middle' };
        if (listNumber) {
            worksheet.mergeCells(rowIndex, totalColumns - 2, rowIndex, totalColumns);
            const requestCell = worksheet.getCell(rowIndex, totalColumns - 2);
            requestCell.value = `Request No. ${listNumber}`;
            requestCell.font = { name: RL_FONT, size: 9, bold: true };
            requestCell.alignment = { horizontal: 'right' };
        }
        rowIndex += 1;

        // 4-slot grid mapped onto the sheet, scaled to however many columns
        // this list actually has (as few as 8 once School/Program/Academic
        // Year are hoisted into the header instead of shown as columns).
        const label1End = Math.min(2, totalColumns - 3);
        const value1End = Math.max(label1End + 1, totalColumns - 4);
        const label2End = Math.max(value1End + 1, totalColumns - 2);

        const writeBudgetRow = ({ label1, value1, label2 = null, value2 = null, value1Bold = false, value1Color = null, spanValue = false }) => {
            worksheet.mergeCells(rowIndex, 1, rowIndex, label1End);
            const l1 = worksheet.getCell(rowIndex, 1);
            l1.value = label1;
            l1.font = { name: RL_FONT, size: 8, bold: true };
            l1.fill = RL_LABEL_FILL;
            l1.alignment = { vertical: 'middle', wrapText: true };

            const v1End = spanValue ? totalColumns : value1End;
            worksheet.mergeCells(rowIndex, label1End + 1, rowIndex, v1End);
            const v1 = worksheet.getCell(rowIndex, label1End + 1);
            v1.value = value1;
            v1.font = { name: RL_FONT, size: 8, bold: value1Bold, color: value1Color ? { argb: value1Color } : undefined };
            v1.alignment = { vertical: 'middle', wrapText: true };

            let lastCol = v1End;
            if (!spanValue && label2 !== null) {
                worksheet.mergeCells(rowIndex, value1End + 1, rowIndex, label2End);
                const l2 = worksheet.getCell(rowIndex, value1End + 1);
                l2.value = label2;
                l2.font = { name: RL_FONT, size: 8, bold: true };
                l2.fill = RL_LABEL_FILL;
                l2.alignment = { vertical: 'middle', wrapText: true, indent: 1 };

                worksheet.mergeCells(rowIndex, label2End + 1, rowIndex, totalColumns);
                const v2 = worksheet.getCell(rowIndex, label2End + 1);
                v2.value = value2 ?? '';
                v2.font = { name: RL_FONT, size: 8 };
                v2.alignment = { vertical: 'middle' };
                lastCol = totalColumns;
            }

            for (let c = 1; c <= lastCol; c++) {
                worksheet.getCell(rowIndex, c).border = RL_ALL_BORDERS;
            }
            worksheet.getRow(rowIndex).height = 15;
            rowIndex += 1;
        };

        writeBudgetRow({
            label1: 'Program',
            value1: `${budgetProgram} · ${budgetAllocation.rc_name || budgetAllocation.rc_code || 'N/A'} · ${budgetAllocation.fiscal_year ? `CY ${budgetAllocation.fiscal_year}` : 'CY N/A'}`,
            spanValue: true,
        });
        writeBudgetRow({
            label1: 'Allocated Fund',
            value1: rlFmtCurrency(budgetAllocation.total_allotment),
            label2: 'No. of Scholars:',
            value2: '',
        });
        writeBudgetRow({
            label1: 'Running Balance',
            value1: rlFmtCurrency(runningBalance),
            label2: 'Current no. for this request',
            value2: totalScholars,
        });
        writeBudgetRow({
            label1: 'Total amount for this request',
            value1: rlFmtCurrency(totalCurrentAyGrant),
            value1Bold: true,
            label2: `Cumulative Approved No.${scopeSuffix}`,
            value2: approvedScholarsToDate,
        });
        writeBudgetRow({
            label1: 'Remaining balance after approval',
            value1: rlFmtCurrency(projectedBalance),
            value1Bold: true,
            value1Color: projectedBalance < 0 ? 'FFB91C1C' : 'FF166534',
            spanValue: true,
        });

        worksheet.mergeCells(rowIndex, label1End + 1, rowIndex, totalColumns);
        const approvalMarkCell = worksheet.getCell(rowIndex, label1End + 1);
        approvalMarkCell.value = '___APPROVED          ___DISAPPROVED';
        approvalMarkCell.font = { name: RL_FONT, size: 7, color: { argb: 'FF555555' } };
        approvalMarkCell.alignment = { horizontal: 'right' };
        rowIndex += 1;
    }

    // ── Save ──────────────────────────────────────────────────────────
    const buffer = await workbook.xlsx.writeBuffer();
    const listNumberSlug = String(listNumber).replace(/[^\w-]+/g, '_');
    const filename = listNumberSlug
        ? `recommendation_list_${listNumberSlug}.xlsx`
        : `recommendation_list_${moment().format('YYYY-MM-DD_HH-mm-ss')}.xlsx`;

    rlSaveWorkbookBuffer(buffer, filename);
}

// ── Interviewed Applicants Excel export ─────────────────────────────
// Mirrors InterviewedApplicantsTemplate.vue as rendered by generateReport()
// in GenerateReportModalIOS.vue: same columns (respecting the same toggles),
// same grouping, same JPM highlight/remarks, same budget-allocation +
// signatories block. Reuses the ExcelJS helpers (rl*) built for the
// Approval Request export above — they aren't report-specific.
const IA_REC_LABELS = {
    recommended: 'Recommended for Approval',
    further_evaluation: 'For Further Evaluation',
    not_recommended: 'Not Recommended',
};

function iaRecLabel(value) {
    return IA_REC_LABELS[value] || value || '—';
}

export async function exportInterviewedApplicantsExcel({
    records = [],
    title = 'INTERVIEWED APPLICANTS REPORT',
    reportType = 'list',
    groupBy = 'none',
    today = moment().format('MMMM D, YYYY'),
    includeInterviewColumns = true,
    showInterviewerColumn = true,
    includeEndorsedBy = false,
    includeProjectedColumns = true,
    highlightJpmMembers = false,
    showRemarks = false,
    budgetAllocation = null,
    budgetProgram = '',
    listNumber = '',
    preparedBy = DEFAULT_PREPARED_BY,
    preparedByPosition = DEFAULT_PREPARED_BY_POSITION,
    preparedByOffice = DEFAULT_PREPARED_BY_OFFICE,
    approvedBy = DEFAULT_APPROVED_BY,
    approvedByPosition = DEFAULT_APPROVED_BY_POSITION,
} = {}) {
    const normalizedRecords = normalizeRecords(records);
    const resolvedBudgetProgram = budgetProgram?.trim() || budgetAllocation?.program || 'N/A';

    // Column layout mirrors the PDF colgroup/toggles.
    const columns = [
        { key: 'num', header: '#', width: 4, align: 'center', rowspan2: true },
        { key: 'name', header: 'Name', width: 26, align: 'left', rowspan2: true },
        { key: 'municipality', header: 'Municipality', width: 14, align: 'left', rowspan2: true },
        { key: 'program', header: 'Program', width: 8, align: 'center', rowspan2: true },
        { key: 'school', header: 'School', width: 20, align: 'left', rowspan2: true },
        { key: 'course', header: 'Course', width: 20, align: 'left', rowspan2: true },
        { key: 'year', header: 'Year', width: 6, align: 'center', rowspan2: true },
        { key: 'term', header: 'Agreement Start', width: 12, align: 'center', rowspan2: true },
        { key: 'ay', header: 'Academic Year', width: 12, align: 'center', rowspan2: true },
    ];
    if (includeProjectedColumns) {
        columns.push(
            { key: 'projTerms', group: 'Projected', header: 'Terms', width: 8, align: 'center' },
            { key: 'projGrant', group: 'Projected', header: 'Grant', width: 14, align: 'right' },
            { key: 'projCompletion', group: 'Projected', header: 'Completion', width: 11, align: 'center' },
        );
    }
    if (includeInterviewColumns) {
        columns.push({ key: 'intDate', group: 'Interview', header: 'Date', width: 12, align: 'center' });
        if (showInterviewerColumn) {
            columns.push({ key: 'intBy', group: 'Interview', header: 'By', width: 16, align: 'center' });
        }
    }
    if (includeEndorsedBy) {
        columns.push({ key: 'endorsed', header: 'Endorsed By', width: 14, align: 'center', rowspan2: true });
    }
    columns.push({ key: 'remarks', header: 'Remarks', width: 22, align: 'left', rowspan2: true });

    const totalColumns = columns.length;

    const valueGetters = {
        num: (record, index) => index + 1,
        name: (record) => rlFormatApplicantName(record),
        municipality: (record) => String(record?.profile?.municipality || '').toUpperCase(),
        program: (record) => record?.program?.shortname || '',
        school: (record) => record?.school?.name || record?.school?.shortname || '',
        course: (record) => record?.course?.name || record?.course?.shortname || '',
        year: (record) => record?.year_level || '',
        term: (record) => record?.term || '',
        ay: (record) => record?.academic_year || '',
        projTerms: (record) => {
            const terms = Number(record?.projected_term_count);
            return Number.isFinite(terms) ? terms : '';
        },
        projGrant: (record) => record?.projected_total_expense !== null && record?.projected_total_expense !== undefined
            ? rlFmtCurrency(record.projected_total_expense)
            : '',
        projCompletion: (record) => record?.projected_completion_year ?? '',
        intDate: (record) => record?.interviewed_at ? moment(record.interviewed_at).format('MMM DD, YYYY') : '',
        intBy: (record) => String(record?.interviewer?.name || '').toUpperCase(),
        endorsed: (record) => record?.endorsed_by || '',
        remarks: (record) => (showRemarks ? String(record?.interview_remarks || record?.remarks || '') : ''),
    };

    const workbook = new ExcelJS.Workbook();
    const logos = await rlLoadReportLogos(workbook);
    const worksheet = workbook.addWorksheet('Interviewed Applicants');
    worksheet.columns = columns.map((col) => ({ width: col.width }));

    // ── Letterhead ────────────────────────────────────────────────────
    const letterheadLines = [
        { text: 'Republic of the Philippines', size: 11, bold: true },
        { text: 'Provincial Government of Palawan', size: 11, bold: true },
        { text: 'Yakap Sa Edukasyon', size: 10, bold: false },
        { text: 'Scholarship Program', size: 10, bold: false },
        { text: 'Puerto Princesa City, Palawan', size: 10, bold: false },
    ];

    letterheadLines.forEach((line, index) => {
        const rowIndex = index + 1;
        worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
        const cell = worksheet.getCell(rowIndex, 1);
        cell.value = line.text;
        cell.font = { name: RL_FONT, size: line.size, bold: line.bold };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        worksheet.getRow(rowIndex).height = 15;
    });
    for (let c = 1; c <= totalColumns; c++) {
        worksheet.getCell(letterheadLines.length, c).border = { bottom: RL_MEDIUM };
    }

    if (logos) {
        const inset = Math.max(totalColumns * 0.25, 1);
        worksheet.addImage(logos.pgpId, {
            tl: { col: inset, row: 0.1 },
            ext: { width: 76, height: 76 },
            editAs: 'oneCell',
        });
        worksheet.addImage(logos.yakapId, {
            tl: { col: Math.max(totalColumns - inset - 1, 0.1), row: 0.1 },
            ext: { width: 76, height: 76 },
            editAs: 'oneCell',
        });
    }

    // ── Report title + date ───────────────────────────────────────────
    const titleRowIndex = letterheadLines.length + 2;
    worksheet.mergeCells(titleRowIndex, 1, titleRowIndex, totalColumns);
    const titleCell = worksheet.getCell(titleRowIndex, 1);
    titleCell.value = title;
    titleCell.font = { name: RL_FONT, size: 13, bold: true };
    titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
    worksheet.getRow(titleRowIndex).height = 20;

    worksheet.mergeCells(titleRowIndex + 1, 1, titleRowIndex + 1, totalColumns);
    const dateCell = worksheet.getCell(titleRowIndex + 1, 1);
    dateCell.value = today;
    dateCell.font = { name: RL_FONT, size: 9 };
    dateCell.alignment = { horizontal: 'center', vertical: 'middle' };

    let rowIndex = titleRowIndex + 3;

    if (normalizedRecords.length === 0) {
        worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
        const emptyCell = worksheet.getCell(rowIndex, 1);
        emptyCell.value = 'No interviewed applicants match the current selection.';
        emptyCell.font = { name: RL_FONT, size: 10, italic: true, color: { argb: 'FF888888' } };
        emptyCell.alignment = { horizontal: 'center', vertical: 'middle' };
        rowIndex += 2;
    } else if (reportType === 'summary') {
        // ── Summary: By Recommendation, then By Program (stacked) ──────
        const writeSummaryTable = (heading, rows) => {
            worksheet.mergeCells(rowIndex, 1, rowIndex, totalColumns);
            const headingCell = worksheet.getCell(rowIndex, 1);
            headingCell.value = heading;
            headingCell.font = { name: RL_FONT, size: 9, bold: true };
            headingCell.fill = RL_HEADER_FILL;
            rowIndex += 1;

            const headerRow = worksheet.getRow(rowIndex);
            headerRow.getCell(1).value = heading === 'By Recommendation' ? 'Recommendation' : 'Program';
            headerRow.getCell(2).value = 'Interviewed';
            headerRow.getCell(3).value = 'Projected Grant';
            for (let c = 1; c <= 3; c++) {
                const cell = headerRow.getCell(c);
                cell.font = { name: RL_FONT, size: 8, bold: true };
                cell.fill = RL_HEADER_FILL;
                cell.border = RL_ALL_BORDERS;
                cell.alignment = { horizontal: c === 1 ? 'left' : 'right' };
            }
            rowIndex += 1;

            rows.forEach((row) => {
                const dataRow = worksheet.getRow(rowIndex);
                dataRow.getCell(1).value = row.label;
                dataRow.getCell(2).value = row.interviewed;
                dataRow.getCell(3).value = rlFmtCurrency(row.projected);
                for (let c = 1; c <= 3; c++) {
                    const cell = dataRow.getCell(c);
                    cell.font = { name: RL_FONT, size: 8, bold: c > 1 };
                    cell.border = RL_ALL_BORDERS;
                    cell.alignment = { horizontal: c === 1 ? 'left' : 'right' };
                }
                rowIndex += 1;
            });
            rowIndex += 1;
        };

        const recOrder = ['recommended', 'further_evaluation', 'not_recommended'];
        const recGrouped = {};
        const progGrouped = {};
        for (const record of normalizedRecords) {
            const recKey = record.recommendation || 'unknown';
            if (!recGrouped[recKey]) recGrouped[recKey] = { key: recKey, label: iaRecLabel(recKey), interviewed: 0, projected: 0 };
            recGrouped[recKey].interviewed += 1;
            recGrouped[recKey].projected += Number(record.projected_total_expense || 0);

            const progKey = record.program?.shortname || 'N/A';
            if (!progGrouped[progKey]) progGrouped[progKey] = { key: progKey, label: progKey, interviewed: 0, projected: 0 };
            progGrouped[progKey].interviewed += 1;
            progGrouped[progKey].projected += Number(record.projected_total_expense || 0);
        }

        const recRows = Object.values(recGrouped).sort((a, b) => {
            const ai = recOrder.indexOf(a.key);
            const bi = recOrder.indexOf(b.key);
            if (ai === -1 && bi === -1) return a.label.localeCompare(b.label);
            if (ai === -1) return 1;
            if (bi === -1) return -1;
            return ai - bi;
        });
        const progRows = Object.values(progGrouped).sort((a, b) => a.label.localeCompare(b.label));

        writeSummaryTable('By Recommendation', recRows);
        writeSummaryTable('By Program', progRows);
    } else {
        // ── Detailed list: grouped or flat ──────────────────────────────
        const writeTableHeader = (startRow) => {
            const headerRow1 = worksheet.getRow(startRow);
            const headerRow2 = worksheet.getRow(startRow + 1);

            for (let c = 1; c <= totalColumns; c++) {
                [headerRow1.getCell(c), headerRow2.getCell(c)].forEach((cell) => {
                    cell.font = { name: RL_FONT, size: 7, bold: true };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.fill = RL_HEADER_FILL;
                    cell.border = RL_ALL_BORDERS;
                });
            }

            let colIndex = 1;
            let groupStart = null;
            let groupLabel = '';

            const flushGroup = (endCol) => {
                if (groupStart !== null) {
                    worksheet.mergeCells(startRow, groupStart, startRow, endCol);
                    headerRow1.getCell(groupStart).value = groupLabel;
                    groupStart = null;
                }
            };

            columns.forEach((col) => {
                if (col.group) {
                    if (groupStart === null || groupLabel !== col.group) {
                        flushGroup(colIndex - 1);
                        groupStart = colIndex;
                        groupLabel = col.group;
                    }
                    headerRow2.getCell(colIndex).value = col.header;
                } else {
                    flushGroup(colIndex - 1);
                    worksheet.mergeCells(startRow, colIndex, startRow + 1, colIndex);
                    headerRow1.getCell(colIndex).value = col.header;
                }
                colIndex += 1;
            });
            flushGroup(colIndex - 1);

            headerRow1.height = 14;
            headerRow2.height = 14;
        };

        const writeDataRow = (targetRow, record, index) => {
            const row = worksheet.getRow(targetRow);
            const jpmHighlight = highlightJpmMembers && rlHasJpmMember(record);

            columns.forEach((col, colIdx) => {
                const cell = row.getCell(colIdx + 1);
                cell.value = valueGetters[col.key](record, index);
                const isName = col.key === 'name';
                cell.font = {
                    name: RL_FONT,
                    size: 8,
                    bold: (isName && !jpmHighlight) || jpmHighlight,
                    color: jpmHighlight ? { argb: 'FF166534' } : undefined,
                };
                cell.alignment = { horizontal: col.align, vertical: 'middle', wrapText: true };
                cell.border = RL_ALL_BORDERS;
            });
        };

        const writeGroupHeader = (targetRow, groupName, groupRecords) => {
            const nameSpan = Math.min(8, totalColumns);
            worksheet.mergeCells(targetRow, 1, targetRow, nameSpan);
            const nameCell = worksheet.getCell(targetRow, 1);
            nameCell.value = groupName;
            nameCell.font = { name: RL_FONT, size: 10, bold: true };
            nameCell.alignment = { horizontal: 'left', vertical: 'middle' };

            worksheet.mergeCells(targetRow, nameSpan + 1, targetRow, totalColumns);
            const metaCell = worksheet.getCell(targetRow, nameSpan + 1);
            const projectedTotal = groupRecords.reduce((sum, record) => sum + Number(record?.projected_total_expense || 0), 0);
            metaCell.value = `${groupRecords.length} record${groupRecords.length !== 1 ? 's' : ''} | ${rlFmtCurrency(projectedTotal)} projected grant`;
            metaCell.font = { name: RL_FONT, size: 8, color: { argb: 'FF555555' } };
            metaCell.alignment = { horizontal: 'right', vertical: 'middle' };

            for (let c = 1; c <= totalColumns; c++) {
                worksheet.getCell(targetRow, c).border = { bottom: RL_THIN };
            }
            worksheet.getRow(targetRow).height = 16;
        };

        if (groupBy !== 'none') {
            const groupKeyFor = (record) => {
                if (groupBy === 'program') return record.program?.shortname || 'N/A';
                if (groupBy === 'school') return record.school?.name || record.school?.shortname || 'N/A';
                if (groupBy === 'course') return record.course?.name || record.course?.shortname || 'N/A';
                if (groupBy === 'recommendation') return iaRecLabel(record.recommendation);
                if (groupBy === 'interviewer') return record.interviewer?.name || 'N/A';
                return 'All';
            };

            const groups = {};
            for (const record of normalizedRecords) {
                const key = groupKeyFor(record);
                if (!groups[key]) groups[key] = [];
                groups[key].push(record);
            }

            const sortedGroups = Object.entries(groups).sort(([a], [b]) => a.localeCompare(b));

            sortedGroups.forEach(([groupName, groupRecords], groupIndex) => {
                const sorted = rlSortRecords(groupRecords);
                if (groupIndex > 0) {
                    rowIndex += 1;
                }
                writeGroupHeader(rowIndex, groupName, sorted);
                rowIndex += 1;
                writeTableHeader(rowIndex);
                rowIndex += 2;
                sorted.forEach((record, index) => {
                    writeDataRow(rowIndex, record, index);
                    rowIndex += 1;
                });
            });
        } else {
            writeTableHeader(rowIndex);
            rowIndex += 2;
            normalizedRecords.forEach((record, index) => {
                writeDataRow(rowIndex, record, index);
                rowIndex += 1;
            });
        }
    }

    // ── Budget allocation block ───────────────────────────────────────
    if (budgetAllocation) {
        const calendarYear = rlCalendarYearLabel(budgetAllocation);
        const heading = calendarYear
            ? `Budget Allocation for Calendar Year ${calendarYear}`
            : 'Budget Allocation for Current Calendar Year';
        const totalScholars = normalizedRecords.length;
        const approvedScholars = Array.isArray(budgetAllocation.approved_scholars)
            ? budgetAllocation.approved_scholars.filter((scholar) => rlScholarMatchesBudgetProgram(scholar, budgetAllocation, resolvedBudgetProgram))
            : [];
        const approvedScholarsToDate = approvedScholars.length
            ? approvedScholars.length
            : Number(budgetAllocation.approved_scholars_to_date ?? 0) || 0;
        const approvedScholarsCurrentAyTotal = Number(budgetAllocation.approved_scholars_current_ay_estimated_total ?? 0) || 0;
        const runningBalance = Number(budgetAllocation.total_allotment ?? 0)
            - Number(budgetAllocation.disbursed ?? 0)
            - approvedScholarsCurrentAyTotal;
        const totalCurrentAyGrant = normalizedRecords.reduce((sum, record) => sum + rlEstimatedCurrentAyGrant(record), 0);
        const projectedBalance = runningBalance - totalCurrentAyGrant;
        const scopeSuffix = calendarYear ? ` (CY ${calendarYear})` : '';

        rowIndex += 2;
        const headingNameSpan = Math.min(9, totalColumns - 3);
        worksheet.mergeCells(rowIndex, 1, rowIndex, headingNameSpan);
        const headingCell = worksheet.getCell(rowIndex, 1);
        headingCell.value = heading.toUpperCase();
        headingCell.font = { name: RL_FONT, size: 9, bold: true };
        headingCell.alignment = { horizontal: 'left', vertical: 'middle' };
        if (listNumber) {
            worksheet.mergeCells(rowIndex, totalColumns - 2, rowIndex, totalColumns);
            const requestCell = worksheet.getCell(rowIndex, totalColumns - 2);
            requestCell.value = `Request No. ${listNumber}`;
            requestCell.font = { name: RL_FONT, size: 9, bold: true };
            requestCell.alignment = { horizontal: 'right' };
        }
        rowIndex += 1;

        const label1End = 3;
        const value1End = 8;
        const label2End = Math.min(12, totalColumns - 1);

        const writeBudgetRow = ({ label1, value1, label2 = null, value2 = null, value1Bold = false, value1Color = null, spanValue = false }) => {
            worksheet.mergeCells(rowIndex, 1, rowIndex, label1End);
            const l1 = worksheet.getCell(rowIndex, 1);
            l1.value = label1;
            l1.font = { name: RL_FONT, size: 8, bold: true };
            l1.fill = RL_LABEL_FILL;
            l1.alignment = { vertical: 'middle', wrapText: true };

            const v1End = spanValue ? totalColumns : value1End;
            worksheet.mergeCells(rowIndex, label1End + 1, rowIndex, v1End);
            const v1 = worksheet.getCell(rowIndex, label1End + 1);
            v1.value = value1;
            v1.font = { name: RL_FONT, size: 8, bold: value1Bold, color: value1Color ? { argb: value1Color } : undefined };
            v1.alignment = { vertical: 'middle', wrapText: true };

            let lastCol = v1End;
            if (!spanValue && label2 !== null) {
                worksheet.mergeCells(rowIndex, value1End + 1, rowIndex, label2End);
                const l2 = worksheet.getCell(rowIndex, value1End + 1);
                l2.value = label2;
                l2.font = { name: RL_FONT, size: 8, bold: true };
                l2.fill = RL_LABEL_FILL;
                l2.alignment = { vertical: 'middle', wrapText: true, indent: 1 };

                worksheet.mergeCells(rowIndex, label2End + 1, rowIndex, totalColumns);
                const v2 = worksheet.getCell(rowIndex, label2End + 1);
                v2.value = value2 ?? '';
                v2.font = { name: RL_FONT, size: 8 };
                v2.alignment = { vertical: 'middle' };
                lastCol = totalColumns;
            }

            for (let c = 1; c <= lastCol; c++) {
                worksheet.getCell(rowIndex, c).border = RL_ALL_BORDERS;
            }
            worksheet.getRow(rowIndex).height = 15;
            rowIndex += 1;
        };

        writeBudgetRow({
            label1: 'Program',
            value1: `${resolvedBudgetProgram} · ${budgetAllocation.rc_name || budgetAllocation.rc_code || 'N/A'} · ${budgetAllocation.fiscal_year ? `CY ${budgetAllocation.fiscal_year}` : 'CY N/A'}`,
            spanValue: true,
        });
        writeBudgetRow({
            label1: 'Allocated Fund',
            value1: rlFmtCurrency(budgetAllocation.total_allotment),
            label2: 'No. of Scholars:',
            value2: '',
        });
        writeBudgetRow({
            label1: 'Running Balance',
            value1: rlFmtCurrency(runningBalance),
            label2: 'Current no. for this request',
            value2: totalScholars,
        });
        writeBudgetRow({
            label1: 'Total amount for this request',
            value1: rlFmtCurrency(totalCurrentAyGrant),
            value1Bold: true,
            label2: `Cumulative Approved No.${scopeSuffix}`,
            value2: approvedScholarsToDate,
        });
        writeBudgetRow({
            label1: 'Remaining balance after approval',
            value1: rlFmtCurrency(projectedBalance),
            value1Bold: true,
            value1Color: projectedBalance < 0 ? 'FFB91C1C' : 'FF166534',
            spanValue: true,
        });

        worksheet.mergeCells(rowIndex, label1End + 1, rowIndex, totalColumns);
        const approvalMarkCell = worksheet.getCell(rowIndex, label1End + 1);
        approvalMarkCell.value = '___APPROVED          ___DISAPPROVED';
        approvalMarkCell.font = { name: RL_FONT, size: 7, color: { argb: 'FF555555' } };
        approvalMarkCell.alignment = { horizontal: 'right' };
        rowIndex += 1;
    }

    // ── Signatories (only when the PDF would show them) ───────────────
    if (budgetAllocation || preparedBy?.trim()) {
        rowIndex += 2;
        const sigLeftCol = 2;
        const sigRightCol = Math.max(totalColumns - 5, sigLeftCol + 4);
        const sigSpan = 4;

        const sigLabelRow = worksheet.getRow(rowIndex);
        sigLabelRow.getCell(sigLeftCol).value = 'Prepared by:';
        sigLabelRow.getCell(sigLeftCol).font = { name: RL_FONT, size: 8, bold: true };
        sigLabelRow.getCell(sigRightCol).value = 'Approved by:';
        sigLabelRow.getCell(sigRightCol).font = { name: RL_FONT, size: 8, bold: true };
        rowIndex += 4;

        worksheet.mergeCells(rowIndex, sigLeftCol, rowIndex, sigLeftCol + sigSpan - 1);
        const prepNameCell = worksheet.getCell(rowIndex, sigLeftCol);
        prepNameCell.value = (preparedBy || DEFAULT_PREPARED_BY).toUpperCase();
        prepNameCell.font = { name: RL_FONT, size: 8, bold: true };
        prepNameCell.alignment = { horizontal: 'center' };

        worksheet.mergeCells(rowIndex, sigRightCol, rowIndex, sigRightCol + sigSpan - 1);
        const apprNameCell = worksheet.getCell(rowIndex, sigRightCol);
        apprNameCell.value = (approvedBy || DEFAULT_APPROVED_BY).toUpperCase();
        apprNameCell.font = { name: RL_FONT, size: 8, bold: true };
        apprNameCell.alignment = { horizontal: 'center' };

        for (let c = 0; c < sigSpan; c++) {
            worksheet.getCell(rowIndex, sigLeftCol + c).border = { top: RL_THIN };
            worksheet.getCell(rowIndex, sigRightCol + c).border = { top: RL_THIN };
        }
        rowIndex += 1;

        worksheet.mergeCells(rowIndex, sigLeftCol, rowIndex, sigLeftCol + sigSpan - 1);
        const prepPosCell = worksheet.getCell(rowIndex, sigLeftCol);
        prepPosCell.value = preparedByPosition || DEFAULT_PREPARED_BY_POSITION;
        prepPosCell.font = { name: RL_FONT, size: 8 };
        prepPosCell.alignment = { horizontal: 'center' };

        worksheet.mergeCells(rowIndex, sigRightCol, rowIndex, sigRightCol + sigSpan - 1);
        const apprPosCell = worksheet.getCell(rowIndex, sigRightCol);
        apprPosCell.value = approvedByPosition || DEFAULT_APPROVED_BY_POSITION;
        apprPosCell.font = { name: RL_FONT, size: 8 };
        apprPosCell.alignment = { horizontal: 'center' };
        rowIndex += 1;

        worksheet.mergeCells(rowIndex, sigLeftCol, rowIndex, sigLeftCol + sigSpan - 1);
        const prepOfficeCell = worksheet.getCell(rowIndex, sigLeftCol);
        prepOfficeCell.value = preparedByOffice || DEFAULT_PREPARED_BY_OFFICE;
        prepOfficeCell.font = { name: RL_FONT, size: 8 };
        prepOfficeCell.alignment = { horizontal: 'center' };
        rowIndex += 3;

        worksheet.mergeCells(rowIndex, sigRightCol, rowIndex, sigRightCol + sigSpan - 1);
        const dateLineCell = worksheet.getCell(rowIndex, sigRightCol);
        dateLineCell.value = 'Date';
        dateLineCell.font = { name: RL_FONT, size: 8 };
        dateLineCell.alignment = { horizontal: 'center' };
        for (let c = 0; c < sigSpan; c++) {
            worksheet.getCell(rowIndex, sigRightCol + c).border = { top: RL_THIN };
        }
    }

    // ── Save ──────────────────────────────────────────────────────────
    const buffer = await workbook.xlsx.writeBuffer();
    rlSaveWorkbookBuffer(buffer, `interviewed_applicants_${moment().format('YYYY-MM-DD_HH-mm-ss')}.xlsx`);
}

// Plain data-dump export for the "All" approval-request audit view — just a
// header row + data rows, no letterhead/title rows like the other exports.
export async function exportRecommendationListAuditExcel({ records = [] } = {}) {
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('All Records');

    worksheet.columns = [
        { header: 'Name', key: 'name', width: 30 },
        { header: 'Program', key: 'program', width: 14 },
        { header: 'Course', key: 'course', width: 24 },
        { header: 'School', key: 'school', width: 24 },
        { header: 'Current Status', key: 'status', width: 16 },
        { header: 'Date Approved', key: 'date_approved', width: 16 },
        { header: 'Approval Request(s)', key: 'lists', width: 26 },
        { header: 'Processed Outside Request', key: 'flag', width: 22 },
    ];

    worksheet.getRow(1).font = { name: RL_FONT, bold: true };

    records.forEach((record) => {
        worksheet.addRow({
            name: formatApplicantName(record),
            program: record?.program || '',
            course: record?.course || '',
            school: record?.school || '',
            status: record?.unified_status || '',
            date_approved: record?.date_approved || '',
            lists: (record?.lists || []).map((l) => l.list_number).join(', '),
            flag: record?.processed_outside_list ? 'Yes' : '',
        });
    });

    worksheet.eachRow((row) => {
        row.font = { ...row.font, name: RL_FONT };
    });

    const buffer = await workbook.xlsx.writeBuffer();
    rlSaveWorkbookBuffer(buffer, `approval_request_audit_${moment().format('YYYY-MM-DD_HH-mm-ss')}.xlsx`);
}
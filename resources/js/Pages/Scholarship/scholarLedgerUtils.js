const normalizeText = (value) => String(value ?? '').trim();

const upper = (value) => normalizeText(value).toUpperCase();

const getLedgerEntryYearLevel = (entry) => normalizeText(entry?.year_level ?? entry?.yearLevel);
const getLedgerEntryAcademicYear = (entry) => normalizeText(entry?.academic_year ?? entry?.academicYear);
const getLedgerEntrySemester = (entry) => normalizeText(entry?.semester ?? entry?.term);
const getLedgerEntryPaymentType = (entry) => normalizeText(entry?.disbursement_type ?? entry?.paymentType);

const getLedgerEntryRosMonths = (entry) => {
    if (Object.prototype.hasOwnProperty.call(entry ?? {}, 'ros_months')) {
        return entry?.ros_months;
    }

    return entry?.rosMonths;
};

const buildLedgerRosDedupeKey = (entry) => {
    return [
        getLedgerEntryYearLevel(entry),
        getLedgerEntrySemester(entry),
        getLedgerEntryAcademicYear(entry),
    ].join('|');
};

const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

export const isFourMonthLedgerTerm = (semester) => {
    if (typeof semester !== 'string') return false;

    const normalizedSemester = semester.toLowerCase();
    return normalizedSemester.includes('trimester')
        || normalizedSemester.includes('3rd semester')
        || normalizedSemester.includes('3rd sem');
};

export const normalizeLedgerPaymentType = (value) => {
    return normalizeText(value)
        .toLowerCase()
        .replace(/[\s-]+/g, '_');
};

export const isFinancialAssistanceLedgerPayment = (entry) => {
    return normalizeLedgerPaymentType(getLedgerEntryPaymentType(entry)) === 'financial_assistance';
};

export const resolveLedgerEntryRosMonths = (entry) => {
    const rawRosMonths = getLedgerEntryRosMonths(entry);
    const hasExplicitRosMonths = Object.prototype.hasOwnProperty.call(entry ?? {}, 'ros_months')
        || Object.prototype.hasOwnProperty.call(entry ?? {}, 'rosMonths');
    const normalizedRosMonths = typeof rawRosMonths === 'string'
        ? rawRosMonths.trim()
        : rawRosMonths;
    const explicitMonths = parseInt(normalizedRosMonths, 10);

    if (!Number.isNaN(explicitMonths) && explicitMonths > 0) {
        return explicitMonths;
    }

    if (hasExplicitRosMonths && (
        normalizedRosMonths === ''
        || normalizedRosMonths === null
        || normalizedRosMonths === undefined
        || normalizedRosMonths === '-'
        || normalizedRosMonths === '—'
    )) {
        return 0;
    }

    return isFourMonthLedgerTerm(getLedgerEntrySemester(entry)) ? 4 : 6;
};

export const calculateLedgerRosTotalMonths = (entries, { excludeFinancialAssistance = false } = {}) => {
    const seen = new Set();
    let totalMonths = 0;

    (entries ?? []).forEach((entry) => {
        if (excludeFinancialAssistance && isFinancialAssistanceLedgerPayment(entry)) {
            return;
        }

        const dedupeKey = buildLedgerRosDedupeKey(entry);
        if (seen.has(dedupeKey)) {
            return;
        }

        seen.add(dedupeKey);
        totalMonths += resolveLedgerEntryRosMonths(entry);
    });

    return totalMonths;
};

export const formatLedgerRosYearsLabel = (totalMonths) => {
    const years = totalMonths / 12;

    if (years <= 0) {
        return '—';
    }

    if (years === Math.floor(years)) {
        return `${Math.floor(years)} YRS`;
    }

    return `${years.toFixed(1)} YRS`;
};

export const buildScholarshipCoverageText = (entries, { excludeFinancialAssistanceFromRos = true } = {}) => {
    const normalizedEntries = Array.isArray(entries) ? entries : [];
    if (!normalizedEntries.length) {
        return 'N/A';
    }

    const totalMonths = calculateLedgerRosTotalMonths(normalizedEntries, {
        excludeFinancialAssistance: excludeFinancialAssistanceFromRos,
    });
    const totalYearsLabel = formatLedgerRosYearsLabel(totalMonths);
    const firstEntry = normalizedEntries[0];
    const prefix = [
        getLedgerEntryYearLevel(firstEntry),
        upper(getLedgerEntrySemester(firstEntry)),
        getLedgerEntryAcademicYear(firstEntry),
    ].filter(Boolean).join(', ');

    if (!prefix) {
        return totalYearsLabel === '—' ? 'N/A' : totalYearsLabel;
    }

    return totalYearsLabel === '—'
        ? prefix
        : `${prefix} (${totalYearsLabel})`;
};

export const textToEditorHtml = (value) => {
    const normalizedValue = normalizeText(value);
    if (!normalizedValue) {
        return '';
    }

    return `<p>${escapeHtml(normalizedValue).replace(/\n/g, '<br>')}</p>`;
};
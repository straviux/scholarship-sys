<template>
    <!--
        Individual Scholar Ledger  –  Long (8.5 × 13 in)
        All layout uses inline styles so renderVueTemplate captures them correctly.
    -->
    <div
        style="max-width:950px;margin:0 auto;background:#fff;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.4;color:#333;">

        <!-- ── Header ──────────────────────────────────────── -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
                    border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
            <p style="font-size:11px;font-weight:400;">Republic of the Philippines</p>
            <p style="font-size:11px;font-weight:400;">Provincial Government of Palawan</p>
            <p style="font-size:13px;font-weight:700;">Akbay sa Mag-aaral Yaman ng Kinabukasan</p>
            <p style="font-style:italic;font-size:11px;">(Programang Pang-Edukasyon para sa Palawenyo)</p>
        </div>

        <!-- ── Document Title ──────────────────────────────── -->
        <div style="text-align:center;margin-top:12pt;">
            <p style="font-size:13px;font-weight:700;">INDIVIDUAL SCHOLAR LEDGER</p>
        </div>

        <!-- ── Scholar Info Band ───────────────────────────── -->
        <div style="padding:6px 0 8px;margin-top:10pt;">
            <div style="display:flex;gap:24px;align-items:flex-start;">

                <!-- Left column -->
                <div style="flex:2.2;">
                    <table style="border-collapse:collapse;width:100%;">
                        <tr>
                            <td :style="LBL">NAME</td>
                            <td :style="VAL">{{ upperName }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">BIRTHDATE</td>
                            <td :style="VAL">{{ upperBirthdate }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">CIVIL STATUS</td>
                            <td :style="VAL">{{ upper(profile.civil_status) || '—' }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">DEGREE PROGRAM</td>
                            <td :style="VAL">{{ latestCourseName }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">PERMANENT ADDRESS</td>
                            <td :style="VAL">{{ permanentAddress }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">SCHOLARSHIP COVERAGE</td>
                            <td :style="VAL">{{ coverageStr }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">OTHER ASSISTANCE</td>
                            <td :style="VAL">{{ upper(otherAssistance) || 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">LICENSURE EXAMINATION RESULT</td>
                            <td :style="VAL">{{ upper(licensureExaminationResult) || 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">SCHOOL ATTENDED</td>
                            <td :style="VAL">{{ latestSchoolName }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Right column -->
                <div style="flex:1;">
                    <table style="border-collapse:collapse;width:100%;">
                        <tr>
                            <td :style="LBL">GENDER</td>
                            <td :style="VAL">{{ upper(profile.gender) || '—' }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">AGE</td>
                            <td :style="VAL">{{ ageStr }}</td>
                        </tr>
                        <tr>
                            <td :style="LBL">CONTACT NO.</td>
                            <td :style="VAL">{{ profile.contact_no || '—' }}</td>
                        </tr>
                    </table>
                </div>

            </div>
        </div>

        <!-- ── Ledger Table ─────────────────────────────────── -->
        <table style="width:100%;border-collapse:collapse;margin-top:14px;font-size:10px;">
            <thead>
                <tr>
                    <th :style="TH">SCHOLARSHIP COVERAGE<br>(YEAR LEVEL)</th>
                    <th :style="TH">ACADEMIC YEAR</th>
                    <th :style="TH">SEMESTER</th>
                    <th :style="TH">DATE OF OBLIGATION REQUEST</th>
                    <th :style="TH">OBLIGATION REQUEST NO.</th>
                    <th :style="TH">TYPE OF PAYMENT</th>
                    <th :style="TH">AMOUNT</th>
                    <th :style="TH">EQUIVALENT NO. OF YEARS FOR ROS</th>
                </tr>
            </thead>
            <tbody>
                <template v-if="yearGroupedRows.length > 0">
                    <template v-for="yg in yearGroupedRows" :key="yg.yearLevel">
                        <template v-for="(term, ti) in yg.termList" :key="term.termKey">
                            <tr v-for="(d, ri) in term.rows" :key="d.id ?? `${yg.yearLevel}-${ti}-${ri}`">
                                <td v-if="ti === 0 && ri === 0" :rowspan="yg.totalRows" :style="TD_COV">
                                    {{ upper(yg.yearLevel) }}
                                </td>
                                <td :style="TD">{{ d.academic_year || '—' }}</td>
                                <td :style="TD">{{ d.semester || '—' }}</td>
                                <td :style="TD">{{ formatDate(d.date_obligated) }}</td>
                                <td :style="TD">{{ d.obr_no || '—' }}</td>
                                <td :style="TD">{{ upper(d.disbursement_type) || '—' }}</td>
                                <td :style="TD_AMT">{{ d.amount != null ? money(d.amount) : '—' }}</td>
                                <td v-if="ri === 0" :rowspan="term.rows.length" :style="TD_AMT">
                                    {{ resolvedRosLabel(term.rows[0]) }}
                                </td>
                            </tr>
                        </template>
                    </template>
                </template>
                <template v-else>
                    <tr>
                        <td colspan="8" style="border:1px solid #000;padding:12px;text-align:center;color:#333;">
                            No records found.
                        </td>
                    </tr>
                </template>
                <tr>
                    <td colspan="6" :style="TD_TOT + 'text-align:right;'">TOTAL</td>
                    <td :style="TD_TOT">{{ money(grandTotal) }}</td>
                    <td :style="TD_TOT">{{ totalRosYrs }}</td>
                </tr>

            </tbody>
        </table>

        <!-- ── Signatories ─────────────────────────────────── -->
        <div style="display:flex;margin-top:28px;gap:24px;">
            <div style="min-width:200px;">
                <div style="font-size:10px;color:#333;margin-bottom:20px;">Prepared by:</div>
                <div
                    style="padding-top:48px;font-size:11px;font-weight:700;color:#333;text-transform:uppercase;text-decoration:underline;">
                    {{ upper(preparedBy) || '—' }}
                </div>
                <div style="font-size:10px;color:#333;text-transform:uppercase;">{{ upper(preparedByDesignation) }}
                </div>
            </div>
            <div style="min-width:200px;margin-left:auto;">
                <div style="font-size:10px;color:#333;margin-bottom:20px;">Approved by:</div>
                <div
                    style="padding-top:48px;font-size:11px;font-weight:700;color:#333;text-transform:uppercase;text-decoration:underline;">
                    NUR-AINA S. IBRAHIM
                </div>
                <div style="font-size:10px;color:#333;text-transform:uppercase;">Program Manager</div>
            </div>
        </div>
        <div style="display:flex;margin-top:48px;gap:24px;">
            <div style="min-width:200px;margin-top:28px;">
                <div style="font-size:10px;color:#333;margin-bottom:20px;">Conformed by:</div>
                <div
                    style="padding-top:48px;font-size:11px;font-weight:700;color:#333;text-transform:uppercase;text-decoration:overline;">
                    Signature over printed name
                </div>
                <div style="font-size:10px;color:#333;text-transform:uppercase;">(PGP SCHOLAR)</div>
            </div>
        </div>

        <!-- ── Footer ─────────────────────────────────────── -->
        <div style="margin-top:24px;text-align:right;font-size:9px;color:#999;">
            Date Generated: {{ today }}
        </div>

    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    preparedBy: { type: String, default: '' },
    preparedByDesignation: { type: String, default: '' },
    today: { type: String, default: '' },
    otherAssistance: { type: String, default: '' },
    licensureExaminationResult: { type: String, default: '' },
    ledgerEntries: { type: Array, default: () => [] },
});

/* ── inline style constants (resolved by Vue before innerHTML capture) ── */
const LBL = 'padding:2px 4px 2px 0;vertical-align:top;font-weight:600;font-size:9px;text-transform:uppercase;white-space:nowrap;';
const VAL = 'padding:2px 0 2px 12px;vertical-align:top;font-weight:600;font-size:9px;text-transform:uppercase;';
const TH = 'color:#333;font-weight:700;font-size:9px;text-transform:uppercase;letter-spacing:0.4px;text-align:center;padding:5px 4px;border:1px solid #000;';
const TD = 'border:1px solid #000;padding:4px 5px;vertical-align:middle;text-align:center;';
const TD_AMT = 'border:1px solid #000;padding:4px 5px;vertical-align:middle;text-align:center;font-weight:600;color:#333;';
const TD_COV = 'border:1px solid #000;padding:4px 5px;vertical-align:middle;text-align:center;font-weight:700;text-transform:uppercase;';
const TD_TOT = 'border:1px solid #000;border-top:2px solid #000;padding:4px 5px;vertical-align:middle;text-align:center;font-weight:700;';

/* ── helpers ─────────────────────────────────────────────── */

const upper = (v) => (v ? String(v).toUpperCase() : '');

const money = (val) =>
    parseFloat(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const formatDate = (d) => {
    if (!d) return '—';
    const dt = new Date(d);
    if (isNaN(dt)) return '—';
    const mm = String(dt.getMonth() + 1).padStart(2, '0');
    const dd = String(dt.getDate()).padStart(2, '0');
    const yyyy = dt.getFullYear();
    return `${mm}/${dd}/${yyyy}`;
};

// 4-month terms: anything explicitly trimester OR the 3rd semester (summer/midyear)
const is4MonthTerm = (semester) => {
    if (typeof semester !== 'string') return false;
    const s = semester.toLowerCase();
    return s.includes('trimester') || s.includes('3rd semester') || s.includes('3rd sem');
};

const normalizeYearLevel = (value) => {
    const normalized = upper(value).replace(/\s+/g, ' ').trim();

    if (/^(1ST|2ND|3RD|4TH|5TH|6TH|7TH|8TH)$/i.test(normalized)) {
        return `${normalized} YEAR`;
    }

    return normalized || '—';
};

const rosMonthsForEntry = (entry) => {
    const explicitMonths = parseInt(entry?.ros_months);

    if (!isNaN(explicitMonths) && explicitMonths > 0) {
        return explicitMonths;
    }

    if (entry?.ros_months === '') {
        return 0;
    }

    return is4MonthTerm(entry?.semester) ? 4 : 6;
};

const resolvedRosLabel = (entry) => {
    const months = rosMonthsForEntry(entry);

    if (months <= 0) {
        return '—';
    }

    return `${months} MONTHS`;
};

/* ── profile derived ─────────────────────────────────────── */

const upperName = computed(() => {
    const p = props.profile;
    return [
        upper(p.last_name) + ',',
        upper(p.first_name),
        p.middle_name ? upper(p.middle_name) : null,
        p.extension_name ? upper(p.extension_name) : null,
    ].filter(Boolean).join(' ');
});

const upperBirthdate = computed(() => {
    const d = props.profile.birthdate || props.profile.date_of_birth;
    if (!d) return '—';
    const dt = new Date(d);
    if (isNaN(dt)) return '—';
    return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }).toUpperCase();
});

const ageStr = computed(() => {
    const d = props.profile.birthdate || props.profile.date_of_birth;
    if (!d) return '—';
    const birth = new Date(d);
    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    const m = now.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) age--;
    return `${age} YRS OLD`;
});

const permanentAddress = computed(() => {
    const p = props.profile;
    return [p.address, p.barangay, p.municipality].filter(Boolean).join(', ') || '—';
});

// Latest active scholarship record
const latestRecord = computed(() => {
    const grants = (props.profile.scholarship_grant || []).filter(g => !g.deleted_at);
    return grants.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0] ?? null;
});

const latestCourseName = computed(() =>
    latestRecord.value?.course?.name ?? '—'
);

const latestSchoolName = computed(() =>
    latestRecord.value?.school_name ?? latestRecord.value?.school?.name ?? '—'
);

const otherAssistance = computed(() =>
    props.otherAssistance ?? ''
);

const licensureExaminationResult = computed(() =>
    props.licensureExaminationResult ?? ''
);

const YEAR_ORDER = ['1ST YEAR', '2ND YEAR', '3RD YEAR', '4TH YEAR', '5TH YEAR', 'PGI', 'REVIEW'];

const normalizedLedgerEntries = computed(() => {
    return (props.ledgerEntries || [])
        .map((entry, index) => {
            const amount = entry?.amount === '' || entry?.amount === null || entry?.amount === undefined
                ? null
                : parseFloat(entry.amount);

            return {
                id: entry?.id ?? `ledger-${index}`,
                sortIndex: index,
                year_level: normalizeYearLevel(entry?.year_level),
                academic_year: entry?.academic_year ?? '',
                semester: entry?.semester ?? '',
                date_obligated: entry?.date_obligated ?? '',
                obr_no: entry?.obr_no ?? '',
                disbursement_type: entry?.disbursement_type ?? '',
                amount: Number.isFinite(amount) ? amount : null,
                ros_months: entry?.ros_months,
            };
        })
        .filter((entry) => {
            return [entry.academic_year, entry.semester, entry.date_obligated, entry.obr_no, entry.disbursement_type]
                .some((value) => String(value ?? '').trim() !== '') || entry.amount !== null;
        });
});

const sortedLedgerEntries = computed(() => {
    return [...normalizedLedgerEntries.value].sort((a, b) => {
        const yearA = YEAR_ORDER.indexOf(a.year_level);
        const yearB = YEAR_ORDER.indexOf(b.year_level);
        const orderA = yearA === -1 ? 999 : yearA;
        const orderB = yearB === -1 ? 999 : yearB;

        if (orderA !== orderB) {
            return orderA - orderB;
        }

        return a.sortIndex - b.sortIndex;
    });
});

const yearGroupedRows = computed(() => {
    const yearMap = {};
    const yearSequence = [];

    sortedLedgerEntries.value.forEach((entry) => {
        const yearLevel = entry.year_level ?? '—';
        if (!yearMap[yearLevel]) {
            yearMap[yearLevel] = {};
            yearSequence.push(yearLevel);
        }

        const termKey = `${entry.academic_year ?? ''}|${entry.semester ?? ''}`;
        if (!yearMap[yearLevel][termKey]) {
            yearMap[yearLevel][termKey] = [];
        }

        yearMap[yearLevel][termKey].push(entry);
    });

    return yearSequence.map((yearLevel) => {
        const termList = Object.entries(yearMap[yearLevel]).map(([termKey, rows]) => ({ termKey, rows }));
        return {
            yearLevel,
            termList,
            totalRows: termList.reduce((sum, term) => sum + term.rows.length, 0),
        };
    });
});

const grandTotal = computed(() =>
    normalizedLedgerEntries.value.reduce((sum, entry) => sum + parseFloat(entry.amount || 0), 0)
);

/* ── ROS total ───────────────────────────────────────────── */
const totalRosYrs = computed(() => {
    const seen = new Set();
    let totalMonths = 0;
    normalizedLedgerEntries.value.forEach((entry) => {
        const dedupeKey = `${entry.year_level ?? ''}|${entry.semester ?? ''}|${entry.academic_year ?? ''}`;
        if (!seen.has(dedupeKey)) {
            seen.add(dedupeKey);
            totalMonths += rosMonthsForEntry(entry);
        }
    });
    const yrs = totalMonths / 12;
    if (yrs <= 0) return '—';
    if (yrs === Math.floor(yrs)) return `${Math.floor(yrs)} YRS`;
    return `${yrs.toFixed(1)} YRS`;
});

/* ── coverage string ─────────────────────────────────────── */
const coverageStr = computed(() => {
    const entries = sortedLedgerEntries.value;
    if (!entries.length) return 'N/A';

    const seen = new Set();
    let totalMonths = 0;
    entries.forEach((entry) => {
        const dedupeKey = `${entry.year_level ?? ''}|${entry.semester ?? ''}|${entry.academic_year ?? ''}`;
        if (!seen.has(dedupeKey)) {
            seen.add(dedupeKey);
            totalMonths += rosMonthsForEntry(entry);
        }
    });

    const yrs = totalMonths / 12;
    const yrsStr = yrs <= 0 ? '' : yrs === Math.floor(yrs)
        ? ` (${Math.floor(yrs)} YRS)` : ` (${yrs.toFixed(1)} YRS)`;

    const first = entries[0];
    return [
        first?.year_level ?? '',
        upper(first?.semester ?? ''),
        first?.academic_year ?? '',
    ].filter(Boolean).join(', ') + yrsStr;
});
</script>

<style>
/* All layout is inline-style only – no classes needed here. */
._placeholder {
    display: none;
}
</style>

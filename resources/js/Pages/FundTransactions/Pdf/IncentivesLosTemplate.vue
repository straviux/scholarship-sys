<template>
    <!--
        LIST OF SCHOLARS (INCENTIVES) — A4 Portrait (576pt content width)
        Copy of LosTemplate.vue with Course and School columns added after Name.
        Uses fixed-width flex columns measured in pt.

        Column system:
        ┌───────┬──────────────┬──────────────┬──────────────┬──────────┬──────────┐
        │  No.  │ Name of Scholar │   Course   │   School   │   Year   │  Amount  │
        │ 40pt  │     flex:1      │   100pt    │   100pt    │   60pt   │  100pt   │
        └───────┴──────────────┴──────────────┴──────────────┴──────────┴──────────┘
    -->
    <div style="display:flex;flex-direction:column;min-height:90vh;">

        <!-- GOVERNMENT HEADER -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
                    padding:10pt 4pt;min-height:70pt;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:8%;top:50%;transform:translateY(-50%);width:60pt;height:auto;" />
            <img src="/images/yakap-logo.svg" alt="Yakap Logo"
                style="position:absolute;right:8%;top:50%;transform:translateY(-50%);width:60pt;height:auto;" />
            <p class="t-12">Republic of the Philippines</p>
            <p class="t-11">Provincial Government of Palawan</p>
            <p class="t-11">OFFICE OF THE GOVERNOR</p>
            <p class="bold t-11">YAKAP SA EDUKASYON</p>
            <p class="t-10">SCHOLARSHIP PROGRAM</p>
        </div>

        <!-- Title / Term / Honor -->
        <div style="padding:14pt 0 0 0;text-align:center;">
            <p class="bold t-12" style="padding-top:4pt;">LIST OF SCHOLAR/S</p>
            <p v-if="termAcademic" class="bold t-12" style="padding-top:2pt;">{{ termAcademic }}</p>
            <p v-if="honorNameLine" class="bold t-12" style="padding-top:2pt;">{{ honorNameLine }}</p>
        </div>

        <!-- TABLE -->
        <div style="display:flex;flex-direction:column;padding-top:10pt;">

            <!-- Header row -->
            <div style="display:flex;border:1pt solid #000;">
                <div class="bold t-11"
                    style="width:40pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    No.</div>
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    Name of Scholar/s</div>
                <div class="bold t-11"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    Course</div>
                <div class="bold t-11"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    School</div>
                <div class="bold t-11"
                    style="width:60pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    Year</div>
                <div class="bold t-11"
                    style="width:100pt;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    Amount</div>
            </div>

            <!-- Scholar data rows -->
            <div v-for="(s, i) in scholars" :key="i"
                style="display:flex;border-left:1pt solid #000;border-right:1pt solid #000;border-bottom:1pt solid #000;">
                <div class="bold t-10"
                    style="width:40pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    {{ i + 1 }}</div>
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;padding:4pt 6pt;">
                    {{ s.name }}</div>
                <div class="bold t-11"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;padding:4pt 6pt;">
                    {{ s.course || 'N/A' }}</div>
                <div class="bold t-11"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;padding:4pt 6pt;">
                    {{ s.school || 'N/A' }}</div>
                <div class="bold t-11"
                    style="width:60pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    {{ s.year || 'N/A' }}</div>
                <div class="bold t-11"
                    style="width:100pt;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    {{ money(s.amount) }}</div>
            </div>

            <!-- Empty rows if < 2 scholars -->
            <div v-for="n in emptyRows" :key="'e' + n"
                style="display:flex;border-left:1pt solid #000;border-right:1pt solid #000;border-bottom:1pt solid #000;">
                <div style="width:40pt;border-right:1pt solid #000;min-height:18pt;">&nbsp;</div>
                <div style="flex:1;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:60pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;">&nbsp;</div>
            </div>

            <!-- No scholars message -->
            <div v-if="scholars.length === 0 && emptyRows === 0"
                style="display:flex;border-left:1pt solid #000;border-right:1pt solid #000;border-bottom:1pt solid #000;">
                <div class="t-10" style="flex:1;display:flex;align-items:center;justify-content:center;padding:12pt;">
                    No scholars in this list</div>
            </div>

            <!-- TOTAL row -->
            <div
                style="display:flex;border-left:1pt solid #000;border-right:1pt solid #000;border-bottom:1pt solid #000;">
                <div style="width:40pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="flex:1;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="bold t-12"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    TOTAL</div>
                <div class="bold t-12"
                    style="width:60pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="bold t-12"
                    style="width:100pt;display:flex;align-items:center;justify-content:flex-end;padding:4pt 8pt;">
                    {{ money(grandTotal) }}</div>
            </div>

        </div><!-- /table -->

        <!-- SIGNATURE -->
        <div style="display:flex;justify-content:center;padding-top:140pt;">
            <div style="text-align:center;">
                <p class="bold t-12 underline">NUR-AINA S. IBRAHIM</p>
                <p class="t-11" style="margin-top:2pt;">Program Manager</p>
                <p class="t-11">Yakap sa Edukasyon</p>
            </div>
        </div>

    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    voucher: { type: Object, required: true },
    scholarDetails: { type: Array, default: () => [] },
});

/* ── helpers ─────────────────────────────────────── */
const money = (n) =>
    '₱ ' + parseFloat(n || 0)
        .toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ── derived display fields ──────────────────────── */



const LATIN_HONOR_TYPES = ['cum_laude', 'magna_cum_laude', 'summa_cum_laude'];


const isDeansList = computed(() => props.voucher.incentive_type === 'deans_list');
const isLatinHonor = computed(() => LATIN_HONOR_TYPES.includes(props.voucher.incentive_type));

/** "DEAN'S LISTER" for Dean's List, or the Latin Honor label for graduation honors */
const honorNameLine = computed(() => {
    if (isDeansList.value) return "DEAN'S LISTER";
    if (isLatinHonor.value) return incentiveTypeLabel.value;
    return '';
});

/** Dean's List: selected term(s) + academic year. Latin Honor: "AY [academic year]" only. */
const termAcademic = computed(() => {
    const academicYear = props.voucher.academic_year || props.scholarDetails?.[0]?.academic_year || '';

    if (isLatinHonor.value) {
        return academicYear ? `AY ${academicYear}` : '';
    }

    const term = props.voucher.semester || props.scholarDetails?.[0]?.term || '';
    if (term && academicYear) return `${term} ${academicYear}`;
    return term || academicYear || '';
});

/* ── scholar data ────────────────────────────────── */
const scholarIds = computed(() =>
    Array.isArray(props.voucher.scholar_ids) ? props.voucher.scholar_ids : []
);

const scholars = computed(() =>
    scholarIds.value.map((s) => {
        const profileId = typeof s === 'object' ? s.profile_id : s;
        const rawAmount = typeof s === 'object' ? (s.amount ?? props.voucher.amount) : (props.voucher.amount ?? 0);
        const detail = props.scholarDetails.find(d => String(d.profile_id) === String(profileId));

        let name;
        if (detail) {
            name = detail.last_name + ', ' + detail.first_name;
            if (detail.middle_name) name += ' ' + detail.middle_name;
            if (detail.extension_name) name += ' ' + detail.extension_name;
        } else {
            name = (typeof s === 'object' ? (s.name || '') : '')
                || `SCHOLAR [${profileId}]`;
        }

        // Use voucher year_level if set, otherwise from detail
        const year = props.voucher.year_level || detail?.year_level || '';

        return {
            name,
            course: detail?.course_name || '',
            school: detail?.school_name || '',
            year,
            amount: parseFloat(rawAmount) || 0,
        };
    })
);

const grandTotal = computed(() =>
    scholars.value.reduce((sum, s) => sum + s.amount, 0)
);

/* Pad to minimum 2 rows */
const emptyRows = computed(() => Math.max(0, 2 - scholars.value.length));
</script>

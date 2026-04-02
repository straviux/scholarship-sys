<template>
    <!--
        LIST OF SCHOLARS — A4 Portrait (576pt content width)
        Uses fixed-width flex columns measured in pt.

        Column system:
        ┌───────┬──────────────────┬──────────┬──────────┐
        │  No.  │  Name of Scholar │   Year   │  Amount  │
        │ 40pt  │     flex:1       │   70pt   │  110pt   │
        └───────┴──────────────────┴──────────┴──────────┘
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
            <p class="bold t-11">AKBAY SA MAG-AARAL YAMAN NG KINABUKASAN</p>
            <p class="t-10">(PROGRAMANG PANG-EDUKASYON PARA SA PALAWEÑO)</p>
        </div>

        <!-- School / Title / Term / Course -->
        <div style="padding:14pt 0 0 0;text-align:center;">
            <p v-if="schoolName" class="bold t-12">{{ schoolName }}</p>
            <p class="bold t-12" style="padding-top:4pt;">LIST OF SCHOLARS</p>
            <p v-if="voucher.obr_type === 'REIMBURSEMENT'" class="bold t-12" style="padding-top:2pt;">FOR REIMBURSEMENT
            </p>
            <p v-if="termAcademic" class="bold t-12" style="padding-top:2pt;">{{ termAcademic }}</p>
            <p v-if="courseName" class="bold t-12" style="padding-top:20pt;">{{ courseName }}</p>
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
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    Year</div>
                <div class="bold t-11"
                    style="width:110pt;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
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
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    {{ s.year || 'N/A' }}</div>
                <div class="bold t-11"
                    style="width:110pt;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    {{ money(s.amount) }}</div>
            </div>

            <!-- Empty rows if < 2 scholars -->
            <div v-for="n in emptyRows" :key="'e' + n"
                style="display:flex;border-left:1pt solid #000;border-right:1pt solid #000;border-bottom:1pt solid #000;">
                <div style="width:40pt;border-right:1pt solid #000;min-height:18pt;">&nbsp;</div>
                <div style="flex:1;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:70pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:110pt;">&nbsp;</div>
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
                <div class="bold t-12"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt 2pt;">
                    TOTAL</div>
                <div class="bold t-12"
                    style="width:110pt;display:flex;align-items:center;justify-content:flex-end;padding:4pt 8pt;">
                    {{ money(grandTotal) }}</div>
            </div>

        </div><!-- /table -->

        <!-- SIGNATURE -->
        <div style="display:flex;justify-content:center;padding-top:140pt;">
            <div style="text-align:center;">
                <p class="bold t-12 underline">NUR-AINA S. IBRAHIM</p>
                <p class="t-11" style="margin-top:2pt;">Program Manager</p>
                <p class="t-11">Akbay sa Mag-Aaral Yaman ng Kinabukasan</p>
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
    '₱\u00a0' + parseFloat(n || 0)
        .toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ── derived display fields ──────────────────────── */

/** School name: payee_name if payee_type is school, otherwise from first scholar detail */
const schoolName = computed(() => {
    if (props.voucher.payee_type === 'school' && props.voucher.payee_name) {
        return props.voucher.payee_name;
    }
    // fallback to first scholar's school_name
    return props.scholarDetails?.[0]?.school_name || '';
});

/** Course: voucher.course or first scholar detail */
const courseName = computed(() =>
    props.voucher.course || props.scholarDetails?.[0]?.course_name || ''
);

/** Term + Academic Year combined */
const termAcademic = computed(() => {
    const term = props.voucher.semester || props.scholarDetails?.[0]?.term || '';
    const ay = props.voucher.academic_year || props.scholarDetails?.[0]?.academic_year || '';
    if (term && ay) return `${term} ${ay}`;
    return term || ay || '';
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

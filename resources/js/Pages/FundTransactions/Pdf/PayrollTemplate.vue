<template>
    <!--
        GENERAL PAYROLL — Landscape 13 × 8.5 in (936pt content width at 96 dpi)
        Uses fixed-width flex columns measured in pt for easy manual layout.

        Column system (total ≈ 936pt usable at 6mm margin):
        ┌───────┬──────────────┬──────────┬──────────┬──────────────────┐
        │  No.  │    Names     │ Yr Level │  Amount  │    Signature     │
        │ 40pt  │   300pt      │  100pt   │  120pt   │     flex:1       │
        └───────┴──────────────┴──────────┴──────────┴──────────────────┘
    -->
    <div style="display:flex;flex-direction:column;min-height:90vh;">

        <!-- GOVERNMENT HEADER -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
                    padding:8pt 4pt;min-height:58pt;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:22%;top:50%;transform:translateY(-50%);width:54pt;height:auto;" />
            <p class="bold t-12">GENERAL PAYROLL</p>
            <p class="t-11">PROVINCIAL GOVERNMENT OF PALAWAN</p>
            <p class="bold t-11">AKBAY SA MAG-AARAL YAMAN NG KINABUKASAN</p>
            <p class="t-10">(PROGRAMANG PANG-EDUKASYON PARA SA PALAWEÑO)</p>
        </div>

        <!-- Explanation row -->
        <div v-if="explanation" style="padding:4pt 0;text-align:center;">
            <span class="t-10 blk" v-html="explanation"></span>
        </div>

        <!-- Academic year / semester -->
        <div style="padding:2pt 0 6pt 0;">
            <span class="bold t-10">FOR ACADEMIC YEAR {{ termLabel }} SEM {{ voucher.academic_year || '' }}</span>
        </div>

        <!-- TABLE -->
        <div style="flex:1;display:flex;flex-direction:column;">

            <!-- Header row -->
            <div class="dv-row b-l b-r" style="border-top:1pt solid #000;">
                <div class="bold t-11"
                    style="width:40pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    No.</div>
                <div class="bold t-11"
                    style="width:300pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    Names</div>
                <div class="bold t-11"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    Year Level</div>
                <div class="bold t-11"
                    style="width:120pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    Amount</div>
                <div class="bold t-11"
                    style="flex:1;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    Signature</div>
            </div>

            <!-- Scholar data rows -->
            <div v-for="(s, i) in scholars" :key="i" class="dv-row b-l b-r">
                <div class="bold t-10"
                    style="width:40pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    {{ i + 1 }}</div>
                <div class="bold t-11"
                    style="width:300pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt 4pt;">
                    {{ s.name }}</div>
                <div class="bold t-11"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    {{ s.year || 'N/A' }}</div>
                <div class="bold t-11"
                    style="width:120pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    {{ money(s.amount) }}</div>
                <div style="flex:1;display:flex;align-items:center;padding:2pt;">&nbsp;</div>
            </div>

            <!-- Empty rows if < 5 scholars -->
            <div v-for="n in emptyRows" :key="'e' + n" class="dv-row b-l b-r">
                <div style="width:40pt;border-right:1pt solid #000;min-height:18pt;">&nbsp;</div>
                <div style="width:300pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:120pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="flex:1;">&nbsp;</div>
            </div>

            <!-- No scholars message -->
            <div v-if="scholars.length === 0 && emptyRows === 0" class="dv-row">
                <div class="t-10" style="flex:1;display:flex;align-items:center;justify-content:center;padding:6pt;">
                    No scholars included in this payroll</div>
            </div>

            <!-- GRAND TOTAL row -->
            <div class="dv-row b-l b-r">
                <div style="width:40pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:300pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="bold t-11"
                    style="width:100pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    GRAND TOTAL</div>
                <div class="bold t-11"
                    style="width:120pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    {{ money(grandTotal) }}</div>
                <div style="flex:1;">&nbsp;</div>
            </div>

        </div><!-- /table -->

        <!-- CERTIFICATION ROW 1 -->
        <div style="display:flex;padding:0;margin-top:-8pt">
            <div class="t-9" style="width:300pt;">CERTIFIED CORRECT:</div>
            <div class="t-9" style="width:280pt;padding-left:20pt;">CERTIFIED cash available</div>
            <div class="t-9" style="flex:1;padding-left:20pt;">
                CERTIFIED: Each scholars whose name appears above<br>has been paid the amount indicated opposite his/her
                name
            </div>
        </div>

        <!-- SIGNATURE ROW 1 -->
        <div style="display:flex;padding:36pt 0 0 0;">
            <div style="width:300pt;">
                <div style="width:200pt;text-align:center;">
                    <p class="bold t-11">NUR-AINA S. IBRAHIM</p>
                    <p class="t-9" style="border-top:1pt solid #000;margin-top:2pt;">Program Manager</p>
                </div>
            </div>
            <div style="width:280pt;padding-left:20pt;">
                <div style="width:200pt;text-align:center;">
                    <p class="bold t-11">ELINO P. MONDRAGON</p>
                    <p class="t-9" style="border-top:1pt solid #000;margin-top:2pt;">Provincial Treasurer</p>
                </div>
            </div>
            <div style="flex:1;padding-left:20pt;">
                <div style="width:200pt;text-align:center;">
                    <p>&nbsp;</p>
                    <p class="t-9" style="border-top:1pt solid #000;margin-top:2pt;">Disbursing Officer</p>
                </div>
            </div>
        </div>

        <!-- CERTIFICATION ROW 2 -->
        <div style="display:flex;padding:16pt 0 0 0;">
            <div class="t-9" style="width:300pt;">CERTIFIED CORRECT: as to completeness and propriety of supporting
                documents.</div>
            <div class="t-9" style="width:280pt;padding-left:20pt;">Approved for payment.</div>
            <div style="flex:1;">&nbsp;</div>
        </div>

        <!-- SIGNATURE ROW 2 -->
        <div style="display:flex;padding:36pt 0 0 0;">
            <div style="width:300pt;">
                <div style="width:200pt;text-align:center;">
                    <p class="bold t-11">ERLINDA T. RIZADA</p>
                    <p class="t-9" style="border-top:1pt solid #000;margin-top:2pt;">Provincial Accountant</p>
                    <p class="t-9">Provincial Accountant's Office</p>
                </div>
            </div>
            <div style="width:280pt;padding-left:20pt;">
                <div style="width:200pt;text-align:center;">
                    <p class="bold t-11">AMY ROA ALVAREZ</p>
                    <p class="t-9" style="border-top:1pt solid #000;margin-top:2pt;">Governor</p>
                </div>
            </div>
            <div style="flex:1;padding-left:20pt;">
                <div style="width:200pt;text-align:center;">
                    <p>&nbsp;</p>
                    <p class="t-9" style="border-top:1pt solid #000;margin-top:2pt;">Date</p>
                </div>
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

/* ── explanation (Quill HTML) ────────────────────── */
const explanation = computed(() => (props.voucher.explanation || '').trim());

/* ── term label ──────────────────────────────────── */
const termLabel = computed(() => {
    const term = props.voucher.semester || '';
    // Extract first word (e.g., "1ST" from "1ST SEMESTER")
    return term.split(' ')[0] || term;
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

        return {
            name,
            year: detail?.year_level || '',
            amount: parseFloat(rawAmount) || 0,
        };
    })
);

const grandTotal = computed(() =>
    scholars.value.reduce((sum, s) => sum + s.amount, 0)
);

/* Pad to minimum 5 rows */
const emptyRows = computed(() => Math.max(0, 5 - scholars.value.length));
</script>

<template>
    <!--
        Column system (content width = 576pt, box-sizing:border-box):
        TOP SECTION:   flex:1 (explanation/left) | width:160pt (amount/right)
        PAYEE/ADDR:    width:86pt (label) | flex:1 (name) | width:132pt (TIN) | width:160pt (amount)
        BOTTOM HALF:   width:90pt (C1/C4 labels) | width:140pt (C2 name-A) | width:58pt (C3 date) | flex:1 (C5 name-B)
                       Left half  = C1+C2+C3 = 90+140+58 = 288pt
                       Right half = C4+C5    = 90+rest   = 288pt
        SIG ROWS merge C2+C3 → width:198pt
    -->
    <div style="display:flex;flex-direction:column;min-height:90vh;border:1pt solid #000;">

        <!-- GOVERNMENT HEADER -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
                    border-bottom:1pt solid #000;padding:8pt 4pt;min-height:58pt;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:15%;top:50%;transform:translateY(-50%);width:54pt;height:auto;" />
            <p style="font-weight:bold;font-size:12pt;text-align:center;">Republic of the Philippines</p>
            <p style="font-weight:bold;font-size:12pt;text-align:center;">PROVINCIAL GOVERNMENT OF PALAWAN</p>
            <p style="font-size:11pt;text-align:center;">OFFICE OF THE GOVERNOR</p>
        </div>

        <!-- MAIN CONTENT -->
        <div style="display:flex;flex-direction:column;flex:1;">

            <!-- Title row: flex:1 | 160pt -->
            <div class="dv-row">
                <div class="bold t-12 center"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt;">
                    DISBURSEMENT VOUCHER
                </div>
                <div class="t-9" style="width:100pt;display:flex;align-items:center;padding:2pt;">No.</div>
            </div>

            <!-- Mode of Payment: 86pt | flex:1 (full width, no amount col) -->
            <div class="dv-row">
                <div class="t-9"
                    style="width:76pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Mode of Payment:
                </div>
                <div style="flex:1;display:flex;align-items:center;gap:12pt;padding:4pt 6pt;">
                    <div style="display:flex;align-items:center;gap:4pt;">
                        <div style="width:14pt;height:14pt;border:1pt solid #000;flex-shrink:0;"></div>
                        <span class="t-10">Check</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:4pt;">
                        <div style="width:14pt;height:14pt;border:1pt solid #000;flex-shrink:0;"></div>
                        <span class="t-10">Cash</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:4pt;">
                        <div style="width:14pt;height:14pt;border:1pt solid #000;flex-shrink:0;"></div>
                        <span class="t-10">Others</span>
                    </div>
                </div>
            </div>

            <!-- Payee row: 86pt | flex:1 | 132pt | 160pt -->
            <div class="dv-row" style="min-height:36pt;">
                <div class="t-9"
                    style="width:76pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Payee:
                </div>
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    {{ voucher.payee_name || ph }}
                </div>
                <div style="width:132pt;border-right:1pt solid #000;display:flex;flex-direction:column;">
                    <div style="min-height:28pt;border-bottom:1pt solid #000;display:flex;padding:2pt;">
                        <span class="t-8">TIN/Employee No.</span>
                    </div>
                    <div style="min-height:28pt;display:flex;padding:2pt;">
                        <span class="t-8">Responsibility Center</span>
                    </div>
                </div>
                <div style="width:100pt;display:flex;flex-direction:column;">
                    <div style="min-height:28pt;border-bottom:1pt solid #000;display:flex;padding:2pt;">
                        <span class="t-8">Obligation Request No.</span>
                    </div>
                    <div style="min-height:28pt;">&nbsp;</div>
                </div>
            </div>

            <!-- Address row: 86pt | flex:1 | 132pt | 160pt -->
            <div class="dv-row" style="min-height:36pt;">
                <div class="t-9"
                    style="width:76pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Address:
                </div>
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    {{ voucher.payee_address || ph }}
                </div>
                <div style="width:132pt;border-right:1pt solid #000;padding-left:2pt;">
                    <p class="t-8">Office/Unit/Project: Education for</p>
                    <p class="t-8" style="margin-top: -4pt !important;"> Development Program</p>
                </div>
                <div style="width:100pt;display:flex;padding:2pt;">
                    <span class="t-8">Code</span>
                </div>
            </div>



            <!-- Column headers: EXPLANATION | AMOUNT — flex:1 | 160pt -->
            <div class="dv-row">
                <div class="bold t-10 center"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt;">
                    EXPLANATION
                </div>
                <div class="bold t-10 center"
                    style="width:100pt;display:flex;align-items:center;justify-content:center;padding:4pt;">
                    AMOUNT
                </div>
            </div>

            <!-- Blank gap row (no bottom border): flex:1 | 160pt -->
            <div style="display:flex;min-height:10pt;">
                <div style="flex:1;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;">&nbsp;</div>
            </div>

            <!-- Explanation content + Amount: flex:1 | 160pt -->
            <div style="display:flex;min-height:30pt;">
                <div class="t-11 blk center"
                    style="flex:1;border-right:1pt solid #000;line-height:1.3;text-align:center;"
                    v-safe-html="explanation || ph"></div>
                <div class="t-11"
                    style="width:100pt;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    {{ money(voucher.amount) }}
                </div>
            </div>

            <!-- Scholar name (conditional): flex:1 | 160pt -->
            <div v-if="showScholarName" style="display:flex;min-height:18pt;">
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    ({{ scholarName }})
                </div>
                <div style="width:100pt;">&nbsp;</div>
            </div>

            <!-- FLEX SPACER — pushes bottom section down: flex:1 | 160pt -->
            <div style="flex:1;display:flex;min-height:20pt;">
                <div style="flex:1;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;">&nbsp;</div>
            </div>

        </div><!-- /main content -->

        <!-- BOTTOM SECTION
             5-col: 90pt | 140pt | 58pt | 90pt | flex:1
             Left half  = 90+140+58 = 288pt (C1+C2+C3)
             Right half = 90+flex:1  = 288pt (C4+C5)
             Sig rows merge C2+C3 → 198pt fixed
        -->
        <div>

            <!-- TOTAL row: flex:1 | 160pt -->
            <div class="dv-row" style="border-top:1pt solid #000;">
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    TOTAL
                </div>
                <div class="bold t-11"
                    style="width:100pt;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    {{ money(voucher.amount) }}
                </div>
            </div>

            <!-- Certification A + B: 20pt | 268pt | 20pt | flex:1 (matches C/D exactly) -->
            <div class="dv-row">
                <!-- A letter cell -->
                <div
                    style="width:20pt;height:20pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;align-items:flex-start;justify-content:center;padding-top:4pt;">
                    <span class="bold t-10">A</span>
                </div>
                <!-- Section A content -->
                <div style="width:268pt;border-right:1pt solid #000;padding:4pt;display:flex;flex-direction:column;">
                    <span class="bold">Certified</span>
                    <div style="display:flex;align-items:flex-start;margin-top:8pt;margin-bottom:4pt;margin-left:4pt;">
                        <div style="border:1pt solid #000;width:14pt;height:14pt;flex-shrink:0;margin-right:4pt;"></div>
                        <span class="t-9">Allotment obligated for the purpose as indicated above</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;margin-top:8pt;margin-left:4pt;">
                        <div style="border:1pt solid #000;width:14pt;height:14pt;flex-shrink:0;margin-right:4pt;"></div>
                        <span class="t-9">Supporting documents completed</span>
                    </div>
                </div>
                <!-- B letter cell -->
                <div
                    style="width:20pt; height:20pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;align-items:flex-start;justify-content:center;padding-top:4pt;">
                    <span class="bold t-10">B</span>
                </div>
                <!-- Section B content -->
                <div style="flex:1;padding:4pt;display:flex;flex-direction:column;">
                    <span class="bold">Certified</span>
                    <div style="display:flex;align-items:flex-start;margin-top:8pt;margin-left:4pt;">
                        <div style="border:1pt solid #000;width:14pt;height:14pt;flex-shrink:0;margin-right:4pt;"></div>
                        <span class="t-9">Funds Available</span>
                    </div>
                </div>
            </div>

            <!-- Signature row A/B: 90pt | 198pt | 90pt | flex:1 -->
            <div class="dv-row" style="min-height:30pt;">
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Signature</div>
                <div style="width:198pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Signature</div>
                <div style="flex:1;">&nbsp;</div>
            </div>

            <!-- Printed Name row: 90pt | 140pt | 58pt | 90pt | flex:1 -->
            <div style="display:flex;min-height:24pt;">
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Printed Name</div>
                <div class="bold t-11 center"
                    style="width:140pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;align-items:center;justify-content:center;">
                    ERLINDA T. RIZADA</div>
                <div class="t-9"
                    style="width:58pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;padding:2pt;">
                    Date</div>
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Printed Name</div>
                <div class="bold t-11 center"
                    style="flex:1;border-bottom:1pt solid #000;display:flex;align-items:center;justify-content:center;">
                    ELINO P. MONDRAGON</div>
            </div>

            <!-- Position row: 90pt | 140pt | 58pt | 90pt | flex:1 -->
            <div class="dv-row" style="min-height:28pt;">
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Position</div>
                <div class="t-9 center"
                    style="width:140pt;border-right:1pt solid #000;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2pt;line-height:1.3;">
                    <span>Provincial Accountant</span>
                    <span>Provincial Accountant's Office</span>
                </div>
                <div style="width:58pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Position</div>
                <div class="t-9 center"
                    style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2pt;line-height:1.3;">
                    <span>Provincial Treasurer</span>
                    <span>Treasurer/Authorized Representative</span>
                </div>
            </div>

            <!-- C/D row: 20pt | flex:1 | 20pt | flex:1 (each half = 288pt) -->
            <div class="dv-row" style="min-height:18pt;">
                <div class="bold t-10 center"
                    style="width:20pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;">
                    C</div>
                <div class="bold t-10"
                    style="width:268pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Approved for payment</div>
                <div class="bold t-10 center"
                    style="width:20pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;">
                    D</div>
                <div class="bold t-10" style="flex:1;display:flex;align-items:center;padding:2pt;">Approved for payment
                </div>
            </div>

            <!-- Signature C / Check No. row: 90pt | 198pt | 90pt | flex:1 -->
            <div class="dv-row" style="min-height:30pt;">
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Signature</div>
                <div style="width:198pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-9" style="width:90pt;border-right:1pt solid #000;display:flex;padding:2pt;">
                    Check No.</div>
                <div style="flex:1;display:flex;flex-direction:column;">
                    <div class="t-9"
                        style="flex:1;border-bottom:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                        Bank Name</div>
                    <div style="min-height:18pt;">&nbsp;</div>
                </div>
            </div>

            <!-- AMY ROA ALVAREZ row: 90pt | 140pt | 58pt | 90pt | flex:1 -->
            <div class="dv-row" style="min-height:28pt;">
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Printed Name</div>
                <div
                    style="width:140pt;border-right:1pt solid #000;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2pt;line-height:1.1;">
                    <span class="bold t-11">AMY ROA ALVAREZ</span>
                    <span class="t-9">Governor</span>
                </div>
                <div class="t-9" style="width:58pt;border-right:1pt solid #000;display:flex;padding:2pt;">
                    Date</div>
                <div class="t-9"
                    style="width:90pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Signature</div>
                <div style="flex:1;">&nbsp;</div>
            </div>

            <!-- Agency Head / OR / JEV row: 90pt | 198pt | flex:1(288pt) -->
            <div style="display:flex;min-height:50pt;">
                <div style="width:90pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-9" style="width:198pt;border-right:1pt solid #000;display:flex;padding:2pt;">
                    Agency Head/Authorized Representative
                </div>
                <div style="flex:1;display:flex;flex-direction:column;">
                    <div class="t-9" style="border-bottom:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                        Printed Name</div>
                    <div style="flex:1;display:flex;min-height:30pt;">
                        <div class="t-9" style="flex:1;border-right:1pt solid #000;display:flex;padding:2pt;">
                            OR/Other Documents</div>
                        <div class="t-9" style="width:100pt;display:flex;padding:2pt;">JEV. No.</div>
                    </div>
                </div>
            </div>

        </div><!-- /bottom section -->

    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    voucher: { type: Object, required: true },
    scholarDetails: { type: Array, default: () => [] },
});

/* ── helpers ─────────────────────────────────────── */
const ph = '___________';

const money = (n) =>
    '₱\u00a0' + parseFloat(n || 0)
        .toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ── explanation (Quill HTML) ────────────────────── */
const explanation = computed(() => (props.voucher.explanation || '').trim());

/* ── scholar logic ───────────────────────────────── */
const isEfa = computed(() =>
    (props.voucher.particulars_name || '').toLowerCase().includes('efa')
);

const scholarIds = computed(() =>
    Array.isArray(props.voucher.scholar_ids) ? props.voucher.scholar_ids : []
);

// DV shows only the FIRST scholar name in parentheses
const firstScholarDetail = computed(() => {
    if (!scholarIds.value.length) return null;
    const first = scholarIds.value[0];
    const profileId = typeof first === 'object' ? first.profile_id : first;
    return props.scholarDetails.find(d => String(d.profile_id) === String(profileId)) || null;
});

const scholarName = computed(() => {
    const s = firstScholarDetail.value;
    if (s) {
        let name = s.last_name + ', ' + s.first_name;
        if (s.middle_name) name += ' ' + s.middle_name;
        if (s.extension_name) name += ' ' + s.extension_name;
        return name.toUpperCase();
    }
    // fallback to wizard-saved name
    const first = scholarIds.value[0];
    if (typeof first === 'object' && first.name) return first.name.toUpperCase();
    return '';
});

/* Mirrors blade @if condition exactly */
const showScholarName = computed(() =>
    props.voucher.obr_type !== 'REIMBURSEMENT'
    && !(props.voucher.obr_type === 'FINANCIAL ASSISTANCE' && !isEfa.value)
    && scholarIds.value.length > 0
    && scholarName.value !== ''
);
</script>

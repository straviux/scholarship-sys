<template>
    <!--
        Column system (total = 576pt):
        ┌─────────┬──────────────┬────────┬───────┬──────────┬──────────┐
        │ label   │     main     │  year  │  fpp  │  code    │  amount  │
        │  100pt  │    181pt     │  70pt  │  50pt │  65pt    │  110pt   │
        └─────────┴──────────────┴────────┴───────┴──────────┴──────────┘
        Merged spans:  wide  = 251pt (main+year, cols 2–3)
                       right = 225pt (fpp+code+amt, cols 4–6)
                       all   = 576pt (full width)

        Every row uses display:flex with explicit widths summing to 576pt.
        Title: OBLIGATION REQUEST (flex:1) | No. (150pt)
    -->
    <div style="display:flex;flex-direction:column;min-height:85vh;border:1pt solid #000;">

        <!-- GOVERNMENT HEADER -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
                    border-bottom:1pt solid #000;padding:8pt 4pt;min-height:58pt;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:15%;top:50%;transform:translateY(-50%);width:54pt;height:auto;" />
            <p class="bold t-12">Republic of the Philippines</p>
            <p class="bold t-12">PROVINCIAL GOVERNMENT OF PALAWAN</p>
            <p class="t-11">OFFICE OF THE GOVERNOR</p>
        </div>

        <!-- MAIN CONTENT -->
        <div style="display:flex;flex-direction:column;flex:1;">

            <!-- TITLE ROW -->
            <div class="dv-row">
                <div class="bold t-12"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:4pt;">
                    OBLIGATION REQUEST</div>
                <div class="t-9" style="width:200pt;display:flex;align-items:center;padding:2pt;">
                    No.&nbsp;&nbsp;<span class="bold t-10">{{ voucher.obr_no || '' }}</span>
                </div>
            </div>

            <!-- PAYEE -->
            <div class="dv-row">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Payee:</div>
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    {{ voucher.payee_name || ph }}</div>
                <div style="width:200pt;">&nbsp;</div>
            </div>

            <!-- OFFICE -->
            <div class="dv-row">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Office:</div>
                <div style="flex:1;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:200pt;">&nbsp;</div>
            </div>

            <!-- ADDRESS -->
            <div class="dv-row">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Address:</div>
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    {{ voucher.payee_address || ph }}</div>
                <div style="width:200pt;">&nbsp;</div>
            </div>

            <!-- COLUMN HEADERS -->
            <div class="dv-row">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    Responsibility Center:</div>
                <div class="bold"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    PARTICULARS</div>
                <div class="t-9"
                    style="width:50pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    F.P.P</div>
                <div class="t-9"
                    style="width:50pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;text-align:center;padding:2pt;">
                    Account Code</div>
                <div class="t-9"
                    style="width:100pt;display:flex;align-items:center;justify-content:center;padding:2pt 8pt;">
                    Amount</div>
            </div>

            <!-- VALUES -->
            <div style="display:flex;min-height:30pt;">
                <div class="t-10"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;text-align:center;padding:2pt;">
                    {{ voucher.responsibility_center || ph }}</div>
                <div class="bold t-11"
                    style="flex:1;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                    {{ voucher.particulars_name || ph }}</div>
                <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-9"
                    style="width:50pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                    {{ voucher.account_code || ph }}</div>
                <div class="t-11"
                    style="width:100pt;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                    {{ showTopAmount ? money(voucher.amount) : '\u00a0' }}</div>
            </div>

            <!-- PARTICULARS DESCRIPTION (Quill HTML) -->
            <div v-if="desc" style="display:flex;min-height:30pt;">
                <div style="width:70pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-11 blk" style="flex:1;border-right:1pt solid #000;line-height:1.3;text-align:center;"
                    v-html="desc"></div>
                <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;">&nbsp;</div>
            </div>

            <!-- SCHOLAR ROWS -->
            <template v-if="showScholars">

                <!-- blank spacer row -->
                <div style="display:flex;min-height:10pt;">
                    <div style="width:70pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div style="display:flex;flex:1;border-right:1pt solid #000;">&nbsp;</div>
                    <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div style="width:100pt;">&nbsp;</div>
                </div>

                <!-- scholar column headers -->
                <div style="display:flex;min-height:10pt;">
                    <div style="width:70pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div class="t-11 bold underline" style="flex:1;display:flex;align-items:center;padding:2pt;">
                        NAME OF SCHOLARS</div>
                    <div class="t-11 bold underline"
                        style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                        YEAR</div>
                    <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div style="width:100pt;">&nbsp;</div>
                </div>

                <!-- scholar data rows -->
                <div v-for="(s, i) in scholars" :key="i" style="display:flex;min-height:10pt;">
                    <div style="width:70pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div class="t-11" style="flex:1;display:flex;align-items:center;padding:2pt;">
                        {{ i + 1 }}. {{ s.name }}</div>
                    <div class="t-11"
                        style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;padding:2pt;">
                        {{ s.year }}</div>
                    <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                    <div class="t-11"
                        style="width:100pt;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                        {{ money(s.amount) }}</div>
                </div>

            </template>

            <!-- FLEX SPACER — pushes bottom section down -->
            <div style="flex:1;display:flex;min-height:20pt;">
                <div style="width:70pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="display: flex;flex:1">&nbsp;</div>
                <div style="width:70pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:50pt;border-right:1pt solid #000;">&nbsp;</div>
                <div style="width:100pt;">&nbsp;</div>
            </div>

        </div><!-- /main content -->

        <!-- BOTTOM SECTION -->
        <div>

            <!-- TOTAL ROW -->
            <template v-if="showTotal">
                <div style="display:flex;align-items:stretch;border-top:1pt solid #000;">
                    <div style="width:70pt;">&nbsp;</div>
                    <div style="display: flex;flex:1;">&nbsp;</div>
                    <div class="bold"
                        style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;">
                        TOTAL</div>
                    <div style="width:50pt;">&nbsp;</div>
                    <div style="width:50pt;">&nbsp;</div>
                    <div class="t-11"
                        style="width:100pt;display:flex;align-items:center;justify-content:flex-end;padding:2pt 8pt;">
                        {{ money(totalAmount) }}</div>
                </div>
            </template>

            <!-- CERTIFICATION ROW -->
            <div style="display:flex;align-items:stretch;border-top:1pt solid #000;min-height:52pt;">
                <!-- A letter -->
                <div
                    style="width:20pt;height:20pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;align-items:flex-start;justify-content:center;padding-top:4pt;">
                    <span class="bold t-12">A</span>
                </div>
                <!-- A content -->
                <div style="width:285pt;border-right:1pt solid #000;padding:4pt;display:flex;flex-direction:column;">
                    <span class="bold">Certified</span>
                    <div style="display:flex;align-items:flex-start;margin-top:4pt;margin-bottom:4pt;margin-left:4pt;">
                        <div style="border:1pt solid #000;min-width:14pt;height:14pt;flex-shrink:0;margin-right:4pt;">
                        </div>
                        <span class="t-9">Charges to appropriation/allotment necessary, lawful and under my direct
                            supervision</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;margin-left:4pt;">
                        <div style="border:1pt solid #000;min-width:14pt;height:14pt;flex-shrink:0;margin-right:4pt;">
                        </div>
                        <span class="t-9">Supporting documents valid, proper and legal</span>
                    </div>
                </div>
                <!-- B letter -->
                <div
                    style="width:20pt;height:20pt;border-right:1pt solid #000;border-bottom:1pt solid #000;display:flex;align-items:flex-start;justify-content:center;padding-top:4pt;">
                    <span class="bold t-12">B</span>
                </div>
                <!-- B content -->
                <div style="flex:1;padding:4pt;display:flex;flex-direction:column;">
                    <span class="bold">Certified</span>
                    <div style="display:flex;align-items:flex-start;margin-top:4pt;margin-left:4pt;">
                        <div style="border:1pt solid #000;min-width:14pt;height:14pt;flex-shrink:0;margin-right:4pt;">
                        </div>
                        <span class="t-9">Existence of Available Appropriation</span>
                    </div>
                </div>
            </div>

            <!-- SIGNATURE -->
            <div style="display:flex;align-items:stretch;border-top:1pt solid #000;min-height:50pt;">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:flex-end;justify-content:center;padding:2pt;text-align:center;">
                    Signature</div>
                <div style="width:235pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:flex-end;justify-content:center;padding:2pt;text-align:center;">
                    Signature</div>
                <div style="display: flex; flex:1">&nbsp;</div>
            </div>

            <!-- PRINTED NAME -->
            <div style="display:flex;align-items:stretch;border-top:1pt solid #000;">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    Printed Name</div>
                <div class="bold t-11"
                    style="width:235pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    AMY ROA ALVAREZ</div>
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    Printed Name</div>
                <div class="bold t-11"
                    style="flex:1;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    MA. ISABEL E. GUINTO</div>
            </div>

            <!-- POSITION -->
            <div style="display:flex;align-items:stretch;border-top:1pt solid #000;height:32pt;">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    Position</div>
                <div class="t-11"
                    style="width:235pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    Governor</div>
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    Position</div>
                <div class="t-10"
                    style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:space-around;padding:2pt;text-align:center;">
                    <div>Supervising Administrative Officer</div>
                    <div>Acting Provincial Budget Officer</div>
                </div>
            </div>

            <!-- DATE -->
            <div style="display:flex;align-items:stretch;border-top:1pt solid #000;">
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    Date</div>
                <div style="width:235pt;border-right:1pt solid #000;">&nbsp;</div>
                <div class="t-9"
                    style="width:70pt;border-right:1pt solid #000;display:flex;align-items:center;justify-content:center;padding:2pt;text-align:center;">
                    Date</div>
                <div style="display:flex;flex:1">&nbsp;</div>
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
const ph = '___________';  // empty field placeholder

/** Format as ₱ 1,234.56 */
const money = (n) =>
    '₱\u00a0' + parseFloat(n || 0)
        .toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/* ── scholar data ────────────────────────────────── */
// Mirrors blade: stripos($voucher->particulars_name, 'EFA')
const isEfa = computed(() =>
    (props.voucher.particulars_name || '').toLowerCase().includes('efa')
);

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
            name = name.toUpperCase();
        } else {
            name = (typeof s === 'object' ? (s.name || '') : '').toUpperCase()
                || `SCHOLAR [${profileId}]`;
        }

        return {
            name,
            year: (detail?.year_level || '').toUpperCase(),
            amount: parseFloat(rawAmount) || 0,
        };
    })
);

/* ── visibility flags ────────────────────────────── */
// Mirrors blade @if conditions exactly
const showScholars = computed(() =>
    props.voucher.obr_type !== 'REIMBURSEMENT'
    && !(props.voucher.obr_type === 'FINANCIAL ASSISTANCE' && !isEfa.value)
    && scholars.value.length > 0
);

const showTopAmount = computed(() =>
    props.voucher.obr_type === 'REIMBURSEMENT'
    || (props.voucher.obr_type === 'FINANCIAL ASSISTANCE' && !isEfa.value)
);

const totalAmount = computed(() => {
    if (showScholars.value) return scholars.value.reduce((sum, s) => sum + s.amount, 0);
    if (showTopAmount.value) return parseFloat(props.voucher.amount) || 0;
    return 0;
});

const showTotal = computed(() => showScholars.value || showTopAmount.value);

/* ── description (Quill HTML) ────────────────────── */
const desc = computed(() => (props.voucher.particulars_description || '').trim());
</script>

<template>
    <!--
        Budget / Allotment Report  Landscape Long (13  8.5 in)
        9 columns: # | Date Obligated | Account Code | Prepared By | Payee |
                   Particulars | Credit | Debit | Remarks
    -->
    <div>

        <!-- GOVERNMENT HEADER -->
        <div style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
                    border-bottom:1.5pt solid #000;padding:8pt 4pt;min-height:56pt;text-align:center;">
            <img src="/images/pgp-logo.svg" alt="PGP Logo"
                style="position:absolute;left:4%;top:50%;transform:translateY(-50%);width:48pt;height:auto;" />
            <p class="bold t-11">Republic of the Philippines</p>
            <p class="bold t-11">Provincial Government of Palawan</p>
            <p class="t-10">Akbay sa Mag-aaral Yaman ng kinabukasan</p>
            <p class="t-10">(Programang Pang-Edukasyon para sa Palaweño)</p>
            <p class="t-10">Puerto Princesa City, Palawan</p>
        </div>

        <!-- REPORT TITLE -->
        <div class="center" style="padding:8pt 0 6pt;margin-bottom:6pt;">
            <p class="bold" style="font-size:13pt;">ALLOTMENT FOR {{ reportData.program_name?.toUpperCase() }}</p>
            <p class="t-9" style="margin-top:3pt;">Fiscal Year: <strong>{{ reportData.fiscal_year }}</strong></p>
            <p class="t-8" style="margin-top:2pt;">As of {{ today }}</p>
        </div>

        <!-- RC HEADER BLOCK -->
        <div style="padding:3pt 0;margin-bottom:2pt;">
            <span class="bold t-9">RESPONSIBILITY CENTER: {{ reportData.rc_name }}<span v-if="reportData.rc_code"> ({{
                reportData.rc_code }})</span></span>
        </div>

        <!-- PARTICULARS HEADER BLOCK -->
        <div class="t-8" style="display:flex;gap:16pt;margin-bottom:6pt;padding-bottom:4pt;">
            <span>Particulars: <strong>{{ reportData.particular_name }}</strong></span>
            <span>Account Code: <strong>{{ reportData.account_code || '—' }}</strong></span>
            <span>Allotment: <strong>{{ money(reportData.allotment) }}</strong></span>
        </div>

        <!-- NO DATA -->
        <div v-if="!reportData.rows || !reportData.rows.length" class="center italic" style="padding:24pt;color:#888;">
            No transactions found for this particular.
        </div>

        <!-- REPORT TABLE -->
        <table v-else class="t-8" style="width:100%;border-collapse:collapse;">
            <colgroup>
                <col style="width:20pt" />
                <col style="width:68pt" />
                <col style="width:68pt" />
                <col style="width:88pt" />
                <col style="width:110pt" />
                <col />
                <col style="width:78pt" />
                <col style="width:78pt" />
                <col style="width:60pt" />
            </colgroup>

            <!-- Column headers -->
            <thead>
                <tr>
                    <th class="b-all center bold" style="padding:5pt;">#</th>
                    <th class="b-all center bold" style="padding:5pt;">Date Obligated</th>
                    <th class="b-all center bold" style="padding:5pt;">Account Code</th>
                    <th class="b-all center bold" style="padding:5pt;">Prepared By</th>
                    <th class="b-all center bold" style="padding:5pt;">Payee</th>
                    <th class="b-all center bold" style="padding:5pt;">Particulars</th>
                    <th class="b-all right bold" style="padding:5pt;">Debit</th>
                    <th class="b-all right bold" style="padding:5pt;">Credit</th>
                    <th class="b-all center bold" style="padding:5pt;">Remarks</th>
                </tr>
            </thead>

            <tbody>
                <!-- ENDING BALANCE row (#1) — shows allotment as Credit -->
                <tr>
                    <td class="b-all center" style="padding:3pt 5pt;vertical-align:top;">1</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;"></td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;"></td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;"></td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;"></td>
                    <td class="b-all bold" style="padding:3pt 5pt;vertical-align:top;">ENDING BALANCE</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;"></td>
                    <td class="b-all right nowrap bold" style="padding:3pt 5pt;vertical-align:top;">
                        {{ money(reportData.allotment) }}</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;"></td>
                </tr>

                <!-- Data rows -->
                <tr v-for="(row, rowi) in reportData.rows" :key="rowi">
                    <td class="b-all center" style="padding:3pt 5pt;vertical-align:top;">{{ rowi + 2 }}</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;">{{ row.date_obligated }}</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;">{{ row.account_code }}</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;">{{ row.prepared_by }}</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;">{{ row.payee }}</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;">{{ row.particulars }}</td>
                    <td class="b-all" style="padding:3pt 5pt;vertical-align:top;"></td>
                    <td class="b-all right nowrap" style="padding:3pt 5pt;vertical-align:top;">
                        {{ row.credit ? money(row.credit) : '' }}
                    </td>
                    <td class="b-all center bold t-8" style="padding:3pt 5pt;vertical-align:top;">
                        {{ row.status }}
                    </td>
                </tr>

                <!-- Sub-total -->
                <tr>
                    <td colspan="6" class="b-all right t-8" style="padding:4pt 6pt;">Sub-Total</td>
                    <td class="b-all" style="padding:4pt 6pt;"></td>
                    <td class="b-all right bold nowrap" style="padding:4pt 6pt;">
                        {{ money(reportData.sub_credit) }}</td>
                    <td class="b-all" style="padding:4pt 6pt;"></td>
                </tr>

                <!-- Grand total -->
                <tr>
                    <td colspan="6" class="b-all right bold t-9" style="padding:5pt 8pt;border-top:1.5pt solid #000;">
                        GRAND TOTAL</td>
                    <td class="b-all" style="padding:5pt 8pt;border-top:1.5pt solid #000;"></td>
                    <td class="b-all right bold t-9 nowrap" style="padding:5pt 8pt;border-top:1.5pt solid #000;">
                        {{ money(reportData.sub_credit) }}</td>
                    <td class="b-all" style="padding:5pt 8pt;border-top:1.5pt solid #000;"></td>
                </tr>

            </tbody>
        </table>

    </div>
</template>

<script setup>
const props = defineProps({
    reportData: { type: Object, required: true },
    today: { type: String, default: '' },
});

function money(val) {
    if (val === null || val === undefined || val === '') return '';
    return parseFloat(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

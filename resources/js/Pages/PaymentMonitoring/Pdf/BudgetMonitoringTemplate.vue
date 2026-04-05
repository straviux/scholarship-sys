<template>
    <!--
        Budget Monitoring Report  –  Portrait A4
        Columns: # | Program | Fiscal Year | Allotment | Disbursed | Remaining | Usage %
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
        <div class="center" style="padding:8pt 0 6pt;">
            <p class="bold" style="font-size:13pt;">BUDGET MONITORING REPORT</p>
            <p class="t-9" style="margin-top:3pt;" v-if="filterProgram || filterFiscalYear">
                <span v-if="filterProgram">Program: <strong>{{ filterProgram }}</strong></span>
                <span v-if="filterProgram && filterFiscalYear"> &nbsp;·&nbsp; </span>
                <span v-if="filterFiscalYear">Fiscal Year: <strong>{{ filterFiscalYear }}</strong></span>
            </p>
            <p class="t-8" style="margin-top:2pt;">As of {{ today }}</p>
        </div>

        <!-- NOTE -->
        <p class="t-8 italic" style="margin-bottom:6pt;color:#555;">
            * Only <strong>Paid</strong> and <strong>Claimed</strong> transactions are counted as disbursed.
        </p>

        <!-- NO DATA -->
        <div v-if="!rows || !rows.length" class="center italic" style="padding:24pt;color:#888;">
            No data available.
        </div>

        <!-- REPORT TABLE -->
        <table v-else class="t-8" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th class="b-all center bold" style="padding:5pt;width:22pt;">#</th>
                    <th class="b-all center bold" style="padding:5pt;">Program</th>
                    <th class="b-all center bold" style="padding:5pt;width:66pt;">Fiscal Year</th>
                    <th class="b-all right bold" style="padding:5pt;width:88pt;">Allotment</th>
                    <th class="b-all right bold" style="padding:5pt;width:88pt;">Disbursed</th>
                    <th class="b-all right bold" style="padding:5pt;width:88pt;">Remaining</th>
                    <th class="b-all center bold" style="padding:5pt;width:52pt;">Usage %</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, i) in rows" :key="i">
                    <td class="b-all center" style="padding:3pt 5pt;">{{ i + 1 }}</td>
                    <td class="b-all bold" style="padding:3pt 5pt;">{{ row.program }}</td>
                    <td class="b-all center" style="padding:3pt 5pt;">{{ row.fiscal_year || '—' }}</td>
                    <td class="b-all right nowrap" style="padding:3pt 5pt;">{{ money(row.total_allotment) }}</td>
                    <td class="b-all right nowrap" style="padding:3pt 5pt;">{{ money(row.disbursed) }}</td>
                    <td class="b-all right nowrap bold" :style="row.overBudget ? 'color:#c0392b;' : 'color:#27ae60;'"
                        style="padding:3pt 5pt;">
                        {{ money(row.remaining) }}
                        <span v-if="row.overBudget" style="font-size:7pt;display:block;font-weight:normal;">over
                            budget</span>
                    </td>
                    <td class="b-all center" style="padding:3pt 5pt;">
                        {{ row.total_allotment > 0
                            ? ((row.disbursed / row.total_allotment) * 100).toFixed(2) + '%'
                            : '0.00%' }}
                    </td>
                </tr>

                <!-- Totals row -->
                <tr style="border-top:1.5pt solid #000;">
                    <td colspan="3" class="b-all right bold t-9" style="padding:5pt 8pt;">GRAND TOTAL</td>
                    <td class="b-all right bold nowrap t-9" style="padding:5pt 8pt;">{{ money(totals.allotment) }}</td>
                    <td class="b-all right bold nowrap t-9" style="padding:5pt 8pt;">{{ money(totals.disbursed) }}</td>
                    <td class="b-all right bold nowrap t-9"
                        :style="totals.remaining >= 0 ? 'color:#27ae60;' : 'color:#c0392b;'" style="padding:5pt 8pt;">
                        {{ money(totals.remaining) }}
                    </td>
                    <td class="b-all center bold t-9" style="padding:5pt 8pt;">
                        {{ totals.allotment > 0
                            ? ((totals.disbursed / totals.allotment) * 100).toFixed(2) + '%'
                            : '0.00%' }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- SIGNATURE BLOCK -->
        <div style="margin-top:24pt;display:flex;gap:32pt;">
            <div style="flex:1;text-align:center;">
                <div style="border-top:1pt solid #000;margin-top:36pt;padding-top:3pt;" class="bold t-9">
                    Prepared by
                </div>
            </div>
            <div style="flex:1;text-align:center;">
                <div style="border-top:1pt solid #000;margin-top:36pt;padding-top:3pt;" class="bold t-9">
                    Noted by
                </div>
            </div>
            <div style="flex:1;text-align:center;">
                <div style="border-top:1pt solid #000;margin-top:36pt;padding-top:3pt;" class="bold t-9">
                    Approved by
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
defineProps({
    rows: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({ allotment: 0, disbursed: 0, remaining: 0 }) },
    filterProgram: { type: String, default: null },
    filterFiscalYear: { type: [String, Number], default: null },
    today: { type: String, default: '' },
});

function money(val) {
    if (val === null || val === undefined || val === '') return '';
    return parseFloat(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
